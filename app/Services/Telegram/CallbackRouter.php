<?php

namespace App\Services\Telegram;

use App\Enums\PembelianStatus;
use App\Models\CustomerModel;
use App\Models\Laporan;
use App\Models\Pembelian;
use App\Models\SettingWebsite;
use App\Models\TelegramSession;
use App\Models\User;
use App\Services\Laporan\LaporanService;
use Illuminate\Support\Str;

class CallbackRouter
{
    public const CATEGORIES = [
        1 => 'Akun Tidak Bisa Login',
        2 => 'Akun Terkena Limit / Penuh',
        3 => 'Langganan Turun / Back to Free',
        4 => 'File Digital Rusak / Tidak Bisa Akses',
        5 => 'Masalah Lainnya',
    ];

    protected TelegramClient $client;
    protected TelegramLinkService $linkService;
    protected LaporanService $laporanService;

    public function __construct(
        TelegramClient $client,
        ?TelegramLinkService $linkService = null,
        ?LaporanService $laporanService = null
    ) {
        $this->client = $client;
        $this->linkService = $linkService ?? app(TelegramLinkService::class);
        $this->laporanService = $laporanService ?? app(LaporanService::class);
    }

    /**
     * Handle callback_query from inline keyboard buttons.
     */
    public function handle(array $callbackQuery): void
    {
        $id = $callbackQuery['id'] ?? null;
        $data = $callbackQuery['data'] ?? '';
        $chatId = $callbackQuery['message']['chat']['id'] ?? ($callbackQuery['from']['id'] ?? null);
        $messageId = $callbackQuery['message']['message_id'] ?? null;
        $fromId = $callbackQuery['from']['id'] ?? $chatId;

        if (!$chatId) {
            return;
        }

        // 1. Format callback pendek aksi tiket lp:{laporan_id}:{aksi} (maksimal 64 byte)
        if (preg_match('/^lp:(\d+):([a-z_]+)$/', $data, $matches)) {
            $ticketId = (int) $matches[1];
            $action = $matches[2];
            $this->handleTicketActionCallback($id, $chatId, $messageId, (int) $fromId, $ticketId, $action);
            return;
        }

        // 2. Format legacy reply ticket button
        if (str_starts_with($data, 'reply_ticket_')) {
            if ($id) {
                $this->client->answerCallbackQuery($id);
            }
            $ticketId = (int) substr($data, strlen('reply_ticket_'));
            $this->handleReplyTicketButton($chatId, $messageId, $ticketId);
            return;
        }

        // 3. Callback penautan akun (unlink)
        if ($data === 'unlink_confirm') {
            if ($id) {
                $this->client->answerCallbackQuery($id);
            }
            $this->handleUnlinkConfirm($chatId, $messageId);
            return;
        }

        if ($data === 'unlink_cancel') {
            if ($id) {
                $this->client->answerCallbackQuery($id);
            }
            $this->handleUnlinkCancel($chatId, $messageId);
            return;
        }

        // 4. Callback wizard /lapor
        if (str_starts_with($data, 'lapor_order_')) {
            if ($id) {
                $this->client->answerCallbackQuery($id);
            }
            $orderId = substr($data, strlen('lapor_order_'));
            $this->handleLaporSelectOrder($chatId, $messageId, $orderId);
            return;
        }

        if (str_starts_with($data, 'lapor_cat_')) {
            if ($id) {
                $this->client->answerCallbackQuery($id);
            }
            $catIdx = (int) substr($data, strlen('lapor_cat_'));
            $this->handleLaporSelectCategory($chatId, $messageId, $catIdx);
            return;
        }

        if ($data === 'lapor_skip_photo') {
            if ($id) {
                $this->client->answerCallbackQuery($id);
            }
            $this->handleLaporSkipPhoto($chatId, $messageId);
            return;
        }

        if ($data === 'lapor_submit') {
            if ($id) {
                $this->client->answerCallbackQuery($id);
            }
            $this->handleLaporSubmit($chatId, $messageId);
            return;
        }

        if ($data === 'lapor_cancel') {
            if ($id) {
                $this->client->answerCallbackQuery($id);
            }
            $this->handleLaporCancel($chatId, $messageId);
            return;
        }
    }

