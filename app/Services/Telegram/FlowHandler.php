<?php

namespace App\Services\Telegram;

use App\Models\Laporan;
use App\Models\TelegramMessageMap;
use App\Models\TelegramSession;
use App\Models\User;
use App\Services\Laporan\LaporanService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class FlowHandler
{
    protected TelegramClient $client;
    protected LaporanService $laporanService;

    public function __construct(TelegramClient $client, ?LaporanService $laporanService = null)
    {
        $this->client = $client;
        $this->laporanService = $laporanService ?? app(LaporanService::class);
    }

    /**
     * Handle non-command messages (text, photo, document, replies, etc.)
     */
    public function handle(array $message): void
    {
        $chatId = $message['chat']['id'] ?? ($message['from']['id'] ?? null);
        $fromId = $message['from']['id'] ?? null;
        $messageId = $message['message_id'] ?? null;
        $chatType = $message['chat']['type'] ?? 'private';
        $topicId = $message['message_thread_id'] ?? null;
        $adminGroupId = config('services.telegram.admin_group_id');

        if (!$chatId) {
            return;
        }

        // 1. Deteksi pesan di Topic Forum Grup Admin Telegram
        if ($chatType !== 'private' && !empty($adminGroupId) && (string)$chatId === (string)$adminGroupId && $topicId) {
            $this->handleAdminTopicMessage($message, (int) $topicId, (int) $fromId, $messageId);
            return;
        }

        // 2. Rate Limiting: 20 pesan per menit per chat
        $rateLimitKey = 'telegram_msg:' . $chatId;
        if (RateLimiter::tooManyAttempts($rateLimitKey, 20)) {
            $seconds = RateLimiter::availableIn($rateLimitKey);
            $this->client->sendMessage(
                $chatId,
                "⚠️ Terlalu banyak pesan. Mohon tunggu {$seconds} detik sebelum mengirim pesan kembali."
            );
            return;
        }
        RateLimiter::hit($rateLimitKey, 60);

        // 3. Batasan: User belum tertaut hanya dijawab instruksi menautkan akun
        $user = User::where('telegram_chat_id', (int) $chatId)->first();
        if (!$user) {
            $this->client->sendMessage(
                $chatId,
                "Akun Telegram Anda belum ditautkan dengan akun Lapaktifikasi. Silakan login ke website Lapaktifikasi dan tautkan Telegram Anda terlebih dahulu."
            );
            return;
        }

        // 4. Ekstrak teks pesan (text atau caption)
        $text = trim($message['text'] ?? ($message['caption'] ?? ''));

        // 5. Filter Kredensial / Password: Jangan pernah menerima atau menyimpan kata sandi via chat
        if ($this->containsCredential($text)) {
            $warning = "⚠️ <b>Peringatan Keamanan!</b>\n\n" .
                       "Pesan Anda terdeteksi memuat kata sandi (password) atau informasi kredensial akun.\n\n" .
                       "Demi alasan keamanan dan privasi data akun Anda, Lapaktifikasi <b>tidak menerima kredensial akun melalui Telegram</b>.\n\n" .
                       "Pesan ini <b>tidak disimpan</b> ke sistem. Mohon hubungi admin langsung di website jika memerlukan penanganan kredensial akun.";
            $this->client->sendMessage($chatId, $warning);
            return;
        }

        // 6. Tangani lampiran berkas jika ada (foto atau dokumen PDF/gambar)
        $lampiranPath = $this->handleIncomingAttachment($message);

        // 7. Cek sesi aktif di tbl_telegram_sessions (flow='balas' atau flow='lapor')
        $session = TelegramSession::where('chat_id', (int) $chatId)
            ->where('expires_at', '>', now())
            ->first();

        if ($session) {
            if ($session->flow === 'balas') {
                $this->handleSessionBalas($chatId, $messageId, $session, $user, $text, $lampiranPath);
                return;
            }

            if ($session->flow === 'lapor') {
                $this->handleSessionLapor($chatId, $messageId, $session, $user, $text, $lampiranPath);
                return;
            }
        }

        // 8. Cek apakah ini balasan (reply) langsung ke notifikasi bot
        $replyTo = $message['reply_to_message'] ?? null;
        if ($replyTo && !empty($replyTo['message_id'])) {
            $repliedMessageId = (int) $replyTo['message_id'];
            $map = TelegramMessageMap::where('chat_id', (int) $chatId)
                ->where('telegram_message_id', $repliedMessageId)
                ->first();

            if ($map) {
                $this->handleDirectReplyMap($chatId, $messageId, $map, $user, $text, $lampiranPath);
                return;
            }
        }

        // 9. Pesan umum di luar alur tiket atau sesi
        $guidance = "Halo <b>" . TelegramClient::escape($user->name ?: 'Pengguna') . "</b>,\n\n" .
                    "Ketik /lapor untuk membuat laporan kendala baru, /tiket untuk melihat status laporan Anda, atau balas pesan notifikasi tiket secara langsung untuk mengirim balasan.";
        $this->client->sendMessage($chatId, $guidance);
    }

    /**
     * Tangani pesan yang dikirim Admin di Topic Grup Telegram.
     */
    protected function handleAdminTopicMessage(array $message, int $topicId, int $fromId, ?int $messageId): void
    {
        $adminUser = User::where('telegram_chat_id', $fromId)->first();
        if (!$adminUser || !$adminUser->isAdmin()) {
            return; // Hanya admin terdaftar yang dapat membalas tiket di topic
        }

        $laporan = Laporan::where('telegram_topic_id', $topicId)->first();
        if (!$laporan) {
            return;
        }

        $text = trim($message['text'] ?? ($message['caption'] ?? ''));
        if ($this->containsCredential($text)) {
            $this->client->sendMessage(
                $message['chat']['id'],
                "⚠️ Pesan tidak diproses karena terdeteksi memuat format kredensial akun.",
                ['message_thread_id' => $topicId]
            );
            return;
        }

        $lampiranPath = $this->handleIncomingAttachment($message);

        $body = $text !== '' ? $text : ($lampiranPath ? '(Mengirim lampiran berkas)' : 'Pesan dari Admin Support');

        $this->laporanService->addMessage(
            $laporan,
            $adminUser,
            $body,
            'telegram',
            $lampiranPath,
            $messageId
        );
    }

    /**
     * Deteksi apakah teks memuat pola password atau kredensial.
     */
    public function containsCredential(string $text): bool
    {
        if ($text === '') {
            return false;
        }

        $patterns = [
            '/(?:password|kata\s*sandi|pass|pwd)\s*[:=]\s*\S+/i',
            '/(?:email|username|user)\s*[:=]\s*\S+\s+(?:password|kata\s*sandi|pass|pwd)\s*[:=]\s*\S+/i',
            '/\b[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}\s*[:|]\s*\S+/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Unduh dan simpan foto atau dokumen lampiran dari pesan Telegram.
     */
    protected function handleIncomingAttachment(array $message): ?string
    {
        $fileId = null;
        $extension = 'jpg';

        if (!empty($message['photo']) && is_array($message['photo'])) {
            $lastPhoto = end($message['photo']);
            $fileId = $lastPhoto['file_id'] ?? null;
            $extension = 'jpg';
        } elseif (!empty($message['document']) && is_array($message['document'])) {
            $doc = $message['document'];
            $mimeType = $doc['mime_type'] ?? '';
            $fileSize = (int) ($doc['file_size'] ?? 0);

            // Batasi ukuran dokumen maks 10MB
            if ($fileSize > 10 * 1024 * 1024) {
                return null;
            }

            // Batasi jenis: gambar atau PDF
            if (!str_starts_with($mimeType, 'image/') && $mimeType !== 'application/pdf') {
                return null;
            }

            $fileId = $doc['file_id'] ?? null;
            $extension = $mimeType === 'application/pdf' ? 'pdf' : (pathinfo($doc['file_name'] ?? '', PATHINFO_EXTENSION) ?: 'jpg');
        }

        if (!$fileId) {
            return null;
        }

        try {
            $fileInfo = $this->client->getFile($fileId);
            $remotePath = $fileInfo['result']['file_path'] ?? null;
            if (!$remotePath) {
                return null;
            }

            $targetDir = public_path('assets/img/laporan');
            if (!file_exists($targetDir)) {
                mkdir($targetDir, 0755, true);
            }

            $filename = time() . '_' . Str::random(12) . '.' . $extension;
            $localPath = $targetDir . DIRECTORY_SEPARATOR . $filename;

            $this->client->downloadFile($remotePath, $localPath);
            return $filename;
        } catch (\Throwable $e) {
            Log::error('FlowHandler: Gagal mengunduh lampiran Telegram: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Tangani sesi balas tiket (flow='balas').
     */
    protected function handleSessionBalas(
        int|string $chatId,
        ?int $messageId,
        TelegramSession $session,
        User $user,
        string $text,
        ?string $lampiranPath
    ): void {
        $laporanId = $session->payload['laporan_id'] ?? null;
        $laporan = $laporanId ? Laporan::find($laporanId) : null;

        if (!$laporan) {
            $this->client->sendMessage($chatId, "❌ Tiket laporan tidak ditemukan.");
            $session->delete();
            return;
        }

        if (!$this->isAuthorizedForTicket($laporan, $user)) {
            $this->client->sendMessage($chatId, "❌ Anda tidak memiliki hak akses untuk membalas tiket ini.");
            $session->delete();
            return;
        }

        $action = $session->payload['action'] ?? null;
        $bodyPrefix = ($action === 'mintainfo') ? "[Permintaan Info Tambahan]: " : "";
        $body = $text !== '' ? ($bodyPrefix . $text) : ($lampiranPath ? '(Mengirim lampiran berkas)' : 'Tanggapan via Telegram');

        $this->laporanService->addMessage(
            $laporan,
            $user,
            $body,
            'telegram',
            $lampiranPath,
            $messageId
        );

        $session->delete();

        $num = '#' . str_pad($laporan->id, 5, '0', STR_PAD_LEFT);
        $this->client->sendMessage(
            $chatId,
            "✅ <b>Pesan Terkirim</b>\n\nBalasan Anda untuk Tiket <b>{$num}</b> berhasil dikirimkan ke thread tiket."
        );
    }

    /**
     * Tangani balasan langsung via reply ke notifikasi bot (menggunakan tbl_telegram_message_map).
     */
    protected function handleDirectReplyMap(
        int|string $chatId,
        ?int $messageId,
        TelegramMessageMap $map,
        User $user,
        string $text,
        ?string $lampiranPath
    ): void {
        $laporan = Laporan::find($map->laporan_id);
        if (!$laporan) {
            $this->client->sendMessage($chatId, "❌ Tiket laporan yang Anda balas tidak ditemukan.");
            return;
        }

        if (!$this->isAuthorizedForTicket($laporan, $user)) {
            $this->client->sendMessage($chatId, "❌ Anda tidak memiliki hak akses untuk membalas tiket ini.");
            return;
        }

        $body = $text !== '' ? $text : ($lampiranPath ? '(Mengirim lampiran berkas)' : 'Tanggapan via Telegram');

        $this->laporanService->addMessage(
            $laporan,
            $user,
            $body,
            'telegram',
            $lampiranPath,
            $messageId
        );

        $num = '#' . str_pad($laporan->id, 5, '0', STR_PAD_LEFT);
        $this->client->sendMessage(
            $chatId,
            "✅ <b>Pesan Terkirim</b>\n\nBalasan Anda untuk Tiket <b>{$num}</b> berhasil dikirimkan ke thread tiket."
        );
    }

    /**
     * Tangani input teks/foto pada wizard /lapor.
     */
    protected function handleSessionLapor(
        int|string $chatId,
        ?int $messageId,
        TelegramSession $session,
        User $user,
        string $text,
        ?string $lampiranPath
    ): void {
        $payload = $session->payload ?? [];

        // Step: Input kronologi teks
        if ($session->step === 'input_kronologi') {
            if ($text === '') {
                $this->client->sendMessage(
                    $chatId,
                    "Mohon ketikkan penjelasan detail mengenai kendala Anda dalam bentuk teks."
                );
                return;
            }

            $payload['kronologi'] = $text;
            $session->update([
                'step' => 'input_bukti',
                'payload' => $payload,
                'expires_at' => now()->addMinutes(15),
            ]);

            $prompt = "📸 <b>Kirim Foto Bukti Kendala (Opsional):</b>\n\n" .
                      "Silakan kirimkan screenshot atau foto bukti kendala Anda ke chat ini.\n\n" .
                      "Jika Anda tidak memiliki foto bukti, silakan klik tombol <b>Lewati Foto</b> di bawah:";

            $keyboard = [
                [
                    ['text' => '⏩ Lewati Foto', 'callback_data' => 'lapor_skip_photo'],
                    ['text' => '❌ Batal', 'callback_data' => 'lapor_cancel'],
                ],
            ];

            $this->client->sendMessage($chatId, $prompt, [
                'reply_markup' => ['inline_keyboard' => $keyboard],
            ]);
            return;
        }

        // Step: Input bukti foto/dokumen
        if ($session->step === 'input_bukti') {
            if ($lampiranPath) {
                $payload['gambar'] = $lampiranPath;
                $session->update([
                    'step' => 'confirm',
                    'payload' => $payload,
                    'expires_at' => now()->addMinutes(15),
                ]);

                app(CallbackRouter::class)->renderConfirmation($chatId, $messageId ?? 0, $payload);
            } else {
                $this->client->sendMessage(
                    $chatId,
                    "Silakan kirimkan foto/screenshot bukti kendala Anda, atau klik tombol <b>Lewati Foto</b> jika tidak ada bukti gambar."
                );
            }
            return;
        }
    }

    /**
     * Cek apakah user berhak atas tiket laporan.
     */
    public function isAuthorizedForTicket(Laporan $laporan, User $user): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($laporan->user_id === $user->id) {
            return true;
        }

        if ($laporan->assigned_to === $user->id) {
            return true;
        }

        $context = app(NotificationRouter::class)->resolveTicketContext($laporan);
        $seller = $context['seller_user'] ?? null;
        if ($seller && $seller->id === $user->id) {
            return true;
        }

        return false;
    }
}
