<?php

namespace App\Services\Laporan;

use App\Models\Laporan;
use App\Models\LaporanLog;
use App\Models\LaporanPesan;
use App\Models\SettingWebsite;
use App\Models\User;
use App\Services\Telegram\NotificationRouter;
use App\Services\Telegram\TelegramClient;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LaporanService
{
    protected NotificationRouter $notificationRouter;
    protected TelegramClient $telegramClient;

    public function __construct(NotificationRouter $notificationRouter, ?TelegramClient $telegramClient = null)
    {
        $this->notificationRouter = $notificationRouter;
        $this->telegramClient = $telegramClient ?? app(TelegramClient::class);
    }

    /**
     * Buat tiket laporan baru (digunakan bersama oleh form web dan wizard Telegram).
     *
     * @throws ValidationException
     */
    public function createTicket(User $user, array $data): Laporan
    {
        // 1. Rate limit: Maksimal 3 tiket baru per jam per customer
        $limiterKey = 'laporan_create:' . $user->id;
        if (RateLimiter::tooManyAttempts($limiterKey, 3)) {
            $seconds = RateLimiter::availableIn($limiterKey);
            $minutes = ceil($seconds / 60);
            throw ValidationException::withMessages([
                'rate_limit' => "Batas pembuatan tiket tercapai (maksimal 3 tiket per jam). Silakan coba lagi dalam {$minutes} menit.",
            ]);
        }

        // 2. Validasi field wajib
        $judul = trim($data['judul'] ?? '');
        $deskripsi = trim($data['deskripsi'] ?? '');

        if ($judul === '') {
            throw ValidationException::withMessages(['judul' => 'Judul laporan harus diisi.']);
        }
        if ($deskripsi === '') {
            throw ValidationException::withMessages(['deskripsi' => 'Deskripsi kendala harus diisi.']);
        }

        $slaHours = SettingWebsite::getCached()?->sla_respon_jam ?? 3;
        $slaDueAt = now()->addHours($slaHours);

        return DB::transaction(function () use ($user, $data, $judul, $deskripsi, $slaDueAt, $limiterKey) {
            $laporan = Laporan::create([
                'user_id' => $user->id,
                'assigned_to' => $data['assigned_to'] ?? null,
                'judul' => $judul,
                'deskripsi' => $deskripsi,
                'gambar' => $data['gambar'] ?? null,
                'status' => 'pending',
                'sla_due_at' => $slaDueAt,
            ]);

            // Buat pesan pembuka di thread percakapan
            LaporanPesan::create([
                'laporan_id' => $laporan->id,
                'sender_user_id' => $user->id,
                'sender_role' => 'customer',
                'body' => $deskripsi,
                'sumber' => $data['sumber'] ?? 'web',
                'lampiran' => $data['gambar'] ?? null,
                'telegram_message_id' => $data['telegram_message_id'] ?? null,
            ]);

            RateLimiter::hit($limiterKey, 3600);

            // Catat log awal pembuatan tiket
            $sumberLog = ($data['sumber'] ?? 'web') === 'telegram' ? 'telegram_customer' : 'web_customer';
            $this->logStatusChange($laporan, null, 'pending', $sumberLog, $user->id, 'Tiket laporan baru dibuat oleh customer');

            // Kirim notifikasi ke customer, seller, dan admin
            $this->notificationRouter->notifyTicketCreated($laporan);

            return $laporan;
        });
    }

    /**
     * Tambah pesan balasan baru ke thread tiket laporan.
     */
    public function addMessage(
        Laporan $laporan,
        User $sender,
        string $body,
        string $sumber = 'web',
        ?string $lampiran = null,
        ?int $telegramMessageId = null
    ): LaporanPesan {
        $cleanBody = trim($body);

        $senderRole = match (true) {
            $sender->isAdmin() => 'admin',
            $sender->isSeller() => 'seller',
            default => 'customer',
        };

        return DB::transaction(function () use ($laporan, $sender, $senderRole, $cleanBody, $sumber, $lampiran, $telegramMessageId) {
            $pesan = LaporanPesan::create([
                'laporan_id' => $laporan->id,
                'sender_user_id' => $sender->id,
                'sender_role' => $senderRole,
                'body' => $cleanBody,
                'sumber' => $sumber,
                'lampiran' => $lampiran,
                'telegram_message_id' => $telegramMessageId,
            ]);

            // Jika pengirim adalah seller atau admin, tandai first_response_at jika belum ada
            if ($senderRole === 'seller' || $senderRole === 'admin') {
                $updates = [];
                if (is_null($laporan->first_response_at)) {
                    $updates['first_response_at'] = now();
                }
                $oldStatus = $laporan->status;
                if ($laporan->status === 'pending') {
                    $updates['status'] = 'proses';
                }
                if (!empty($updates)) {
                    $laporan->update($updates);

                    if (isset($updates['status']) && $oldStatus !== 'proses') {
                        $sumberLog = $sumber === 'telegram'
                            ? ($senderRole === 'seller' ? 'telegram_seller' : 'telegram_admin')
                            : ($senderRole === 'seller' ? 'web_seller' : 'web_admin');

                        $this->logStatusChange(
                            $laporan,
                            $oldStatus,
                            'proses',
                            $sumberLog,
                            $sender->id,
                            'Respon pertama diterima, status tiket menjadi proses'
                        );
                    }
                }
            }

            // Notifikasi ke pihak lain via NotificationRouter
            $this->notificationRouter->notifyNewThreadMessage($pesan);

            return $pesan;
        });
    }

    /**
     * Aksi Ambil / Tetapkan Tiket:
     * Mengisi assigned_to, update status ke 'proses' jika pending, dan isi first_response_at jika kosong.
     */
    public function assignTicket(Laporan $laporan, User $actor, ?User $assignee = null, string $sumber = 'telegram_seller'): Laporan
    {
        $targetUser = $assignee ?? $actor;
        $oldStatus = $laporan->status;

        $updates = [
            'assigned_to' => $targetUser->id,
        ];

        if (is_null($laporan->first_response_at)) {
            $updates['first_response_at'] = now();
        }

        if ($laporan->status === 'pending') {
            $updates['status'] = 'proses';
        }

        $laporan->update($updates);

        if ($oldStatus !== $laporan->status) {
            $this->logStatusChange(
                $laporan,
                $oldStatus,
                $laporan->status,
                $sumber,
                $actor->id,
                "Tiket diambil/ditetapkan kepada {$targetUser->name} dan status menjadi proses"
            );
            $this->notificationRouter->notifyTicketStatusUpdated($laporan, $oldStatus, $laporan->status);
        } else {
            $this->logStatusChange(
                $laporan,
                $oldStatus,
                $oldStatus,
                $sumber,
                $actor->id,
                "Tiket dialihkan/ditetapkan kepada {$targetUser->name}"
            );
        }

        return $laporan->fresh();
    }

    /**
     * Ubah status tiket (pending, proses, selesai) dan kirim notifikasi terkait.
     */
    public function updateStatus(
        Laporan $laporan,
        User $actor,
        string $newStatus,
        string $sumber = 'web_admin',
        ?string $keterangan = null
    ): Laporan {
        if (!in_array($newStatus, ['pending', 'proses', 'selesai'], true)) {
            throw new \InvalidArgumentException("Status {$newStatus} tidak valid.");
        }

        $oldStatus = $laporan->status;
        if ($oldStatus === $newStatus) {
            return $laporan;
        }

        $laporan->update(['status' => $newStatus]);

        $this->logStatusChange($laporan, $oldStatus, $newStatus, $sumber, $actor->id, $keterangan);

        if ($newStatus === 'selesai') {
            $this->notificationRouter->notifyTicketCompleted($laporan);
        } else {
            $this->notificationRouter->notifyTicketStatusUpdated($laporan, $oldStatus, $newStatus);
        }

        return $laporan->fresh();
    }

    /**
     * Aksi Eskalasi (Seller / Otomatis SLA):
     * Mengisi escalated_at, membuat Topic di Grup Admin Telegram via createForumTopic,
     * menyimpan telegram_topic_id, dan mengirim ringkasan serta tombol aksi admin ke topic itu.
     * Bersifat IDEMPOTEN: jika sudah pernah dieskalasi, operasi diabaikan (return false).
     */
    public function escalateTicket(
        Laporan $laporan,
        User|string $actor,
        string $sumber = 'telegram_seller',
        ?string $alasan = null
    ): bool {
        // Cek idempoten: jika sudah pernah dieskalasi, jangan duplikasi topic
        if (!is_null($laporan->escalated_at)) {
            return false;
        }

        $now = now();
        $laporan->escalated_at = $now;
        $laporan->save();

        $adminGroupId = config('services.telegram.admin_group_id');
        $context = $this->notificationRouter->resolveTicketContext($laporan);
        $tokoNama = $context['toko_nama'] ?? 'Toko';
        $ticketNum = '#' . str_pad($laporan->id, 5, '0', STR_PAD_LEFT);

        if (!empty($adminGroupId) && $this->telegramClient->isEnabled()) {
            try {
                $topicName = Str::limit("{$ticketNum} - {$tokoNama}", 120);
                $topicRes = $this->telegramClient->createForumTopic($adminGroupId, $topicName);
                $topicId = is_array($topicRes)
                    ? ($topicRes['result']['message_thread_id'] ?? $topicRes['message_thread_id'] ?? null)
                    : null;

                if ($topicId) {
                    $laporan->telegram_topic_id = (int) $topicId;
                    $laporan->save();

                    // Kirim ringkasan tiket dan tombol aksi admin ke dalam forum topic baru
                    $summaryHtml = "<b>🚨 ESKALASI TIKET LAPORAN {$ticketNum}</b>\n\n" .
                                   "• <b>Nomor Tiket:</b> {$ticketNum}\n" .
                                   "• <b>Status:</b> " . ucfirst($laporan->status) . "\n" .
                                   "• <b>Toko:</b> " . TelegramClient::escape($tokoNama) . "\n" .
                                   "• <b>Judul:</b> " . TelegramClient::escape($laporan->judul) . "\n" .
                                   "• <b>Deskripsi:</b> " . TelegramClient::escape(Str::limit($laporan->deskripsi, 200)) . "\n" .
                                   "• <b>Alasan Eskalasi:</b> " . TelegramClient::escape($alasan ?? 'Eskalasi oleh seller / Batas waktu SLA terlampaui') . "\n\n" .
                                   "<i>Admin dapat membalas chat ini langsung di topic atau menggunakan tombol aksi di bawah:</i>";

                    $adminMarkup = $this->buildAdminActionButtons($laporan);

                    $this->telegramClient->sendMessage($adminGroupId, $summaryHtml, [
                        'reply_markup' => $adminMarkup,
                        'message_thread_id' => $topicId,
                    ]);
                }
            } catch (\Throwable $e) {
                Log::error("LaporanService::escalateTicket: Gagal membuat forum topic Telegram: " . $e->getMessage());
            }
        }

        $actorId = ($actor instanceof User) ? $actor->id : null;
        $this->logStatusChange(
            $laporan,
            $laporan->status,
            $laporan->status,
            $sumber,
            $actorId,
            $alasan ?? 'Tiket dieskalasi ke grup admin forum topic'
        );

        return true;
    }

    /**
     * Catat log audit perubahan tiket ke tbl_laporan_log.
     */
    public function logStatusChange(
        Laporan $laporan,
        ?string $oldStatus,
        string $newStatus,
        string $sumber,
        ?int $userId = null,
        ?string $keterangan = null
    ): LaporanLog {
        return LaporanLog::create([
            'laporan_id' => $laporan->id,
            'user_id' => $userId,
            'status_lama' => $oldStatus,
            'status_baru' => $newStatus,
            'sumber_perubahan' => $sumber,
            'keterangan' => $keterangan,
        ]);
    }

    /**
     * Susun tombol aksi Telegram untuk Seller (format callback pendek lp:{id}:{aksi}).
     */
    public function buildSellerActionButtons(Laporan $laporan): array
    {
        $id = $laporan->id;
        $status = $laporan->status;
        $buttons = [];

        // Baris 1: Ambil Tiket (jika pending atau belum assigned) & Minta Info
        $row1 = [];
        if ($status === 'pending' || is_null($laporan->assigned_to)) {
            $row1[] = ['text' => '🙋 Ambil Tiket', 'callback_data' => "lp:{$id}:ambil"];
        }
        if ($status !== 'selesai') {
            $row1[] = ['text' => '❓ Minta Info', 'callback_data' => "lp:{$id}:mintainfo"];
        }
        if (!empty($row1)) {
            $buttons[] = $row1;
        }

        // Baris 2: Kirim Akun Pengganti & Eskalasi
        $row2 = [];
        if ($status !== 'selesai') {
            $row2[] = ['text' => '🔄 Ganti Akun', 'callback_data' => "lp:{$id}:ganti"];
            if (is_null($laporan->escalated_at)) {
                $row2[] = ['text' => '⚠️ Eskalasi', 'callback_data' => "lp:{$id}:eskalasi"];
            }
        }
        if (!empty($row2)) {
            $buttons[] = $row2;
        }

        // Baris 3: Selesai & Buka Website
        $row3 = [];
        if ($status !== 'selesai') {
            $row3[] = ['text' => '✅ Selesai', 'callback_data' => "lp:{$id}:selesai"];
        }
        $row3[] = ['text' => '🌐 Buka di Website', 'url' => url('/premium/laporan/' . $id)];
        $buttons[] = $row3;

        return ['inline_keyboard' => $buttons];
    }

    /**
     * Susun tombol aksi Telegram untuk Admin (format callback pendek lp:{id}:{aksi}).
     */
    public function buildAdminActionButtons(Laporan $laporan): array
    {
        $id = $laporan->id;
        $status = $laporan->status;
        $adminUrl = url('/premium/laporan/' . $id);
        $buttons = [];

        // Baris 1: Ambil Alih & Tetapkan ke Seller
        $row1 = [];
        if ($status !== 'selesai') {
            $row1[] = ['text' => '👨‍💼 Ambil Alih', 'callback_data' => "lp:{$id}:ambil"];
            $row1[] = ['text' => '🏪 Tetapkan ke Seller', 'callback_data' => "lp:{$id}:tetapkan"];
        }
        if (!empty($row1)) {
            $buttons[] = $row1;
        }

        // Baris 2: Ubah Status (Proses / Selesai)
        $row2 = [];
        if ($status === 'pending') {
            $row2[] = ['text' => '⚙️ Set Proses', 'callback_data' => "lp:{$id}:status_proses"];
        }
        if ($status !== 'selesai') {
            $row2[] = ['text' => '✅ Set Selesai', 'callback_data' => "lp:{$id}:status_selesai"];
        }
        if (!empty($row2)) {
            $buttons[] = $row2;
        }

        // Baris 3: Buka Dashboard Admin
        $buttons[] = [
            [['text' => '🌐 Buka Dashboard Admin', 'url' => $adminUrl]],
        ];

        return ['inline_keyboard' => $buttons];
    }
}