    /**
     * Tangani aksi tiket format pendek: lp:{laporan_id}:{aksi}
     * Aksi: ambil, mintainfo, selesai, tutup, status_selesai, status_proses, eskalasi, tetapkan, ganti.
     */
    protected function handleTicketActionCallback(
        ?string $callbackId,
        int|string $chatId,
        ?int $messageId,
        int $fromId,
        int $ticketId,
        string $action
    ): void {
        // Validasi 1: Pengirim harus terdaftar dan tertaut dengan Telegram
        $user = User::where('telegram_chat_id', $fromId)->first();
        if (!$user) {
            $this->answerQuery($callbackId, 'Akun Telegram Anda belum ditautkan dengan akun Lapaktifikasi.', true);
            return;
        }

        // Validasi 2: Tiket harus ada
        $laporan = Laporan::find($ticketId);
        if (!$laporan) {
            $this->answerQuery($callbackId, 'Tiket laporan tidak ditemukan.', true);
            return;
        }

        // Validasi 3: Verifikasi hak atas tiket
        $isAdmin = $user->isAdmin();
        $context = app(NotificationRouter::class)->resolveTicketContext($laporan);
        $isSeller = ($context['seller_user']?->id === $user->id || $laporan->assigned_to === $user->id);
        $isCustomer = ($laporan->user_id === $user->id);

        if (!$isAdmin && !$isSeller && !$isCustomer) {
            $this->answerQuery($callbackId, 'Anda tidak memiliki hak akses atas tiket laporan ini.', true);
            return;
        }

        // Validasi 4 & Eksekusi sesuai jenis aksi
        match ($action) {
            'ambil' => $this->actionAmbil($callbackId, $chatId, $messageId, $laporan, $user, $isAdmin, $isSeller),
            'tetapkan' => $this->actionTetapkan($callbackId, $chatId, $messageId, $laporan, $user, $isAdmin, $context),
            'mintainfo' => $this->actionMintaInfo($callbackId, $chatId, $messageId, $laporan, $user, $isAdmin, $isSeller),
            'ganti' => $this->actionGantiAkun($callbackId, $chatId, $laporan, $user, $isAdmin, $isSeller),
            'status_proses' => $this->actionStatusProses($callbackId, $chatId, $messageId, $laporan, $user, $isAdmin, $isSeller),
            'selesai', 'tutup', 'status_selesai' => $this->actionSelesai($callbackId, $chatId, $messageId, $laporan, $user, $isAdmin, $isSeller),
            'eskalasi' => $this->actionEskalasi($callbackId, $chatId, $messageId, $laporan, $user, $isAdmin, $isSeller),
            default => $this->answerQuery($callbackId, 'Aksi tidak dikenali.', true),
        };
    }

    /**
     * Aksi Ambil Tiket (Seller / Admin)
     */
    protected function actionAmbil(
        ?string $callbackId,
        int|string $chatId,
        ?int $messageId,
        Laporan $laporan,
        User $user,
        bool $isAdmin,
        bool $isSeller
    ): void {
        if (!$isAdmin && !$isSeller) {
            $this->answerQuery($callbackId, 'Hanya seller terkait atau admin yang dapat mengambil tiket ini.', true);
            return;
        }

        if ($laporan->status === 'selesai') {
            $this->answerQuery($callbackId, 'Tiket sudah selesai dan tidak dapat diambil.', true);
            return;
        }

        $sumber = $isAdmin ? 'telegram_admin' : 'telegram_seller';
        $this->laporanService->assignTicket($laporan, $user, $user, $sumber);

        $this->answerQuery($callbackId, 'Tiket berhasil diambil dan status diubah menjadi Proses.');

        if ($messageId) {
            $this->renderUpdatedTicketView($chatId, $messageId, $laporan->fresh(), $isAdmin);
        }
    }

