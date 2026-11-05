<?php

namespace App\Services\Telegram;

use App\Enums\PembelianStatus;
use App\Models\CustomerModel;
use App\Models\Laporan;
use App\Models\Pembelian;
use App\Models\TelegramSession;
use App\Models\User;
use App\Services\Telegram\Exceptions\TelegramLinkException;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class CommandRouter
{
    protected TelegramClient $client;
    protected TelegramLinkService $linkService;

    public function __construct(TelegramClient $client, ?TelegramLinkService $linkService = null)
    {
        $this->client = $client;
        $this->linkService = $linkService ?? app(TelegramLinkService::class);
    }

    public function handle(array $message): void
    {
        $text = trim($message['text'] ?? '');
        $chatId = $message['chat']['id'] ?? null;

        if (!$chatId || $text === '') {
            return;
        }

        // Split command and argument (e.g., "/start" or "/start auth_token_123")
        $parts = preg_split('/\s+/', $text, 2);
        $command = strtolower($parts[0] ?? '');
        $args = trim($parts[1] ?? '');

        // Remove bot username suffix if command was sent in group (e.g., /start@mybot)
        if (str_contains($command, '@')) {
            $command = explode('@', $command)[0];
        }

        match ($command) {
            '/start' => $this->handleStart($chatId, $args, $message),
            '/putus' => $this->handlePutus($chatId, $message),
            '/lapor' => $this->handleLapor($chatId, $message),
            '/tiket' => $this->handleTiket($chatId, $message),
            '/batal' => $this->handleBatal($chatId, $message),
            '/bantuan', '/help' => $this->handleBantuan($chatId, $message),
            default => $this->handleUnknown($chatId, $command, $message),
        };
    }

    protected function handleStart(int|string $chatId, string $args, array $message): void
    {
        if ($args === '') {
            $user = null;
            try {
                $user = User::where('telegram_chat_id', (int) $chatId)->first();
            } catch (\Throwable $e) {
                $user = null;
            }

            if ($user && method_exists($user, 'isSeller') && $user->isSeller()) {
                $reply = "Halo <b>" . TelegramClient::escape($user->name ?: 'Seller') . "</b>! Akun Seller Lapaktifikasi Anda sudah terhubung dengan bot ini.\n\n" .
                         "Untuk mengelola pengaturan integrasi bot, silakan buka menu <b>Profil Toko</b> (<code>/seller/profil</code>) di dashboard seller Anda.\n\n" .
                         "Ketik /bantuan untuk melihat panduan atau /putus untuk memutuskan tautan akun.";
            } elseif ($user && method_exists($user, 'isAdmin') && $user->isAdmin()) {
                $reply = "Halo <b>" . TelegramClient::escape($user->name ?: 'Admin') . "</b>! Akun Admin Lapaktifikasi Anda sudah terhubung dengan bot ini.\n\n" .
                         "Pengaturan integrasi dapat diakses melalui menu <b>Setting Website</b> (<code>/setting_website</code>) di dashboard admin.\n\n" .
                         "Ketik /bantuan untuk melihat panduan atau /putus untuk memutuskan tautan akun.";
            } elseif ($user) {
                $reply = "Halo <b>" . TelegramClient::escape($user->name ?: 'Pelanggan') . "</b>! Akun Lapaktifikasi Anda sudah terhubung.\n\n" .
                         "Ketik /tiket untuk melihat tiket garansi aktif, /lapor untuk membuat laporan kendala, atau /putus untuk memutuskan tautan akun.";
            } else {
                $reply = "Halo! Untuk menautkan akun Lapaktifikasi Anda dengan bot Telegram ini:\n\n" .
                         "• <b>Seller:</b> Buka dashboard lalu masuk ke menu <b>Profil Toko</b> (<code>/seller/profil</code>), lalu klik tombol <b>Buat Tautan Telegram</b>.\n" .
                         "• <b>Customer:</b> Buka menu <b>Profil Saya</b> di website, lalu klik tombol <b>Buat Tautan Telegram</b>.\n" .
                         "• <b>Admin:</b> Buka menu <b>Setting Website</b> (<code>/setting_website</code>).\n\n" .
                         "Setelah tautan dibuat, klik link yang diberikan atau ketik <code>/start &lt;token&gt;</code> di chat ini.";
            }
            $this->client->sendMessage($chatId, $reply);
            return;
        }

        $chatType = $message['chat']['type'] ?? 'private';
        $username = $message['from']['username'] ?? null;

        try {
            $user = $this->linkService->link($args, (int) $chatId, $username, $chatType);
            $nama = TelegramClient::escape($user->name ?: 'Pengguna');
            $email = TelegramClient::escape($user->email);

            $reply = "<b>✅ Akun Berhasil Ditautkan!</b>\n\n" .
                     "Halo <b>{$nama}</b>, akun Lapaktifikasi Anda (<code>{$email}</code>) telah berhasil terhubung dengan bot Telegram ini.\n\n" .
                     "Mulai sekarang Anda dapat membuat tiket laporan kendala via /lapor, memantau status tiket via /tiket, dan menerima notifikasi serta membalas tiket secara real-time langsung di chat ini.\n\n" .
                     "Ketik /bantuan untuk melihat panduan atau /putus jika sewaktu-waktu ingin memutuskan tautan.";

            $this->client->sendMessage($chatId, $reply);
        } catch (TelegramLinkException $e) {
            $reply = "<b>❌ Gagal Menautkan Akun</b>\n\n" . TelegramClient::escape($e->getMessage());
            $this->client->sendMessage($chatId, $reply);
        } catch (\Throwable $e) {
            $reply = "<b>❌ Terjadi Kesalahan</b>\n\nGagal memproses penautan akun Anda. Silakan coba buat tautan baru melalui website Lapaktifikasi.";
            $this->client->sendMessage($chatId, $reply);
        }
    }

    protected function handlePutus(int|string $chatId, array $message): void
    {
        $user = User::where('telegram_chat_id', (int) $chatId)->first();

        if (!$user) {
            $reply = "Akun Telegram Anda saat ini belum tertaut dengan akun Lapaktifikasi mana pun. Tidak ada tautan yang dapat diputuskan.";
            $this->client->sendMessage($chatId, $reply);
            return;
        }

        $email = TelegramClient::escape($user->email);
        $text = "Apakah Anda yakin ingin memutuskan tautan akun Lapaktifikasi (<code>{$email}</code>) dari Telegram ini?";

        $replyMarkup = [
            'inline_keyboard' => [
                [
                    ['text' => '✅ Ya, Putuskan', 'callback_data' => 'unlink_confirm'],
                    ['text' => '❌ Batal', 'callback_data' => 'unlink_cancel'],
                ],
            ],
        ];

        $this->client->sendMessage($chatId, $text, [
            'reply_markup' => $replyMarkup,
        ]);
    }

    /**
     * Wizard /lapor: Memulai alur pembuatan laporan kendala untuk pesanan bergaransi.
     */
    protected function handleLapor(int|string $chatId, array $message): void
    {
        $user = User::where('telegram_chat_id', (int) $chatId)->first();
        if (!$user) {
            $reply = "Akun Telegram Anda belum ditautkan dengan akun Lapaktifikasi. Silakan login ke website Lapaktifikasi dan tautkan Telegram Anda terlebih dahulu.";
            $this->client->sendMessage($chatId, $reply);
            return;
        }

        // Cek rate limiting: Maksimal 3 tiket baru per jam per customer
        $limiterKey = 'laporan_create:' . $user->id;
        if (RateLimiter::tooManyAttempts($limiterKey, 3)) {
            $seconds = RateLimiter::availableIn($limiterKey);
            $minutes = ceil($seconds / 60);
            $this->client->sendMessage(
                $chatId,
                "⚠️ Batas pembuatan tiket tercapai (maksimal 3 tiket per jam). Silakan coba lagi dalam {$minutes} menit."
            );
            return;
        }

        // Cari pesanan SUCCESS milik customer yang masih dalam masa garansi
        $customerIds = CustomerModel::where('user_id', $user->id)->pluck('id')->toArray();
        $customerIds[] = $user->id;

        $orders = Pembelian::with(['varianLayanan.tipeLayanan.produk.toko'])
            ->whereIn('id_customer', $customerIds)
            ->where('status', PembelianStatus::SUCCESS)
            ->latest()
            ->get()
            ->filter(function ($order) {
                $durasi = $order->varianLayanan?->durasi_hari;
                if (is_null($durasi) || (int)$durasi === 0) {
                    return true; // Garansi seumur hidup / tanpa batas
                }
                return $order->created_at->addDays((int)$durasi)->isFuture();
            })
            ->take(8);

        if ($orders->isEmpty()) {
            $reply = "Maaf, Anda tidak memiliki pesanan berstatus SUCCESS yang masih dalam masa garansi untuk dilaporkan.\n\n" .
                     "Jika Anda membutuhkan bantuan umum atau memiliki pertanyaan lain, silakan buka menu tiket laporan di website kami.";
            $this->client->sendMessage($chatId, $reply);
            return;
        }

        // Simpan / perbarui sesi wizard
        TelegramSession::updateOrCreate(
            ['chat_id' => (int) $chatId],
            [
                'flow' => 'lapor',
                'step' => 'select_order',
                'payload' => [],
                'expires_at' => now()->addMinutes(15),
            ]
        );

        $keyboard = [];
        foreach ($orders as $o) {
            $prodName = $o->varianLayanan?->tipeLayanan?->produk?->nama_produk ?: 'Produk Digital';
            $btnText = Str::limit($prodName, 22) . ' #' . substr($o->order_id, 0, 8);
            $keyboard[] = [['text' => $btnText, 'callback_data' => 'lapor_order_' . $o->order_id]];
        }
        $keyboard[] = [['text' => '❌ Batal', 'callback_data' => 'lapor_cancel']];

        $prompt = "📋 <b>Pilih Pesanan yang Berkendala:</b>\n\n" .
                  "Ditemukan <b>" . $orders->count() . "</b> pesanan aktif dalam masa garansi pada akun Anda.\n" .
                  "Silakan pilih pesanan yang ingin Anda laporkan di bawah ini:\n\n" .
                  "<i>(Ketik /batal kapan saja untuk membatalkan proses laporan)</i>";

        $this->client->sendMessage($chatId, $prompt, [
            'reply_markup' => ['inline_keyboard' => $keyboard],
        ]);
    }

    /**
     * Perintah /tiket: Menampilkan ringkasan tiket aktif dan tiket selesai terakhir.
     */
    protected function handleTiket(int|string $chatId, array $message): void
    {
        $user = User::where('telegram_chat_id', (int) $chatId)->first();
        if (!$user) {
            $reply = "Akun Telegram Anda belum ditautkan dengan akun Lapaktifikasi. Silakan login ke website Lapaktifikasi dan tautkan Telegram Anda terlebih dahulu.";
            $this->client->sendMessage($chatId, $reply);
            return;
        }

        $activeTickets = Laporan::where('user_id', $user->id)
            ->whereIn('status', ['pending', 'proses'])
            ->orderBy('created_at', 'desc')
            ->get();

        $resolvedTickets = Laporan::where('user_id', $user->id)
            ->where('status', 'selesai')
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        $text = "<b>📋 Daftar Tiket Laporan Anda</b>\n\n";

        $text .= "<b>Tiket Aktif (" . $activeTickets->count() . "):</b>\n";
        if ($activeTickets->isEmpty()) {
            $text .= "<i>Tidak ada tiket aktif saat ini.</i>\n";
        } else {
            foreach ($activeTickets as $t) {
                $num = '#' . str_pad($t->id, 5, '0', STR_PAD_LEFT);
                $statusBadge = $t->status === 'proses' ? '🔵 Sedang Diproses' : '🟡 Pending';
                $text .= "• <b>{$num}</b>: " . TelegramClient::escape(Str::limit($t->judul, 35)) . " [{$statusBadge}]\n";
            }
        }

        $text .= "\n<b>5 Tiket Terakhir Selesai:</b>\n";
        if ($resolvedTickets->isEmpty()) {
            $text .= "<i>Belum ada tiket yang telah selesai.</i>\n";
        } else {
            foreach ($resolvedTickets as $t) {
                $num = '#' . str_pad($t->id, 5, '0', STR_PAD_LEFT);
                $date = $t->created_at->format('d/m/Y');
                $text .= "• <b>{$num}</b>: " . TelegramClient::escape(Str::limit($t->judul, 35)) . " (🟢 Selesai - {$date})\n";
            }
        }

        $markup = [
            'inline_keyboard' => [
                [['text' => '🌐 Buka di Website', 'url' => url('/premium/laporan')]],
            ],
        ];

        $this->client->sendMessage($chatId, $text, [
            'reply_markup' => $markup,
        ]);
    }

    /**
     * Perintah /batal: Membatalkan sesi wizard laporan atau sesi balas aktif.
     */
    protected function handleBatal(int|string $chatId, array $message): void
    {
        $deleted = TelegramSession::where('chat_id', (int) $chatId)->delete();

        if ($deleted) {
            $this->client->sendMessage($chatId, "❌ Sesi laporan atau balasan Anda telah berhasil dibatalkan.");
        } else {
            $this->client->sendMessage($chatId, "Tidak ada sesi laporan atau balasan aktif yang sedang berjalan.");
        }
    }

    protected function handleBantuan(int|string $chatId, array $message): void
    {
        $reply = "<b>Daftar Perintah Bot Lapaktifikasi:</b>\n\n" .
                 "/lapor - Buat tiket laporan garansi untuk pesanan Anda\n" .
                 "/tiket - Cek status tiket aktif dan riwayat tiket laporan Anda\n" .
                 "/batal - Membatalkan sesi pengisian laporan atau balasan tiket\n" .
                 "/putus - Memutuskan tautan akun Telegram dari Lapaktifikasi\n" .
                 "/start - Menautkan akun atau panduan bot\n" .
                 "/bantuan - Menampilkan daftar perintah yang tersedia";

        $this->client->sendMessage($chatId, $reply);
    }

    protected function handleUnknown(int|string $chatId, string $command, array $message): void
    {
        $chatType = $message['chat']['type'] ?? 'private';
        if ($chatType === 'private') {
            $reply = "Perintah <code>" . TelegramClient::escape($command) . "</code> tidak dikenali. Ketik /bantuan untuk melihat daftar perintah yang tersedia.";
            $this->client->sendMessage($chatId, $reply);
        }
    }
}