    /**
     * Aksi Tetapkan Tiket ke Seller Pemilik Toko (Admin Only)
     */
    protected function actionTetapkan(
        ?string $callbackId,
        int|string $chatId,
        ?int $messageId,
        Laporan $laporan,
        User $user,
        bool $isAdmin,
        array $context
    ): void {
        if (!$isAdmin) {
            $this->answerQuery($callbackId, 'Hanya admin yang dapat menetapkan tiket ke seller.', true);
            return;
        }

        if ($laporan->status === 'selesai') {
            $this->answerQuery($callbackId, 'Tiket sudah berstatus Selesai.', true);
            return;
        }

        $sellerUser = $context['seller_user'] ?? null;
        if (!$sellerUser) {
            $this->answerQuery($callbackId, 'Seller pemilik toko tidak ditemukan untuk pesanan ini.', true);
            return;
        }

        $this->laporanService->assignTicket($laporan, $user, $sellerUser, 'telegram_admin');

        $this->answerQuery($callbackId, "Tiket berhasil ditetapkan ke {$sellerUser->name}.");

        if ($messageId) {
            $this->renderUpdatedTicketView($chatId, $messageId, $laporan->fresh(), true);
        }
    }

    /**
     * Aksi Minta Informasi Tambahan (Seller / Admin)
     */
    protected function actionMintaInfo(
        ?string $callbackId,
        int|string $chatId,
        ?int $messageId,
        Laporan $laporan,
        User $user,
        bool $isAdmin,
        bool $isSeller
    ): void {
        if (!$isAdmin && !$isSeller) {
            $this->answerQuery($callbackId, 'Hanya seller terkait atau admin yang dapat meminta informasi.', true);
            return;
        }

        if ($laporan->status === 'selesai') {
            $this->answerQuery($callbackId, 'Tiket sudah berstatus Selesai.', true);
            return;
        }

        // Buat sesi balas aktif di tbl_telegram_sessions
        TelegramSession::updateOrCreate(
            ['chat_id' => (int) $chatId],
            [
                'flow' => 'balas',
                'step' => 'input_pesan',
                'payload' => [
                    'laporan_id' => $laporan->id,
                    'action' => 'mintainfo',
                ],
                'expires_at' => now()->addMinutes(15),
            ]
        );

        $this->answerQuery($callbackId, 'Silakan ketik informasi yang Anda butuhkan dari customer.');

        $ticketNum = '#' . str_pad($laporan->id, 5, '0', STR_PAD_LEFT);
        $prompt = "❓ <b>Minta Info Tambahan Tiket {$ticketNum}</b>\n\n" .
                  "Silakan ketik pertanyaan atau informasi yang Anda butuhkan dari customer di chat ini. " .
                  "Pesan Anda akan otomatis dikirimkan ke thread tiket dan dinotifikasikan ke customer.\n\n" .
                  "<i>(Ketik /batal jika ingin membatalkan)</i>";

        $this->client->sendMessage($chatId, $prompt);
    }

    /**
     * Aksi Kirim Akun Pengganti:
     * Dilarang menerima kredensial di chat Telegram. Balas tombol tautan ke dashboard seller.
     */
    protected function actionGantiAkun(
        ?string $callbackId,
        int|string $chatId,
        Laporan $laporan,
        User $user,
        bool $isAdmin,
        bool $isSeller
    ): void {
        if (!$isAdmin && !$isSeller) {
            $this->answerQuery($callbackId, 'Hanya seller terkait atau admin yang dapat mengganti akun.', true);
            return;
        }

        $this->answerQuery($callbackId, 'Buka form inventori dashboard seller untuk memilih akun pengganti secara aman.');

        $gantiUrl = url('/seller/dashboard?action=ganti_akun&laporan_id=' . $laporan->id);
        $ticketNum = '#' . str_pad($laporan->id, 5, '0', STR_PAD_LEFT);

        $text = "🔐 <b>Pemberian Akun Pengganti Tiket {$ticketNum}</b>\n\n" .
                "Demi menjaga kerahasiaan kredensial akun, pemberian akun pengganti dilakukan langsung melalui dashboard website resmi Lapaktifikasi.\n\n" .
                "Silakan klik tombol di bawah untuk memilih stok akun garansi dari dashboard Anda:";

        $markup = [
            'inline_keyboard' => [
                [['text' => '📦 Buka Dashboard Penggantian Akun', 'url' => $gantiUrl]],
            ],
        ];

        $this->client->sendMessage($chatId, $text, ['reply_markup' => $markup]);
    }

    /**
     * Aksi Ubah Status menjadi Proses
     */
    protected function actionStatusProses(
        ?string $callbackId,
        int|string $chatId,
        ?int $messageId,
        Laporan $laporan,
        User $user,
        bool $isAdmin,
        bool $isSeller
    ): void {
        if (!$isAdmin && !$isSeller) {
            $this->answerQuery($callbackId, 'Anda tidak memiliki hak akses untuk mengubah status tiket.', true);
            return;
        }

        if ($laporan->status === 'proses') {
            $this->answerQuery($callbackId, 'Tiket sudah berstatus Proses.', true);
            return;
        }

        if ($laporan->status === 'selesai') {
            $this->answerQuery($callbackId, 'Tiket yang telah selesai tidak dapat diubah ke proses via Telegram.', true);
            return;
        }

        $sumber = $isAdmin ? 'telegram_admin' : 'telegram_seller';
        $this->laporanService->updateStatus($laporan, $user, 'proses', $sumber, 'Status diubah ke proses via tombol aksi Telegram');

        $this->answerQuery($callbackId, 'Status tiket berhasil diubah menjadi Proses.');

        if ($messageId) {
            $this->renderUpdatedTicketView($chatId, $messageId, $laporan->fresh(), $isAdmin);
        }
    }

    /**
     * Aksi Selesai / Tutup Tiket
     */
    protected function actionSelesai(
        ?string $callbackId,
        int|string $chatId,
        ?int $messageId,
        Laporan $laporan,
        User $user,
        bool $isAdmin,
        bool $isSeller
    ): void {
        if (!$isAdmin && !$isSeller) {
            $this->answerQuery($callbackId, 'Hanya seller terkait atau admin yang dapat menyelesaikan tiket ini.', true);
            return;
        }

        if ($laporan->status === 'selesai') {
            $this->answerQuery($callbackId, 'Tiket sudah berstatus Selesai.', true);
            return;
        }

        $sumber = $isAdmin ? 'telegram_admin' : 'telegram_seller';
        $this->laporanService->updateStatus($laporan, $user, 'selesai', $sumber, 'Tiket diselesaikan via tombol aksi Telegram');

        $this->answerQuery($callbackId, 'Tiket berhasil diselesaikan dan ajakan ulasan dikirim ke customer.');

        if ($messageId) {
            $this->renderUpdatedTicketView($chatId, $messageId, $laporan->fresh(), $isAdmin);
        }
    }

    /**
     * Aksi Eskalasi ke Grup Admin (Forum Topic)
     */
    protected function actionEskalasi(
        ?string $callbackId,
        int|string $chatId,
        ?int $messageId,
        Laporan $laporan,
        User $user,
        bool $isAdmin,
        bool $isSeller
    ): void {
        if (!$isAdmin && !$isSeller) {
            $this->answerQuery($callbackId, 'Hanya seller atau admin yang dapat mengeskalasi tiket.', true);
            return;
        }

        if ($laporan->status === 'selesai') {
            $this->answerQuery($callbackId, 'Tiket sudah selesai dan tidak dapat dieskalasi.', true);
            return;
        }

        if (!is_null($laporan->escalated_at)) {
            $this->answerQuery($callbackId, 'Tiket ini sudah pernah dieskalasi ke grup admin.', true);
            return;
        }

        $sumber = $isAdmin ? 'telegram_admin' : 'telegram_seller';
        $success = $this->laporanService->escalateTicket(
            $laporan,
            $user,
            $sumber,
            'Eskalasi manual oleh ' . ($isAdmin ? 'Admin' : 'Seller') . ' via Telegram'
        );

        if ($success) {
            $this->answerQuery($callbackId, 'Tiket berhasil dieskalasi ke Forum Topic grup Admin.');
        } else {
            $this->answerQuery($callbackId, 'Eskalasi tiket sudah pernah dilakukan sebelumnya.', true);
        }

        if ($messageId) {
            $this->renderUpdatedTicketView($chatId, $messageId, $laporan->fresh(), $isAdmin);
        }
    }

    /**
     * Perbarui tampilan pesan notifikasi di Telegram setelah aksi sukses dijalankan.
     */
    protected function renderUpdatedTicketView(int|string $chatId, int $messageId, Laporan $laporan, bool $isAdmin): void
    {
        $num = '#' . str_pad($laporan->id, 5, '0', STR_PAD_LEFT);
        $statusBadge = match ($laporan->status) {
            'proses' => '🔵 Sedang Diproses',
            'selesai' => '🟢 Selesai',
            default => '🟡 Pending',
        };

        $context = app(NotificationRouter::class)->resolveTicketContext($laporan);
        $assignedName = $laporan->assignedTo?->name ?: ($context['seller_user']?->name ?? 'Belum ada');

        $text = "<b>🎫 STATUS TIKET {$num} DIPERBARUI</b>\n\n" .
                "• <b>Nomor Tiket:</b> {$num}\n" .
                "• <b>Status Terkini:</b> {$statusBadge}\n" .
                "• <b>Ditangani oleh:</b> " . TelegramClient::escape($assignedName) . "\n" .
                "• <b>Eskalasi:</b> " . ($laporan->escalated_at ? '⚠️ Dieskalasi ke Admin' : 'Tidak') . "\n" .
                "• <b>Kendala:</b> " . TelegramClient::escape($laporan->judul) . "\n\n" .
                "<i>Terakhir diperbarui: " . now()->format('d/m/Y H:i') . " WIB</i>";

        $markup = $isAdmin
            ? $this->laporanService->buildAdminActionButtons($laporan)
            : $this->laporanService->buildSellerActionButtons($laporan);

        $this->client->editMessageText($chatId, $messageId, $text, [
            'reply_markup' => $markup,
        ]);
    }

    protected function answerQuery(?string $callbackId, string $text, bool $showAlert = false): void
    {
        if ($callbackId) {
            $this->client->answerCallbackQuery($callbackId, [
                'text' => Str::limit($text, 180),
                'show_alert' => $showAlert,
            ]);
        }
    }

    protected function handleUnlinkConfirm(int|string $chatId, int $messageId): void
    {
        $user = User::where('telegram_chat_id', (int) $chatId)->first();

        if ($user) {
            $this->linkService->unlink($user);
            $text = "<b>✅ Tautan Berhasil Diputuskan</b>\n\nAkun Telegram Anda telah diputuskan dari akun Lapaktifikasi. Anda tidak akan lagi menerima notifikasi pesan dan status tiket via bot ini.\n\nKetik /start kapan saja untuk menghubungkan kembali.";
        } else {
            $text = "Akun Telegram Anda saat ini tidak tertaut dengan akun Lapaktifikasi mana pun.";
        }

        $this->client->editMessageText($chatId, $messageId, $text);
    }

    protected function handleUnlinkCancel(int|string $chatId, int $messageId): void
    {
        $text = "Permintaan pemutusan tautan dibatalkan. Akun Lapaktifikasi Anda tetap terhubung.";
        $this->client->editMessageText($chatId, $messageId, $text);
    }

    protected function handleReplyTicketButton(int|string $chatId, int $messageId, int $ticketId): void
    {
        $user = User::where('telegram_chat_id', (int) $chatId)->first();
        if (!$user) {
            $this->client->sendMessage($chatId, "Akun Telegram Anda belum ditautkan dengan akun Lapaktifikasi.");
            return;
        }

        $laporan = Laporan::find($ticketId);
        if (!$laporan) {
            $this->client->sendMessage($chatId, "❌ Tiket laporan tidak ditemukan.");
            return;
        }

        TelegramSession::updateOrCreate(
            ['chat_id' => (int) $chatId],
            [
                'flow' => 'balas',
                'step' => 'input_pesan',
                'payload' => ['laporan_id' => $laporan->id],
                'expires_at' => now()->addMinutes(15),
            ]
        );

        $ticketNumber = '#' . str_pad($laporan->id, 5, '0', STR_PAD_LEFT);
        $prompt = "💬 <b>Balas Tiket {$ticketNumber}</b>\n\n" .
                  "• <b>Kendala:</b> " . TelegramClient::escape($laporan->judul) . "\n\n" .
                  "Silakan ketik atau kirim pesan/foto balasan Anda langsung pada chat ini.\n\n" .
                  "<i>(Ketik /batal jika ingin membatalkan)</i>";

        $this->client->sendMessage($chatId, $prompt);
    }

    protected function handleLaporSelectOrder(int|string $chatId, int $messageId, string $orderId): void
    {
        $user = User::where('telegram_chat_id', (int) $chatId)->first();
        if (!$user) {
            return;
        }

        $session = TelegramSession::where('chat_id', (int) $chatId)
            ->where('flow', 'lapor')
            ->where('expires_at', '>', now())
            ->first();

        if (!$session) {
            $this->client->sendMessage($chatId, "Sesi laporan telah kedaluwarsa. Silakan ketik /lapor untuk memulai kembali.");
            return;
        }

        $customerIds = CustomerModel::where('user_id', $user->id)->pluck('id')->toArray();
        $customerIds[] = $user->id;

        $order = Pembelian::with(['varianLayanan.tipeLayanan.produk.toko'])
            ->where('order_id', $orderId)
            ->whereIn('id_customer', $customerIds)
            ->where('status', PembelianStatus::SUCCESS)
            ->first();

        if (!$order) {
            $this->client->sendMessage($chatId, "❌ Pesanan tidak valid atau bukan milik akun Anda.");
            return;
        }

        $durasi = $order->varianLayanan?->durasi_hari;
        if (!is_null($durasi) && (int)$durasi > 0 && $order->created_at->addDays((int)$durasi)->isPast()) {
            $this->client->sendMessage($chatId, "❌ Masa garansi untuk pesanan ini telah berakhir.");
            return;
        }

        $prodName = $order->varianLayanan?->tipeLayanan?->produk?->nama_produk ?: 'Produk Digital';

        $payload = $session->payload ?? [];
        $payload['order_id'] = $order->order_id;
        $payload['produk_nama'] = $prodName;

        $session->update([
            'step' => 'select_category',
            'payload' => $payload,
            'expires_at' => now()->addMinutes(15),
        ]);

        $keyboard = [];
        foreach (self::CATEGORIES as $idx => $label) {
            $keyboard[] = [['text' => $label, 'callback_data' => 'lapor_cat_' . $idx]];
        }
        $keyboard[] = [['text' => '❌ Batal', 'callback_data' => 'lapor_cancel']];

        $prompt = "📂 <b>Pilih Kategori Kendala:</b>\n\n" .
                  "• <b>Order:</b> <code>{$order->order_id}</code>\n" .
                  "• <b>Produk:</b> " . TelegramClient::escape($prodName) . "\n\n" .
                  "Silakan pilih kategori kendala yang dialami:";

        $this->client->editMessageText($chatId, $messageId, $prompt, [
            'reply_markup' => ['inline_keyboard' => $keyboard],
        ]);
    }

    protected function handleLaporSelectCategory(int|string $chatId, int $messageId, int $catIdx): void
    {
        $session = TelegramSession::where('chat_id', (int) $chatId)
            ->where('flow', 'lapor')
            ->where('expires_at', '>', now())
            ->first();

        if (!$session) {
            $this->client->sendMessage($chatId, "Sesi laporan telah kedaluwarsa. Silakan ketik /lapor untuk memulai kembali.");
            return;
        }

        $categoryName = self::CATEGORIES[$catIdx] ?? 'Masalah Lainnya';

        $payload = $session->payload ?? [];
        $payload['kategori'] = $categoryName;

        $session->update([
            'step' => 'input_kronologi',
            'payload' => $payload,
            'expires_at' => now()->addMinutes(15),
        ]);

        $prompt = "✍️ <b>Tuliskan Kronologi Kendala:</b>\n\n" .
                  "• <b>Order:</b> <code>" . ($payload['order_id'] ?? '-') . "</code>\n" .
                  "• <b>Kategori:</b> {$categoryName}\n\n" .
                  "Silakan ketik penjelasan detail mengenai masalah yang Anda alami langsung di chat ini (contoh: sejak kapan akun bermasalah, pesan error, dll).\n\n" .
                  "<i>(Ketik /batal jika ingin membatalkan)</i>";

        $this->client->editMessageText($chatId, $messageId, $prompt);
    }

    protected function handleLaporSkipPhoto(int|string $chatId, int $messageId): void
    {
        $session = TelegramSession::where('chat_id', (int) $chatId)
            ->where('flow', 'lapor')
            ->where('expires_at', '>', now())
            ->first();

        if (!$session) {
            $this->client->sendMessage($chatId, "Sesi laporan telah kedaluwarsa. Silakan ketik /lapor untuk memulai kembali.");
            return;
        }

        $payload = $session->payload ?? [];
        $payload['gambar'] = null;

        $session->update([
            'step' => 'confirm',
            'payload' => $payload,
            'expires_at' => now()->addMinutes(15),
        ]);

        $this->renderConfirmation($chatId, $messageId, $payload);
    }

    public function renderConfirmation(int|string $chatId, int $messageId, array $payload): void
    {
        $prompt = "📋 <b>Ringkasan Laporan Tiket:</b>\n\n" .
                  "• <b>Order ID:</b> <code>" . ($payload['order_id'] ?? '-') . "</code>\n" .
                  "• <b>Produk:</b> " . TelegramClient::escape($payload['produk_nama'] ?? '-') . "\n" .
                  "• <b>Kategori:</b> " . TelegramClient::escape($payload['kategori'] ?? '-') . "\n" .
                  "• <b>Kronologi:</b> " . TelegramClient::escape(Str::limit($payload['kronologi'] ?? '-', 300)) . "\n" .
                  "• <b>Foto Bukti:</b> " . (!empty($payload['gambar']) ? '✅ Terlampir' : '❌ Tidak Ada (Dilewati)') . "\n\n" .
                  "Kirimkan laporan ini sekarang?";

        $keyboard = [
            [
                ['text' => '🚀 Kirim Laporan', 'callback_data' => 'lapor_submit'],
                ['text' => '❌ Batalkan', 'callback_data' => 'lapor_cancel'],
            ],
        ];

        $this->client->sendMessage($chatId, $prompt, [
            'reply_markup' => ['inline_keyboard' => $keyboard],
        ]);
    }

    protected function handleLaporSubmit(int|string $chatId, int $messageId): void
    {
        $user = User::where('telegram_chat_id', (int) $chatId)->first();
        if (!$user) {
            return;
        }

        $session = TelegramSession::where('chat_id', (int) $chatId)
            ->where('flow', 'lapor')
            ->where('expires_at', '>', now())
            ->first();

        if (!$session) {
            $this->client->sendMessage($chatId, "Sesi laporan telah kedaluwarsa. Silakan ketik /lapor untuk memulai kembali.");
            return;
        }

        $payload = $session->payload ?? [];
        if (empty($payload['order_id']) || empty($payload['kategori']) || empty($payload['kronologi'])) {
            $this->client->sendMessage($chatId, "❌ Data laporan tidak lengkap. Silakan ketik /lapor untuk mengulang.");
            $session->delete();
            return;
        }

        try {
            $judul = "[{$payload['kategori']}] Order #{$payload['order_id']}";
            $laporan = $this->laporanService->createTicket($user, [
                'judul' => $judul,
                'deskripsi' => $payload['kronologi'],
                'gambar' => $payload['gambar'] ?? null,
                'sumber' => 'telegram',
                'telegram_message_id' => $messageId,
            ]);

            $session->delete();

            $num = '#' . str_pad($laporan->id, 5, '0', STR_PAD_LEFT);
            $slaHours = SettingWebsite::getCached()?->sla_respon_jam ?? 3;
            $ticketUrl = url('/premium/laporan/' . $laporan->id);

            $text = "🎉 <b>Laporan Tiket Berhasil Dibuat!</b>\n\n" .
                    "• <b>Nomor Tiket:</b> <b>{$num}</b>\n" .
                    "• <b>Status:</b> 🟡 Pending (Menunggu Penanganan)\n" .
                    "• <b>Target SLA Respon:</b> {$slaHours} Jam\n\n" .
                    "Laporan kendala Anda telah masuk ke sistem kami. Tim support dan seller terkait telah menerima notifikasi.\n\n" .
                    "Anda akan menerima kabar tindak lanjut secara langsung di Telegram ini.";

            $markup = [
                'inline_keyboard' => [
                    [['text' => '🌐 Buka di Website', 'url' => $ticketUrl]],
                ],
            ];

            $this->client->sendMessage($chatId, $text, [
                'reply_markup' => $markup,
            ]);
        } catch (\Throwable $e) {
            $this->client->sendMessage($chatId, "❌ Gagal membuat laporan: " . TelegramClient::escape($e->getMessage()));
        }
    }

    protected function handleLaporCancel(int|string $chatId, int $messageId): void
    {
        TelegramSession::where('chat_id', (int) $chatId)->delete();
        $this->client->sendMessage($chatId, "❌ Proses pembuatan laporan telah dibatalkan.");
    }
}
