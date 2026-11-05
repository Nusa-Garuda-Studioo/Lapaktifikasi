<?php

namespace App\Services\Telegram;

use App\Jobs\SendTelegramNotification;
use App\Models\BeliProdukModel;
use App\Models\Laporan;
use App\Models\LaporanPesan;
use App\Models\NotifikasiLog;
use App\Models\Pembelian;
use App\Models\Toko;
use App\Models\User;
use App\Services\FonnteService;
use Illuminate\Support\Facades\Log;

class NotificationRouter
{
    protected FonnteService $fonnte;

    public function __construct(FonnteService $fonnte)
    {
        $this->fonnte = $fonnte;
    }

    /**
     * Notifikasi saat tiket laporan baru dibuat.
     */
    public function notifyTicketCreated(Laporan $laporan): void
    {
        try {
            $laporan->loadMissing('user.customer');
            $customer = $laporan->user;

            $context = $this->resolveTicketContext($laporan);
            $ticketNumber = '#' . str_pad($laporan->id, 5, '0', STR_PAD_LEFT);
            $ticketUrl = url('/premium/laporan');
            $adminUrl = url('/premium/laporan-admin');
            $sellerUrl = url('/seller/dashboard');

            // 1. Notifikasi ke Customer
            if ($customer) {
                $custHtml = "<b>🎫 Tiket Laporan Dibuat</b>\n\n" .
                            "Halo <b>" . TelegramClient::escape($customer->name ?: 'Customer') . "</b>, laporan kendala Anda telah kami terima:\n\n" .
                            "• <b>Nomor Tiket:</b> {$ticketNumber}\n" .
                            "• <b>Status:</b> 🟡 Pending (Menunggu Penanganan)\n" .
                            "• <b>Kendala:</b> " . TelegramClient::escape($laporan->judul) . "\n";

                if (!empty($context['toko_nama'])) {
                    $custHtml .= "• <b>Toko:</b> " . TelegramClient::escape($context['toko_nama']) . "\n";
                }
                if (!empty($context['produk_nama'])) {
                    $custHtml .= "• <b>Produk:</b> " . TelegramClient::escape($context['produk_nama']) . "\n";
                }

                $custHtml .= "\nTim kami akan segera memproses laporan Anda.";

                $custWa = "🎫 *Tiket Laporan Dibuat*\n\n" .
                          "Halo " . ($customer->name ?: 'Customer') . ", laporan kendala Anda telah diterima:\n" .
                          "• Nomor Tiket: {$ticketNumber}\n" .
                          "• Status: Pending\n" .
                          "• Kendala: {$laporan->judul}\n\n" .
                          "Lihat status tiket: {$ticketUrl}";

                $custMarkup = [
                    'inline_keyboard' => [
                        [
                            ['text' => '💬 Balas', 'callback_data' => 'reply_ticket_' . $laporan->id],
                            ['text' => '🌐 Buka di Website', 'url' => $ticketUrl],
                        ],
                    ],
                ];

                $this->sendToCustomer($customer, 'laporan_created', $custHtml, $custWa, $laporan, $custMarkup);
            }

            // 2. Notifikasi ke Seller Toko Terkait (jika terdeteksi)
            $sellerUser = $context['seller_user'] ?? null;
            if ($sellerUser) {
                $sellerHtml = "<b>⚠️ Tiket Kendala Baru untuk Toko Anda</b>\n\n" .
                              "Halo <b>" . TelegramClient::escape($context['toko_nama'] ?: 'Seller') . "</b>, terdapat laporan kendala baru dari customer:\n\n" .
                              "• <b>Nomor Tiket:</b> {$ticketNumber}\n" .
                              "• <b>Status:</b> 🟡 Pending\n" .
                              "• <b>Kendala:</b> " . TelegramClient::escape($laporan->judul) . "\n" .
                              "• <b>Ringkasan:</b> " . TelegramClient::escape(\Illuminate\Support\Str::limit($laporan->deskripsi, 150)) . "\n";

                if (!empty($context['order_id'])) {
                    $sellerHtml .= "• <b>Order ID:</b> <code>" . TelegramClient::escape($context['order_id']) . "</code>\n";
                }
                if (!empty($context['produk_nama'])) {
                    $sellerHtml .= "• <b>Produk:</b> " . TelegramClient::escape($context['produk_nama']) . "\n";
                }
                if (!empty($context['kategori'])) {
                    $sellerHtml .= "• <b>Kategori:</b> " . TelegramClient::escape($context['kategori']) . "\n";
                }
                if (!empty($laporan->gambar)) {
                    $sellerHtml .= "• <b>Bukti Gambar:</b> Terlampir di website\n";
                }

                $sellerHtml .= "\nSilakan periksa detailnya di dashboard seller.";

                $sellerWa = "⚠️ *Tiket Kendala Baru*\n\n" .
                            "Halo " . ($context['toko_nama'] ?: 'Seller') . ", terdapat laporan kendala baru:\n" .
                            "• Nomor Tiket: {$ticketNumber}\n" .
                            "• Kendala: {$laporan->judul}\n" .
                            "• Order: " . ($context['order_id'] ?? '-') . "\n\n" .
                            "Periksa di dashboard: {$sellerUrl}";

                $sellerMarkup = app(\App\Services\Laporan\LaporanService::class)->buildSellerActionButtons($laporan);

                $this->sendToSeller($sellerUser, 'laporan_created_seller', $sellerHtml, $sellerWa, $laporan, $sellerMarkup);
            }

            // 3. Notifikasi ke Grup Admin
            $adminHtml = "<b>🚨 LAPORAN TIKET BARU MASUK</b>\n\n" .
                         "• <b>Nomor Tiket:</b> {$ticketNumber}\n" .
                         "• <b>Status:</b> 🟡 Pending\n" .
                         "• <b>Judul:</b> " . TelegramClient::escape($laporan->judul) . "\n" .
                         "• <b>Deskripsi:</b> " . TelegramClient::escape(\Illuminate\Support\Str::limit($laporan->deskripsi, 180)) . "\n";

            if (!empty($context['toko_nama'])) {
                $adminHtml .= "• <b>Toko:</b> " . TelegramClient::escape($context['toko_nama']) . "\n";
            }
            if (!empty($context['produk_nama'])) {
                $adminHtml .= "• <b>Produk:</b> " . TelegramClient::escape($context['produk_nama']) . "\n";
            }

            $adminHtml .= "\nMohon tim support segera meninjau di dashboard.";

            $adminMarkup = app(\App\Services\Laporan\LaporanService::class)->buildAdminActionButtons($laporan);

            $this->sendToAdmin('laporan_created_admin', $adminHtml, $laporan, $adminMarkup);
        } catch (\Throwable $e) {
            Log::error('NotificationRouter: Kesalahan saat mengirim notifikasi tiket baru: ' . $e->getMessage());
        }
    }

    /**
     * Notifikasi saat status tiket diperbarui (pending -> proses).
     */
    public function notifyTicketStatusUpdated(Laporan $laporan, string $oldStatus, string $newStatus): void
    {
        try {
            $laporan->loadMissing('user.customer');
            $customer = $laporan->user;

            $context = $this->resolveTicketContext($laporan);
            $ticketNumber = '#' . str_pad($laporan->id, 5, '0', STR_PAD_LEFT);
            $ticketUrl = url('/premium/laporan');
            $sellerUrl = url('/seller/dashboard');

            // Format status label
            $statusLabel = match ($newStatus) {
                'proses' => '🔵 Sedang Diproses',
                'selesai' => '🟢 Selesai',
                default => '🟡 Pending',
            };

            // 1. Notifikasi ke Customer
            if ($customer) {
                $custHtml = "<b>🔄 Update Status Tiket {$ticketNumber}</b>\n\n" .
                            "Status laporan kendala Anda telah diperbarui:\n\n" .
                            "• <b>Status Sebelumnya:</b> " . ucfirst($oldStatus) . "\n" .
                            "• <b>Status Terkini:</b> {$statusLabel}\n" .
                            "• <b>Kendala:</b> " . TelegramClient::escape($laporan->judul) . "\n\n" .
                            "Silakan cek detail perkembangannya di website.";

                $custWa = "🔄 *Update Status Tiket {$ticketNumber}*\n\n" .
                          "Status laporan Anda diperbarui:\n" .
                          "• Status: " . ucfirst($newStatus) . "\n" .
                          "• Kendala: {$laporan->judul}\n\n" .
                          "Lihat status tiket: {$ticketUrl}";

                $custMarkup = [
                    'inline_keyboard' => [
                        [
                            ['text' => '💬 Balas', 'callback_data' => 'reply_ticket_' . $laporan->id],
                            ['text' => '🌐 Buka di Website', 'url' => $ticketUrl],
                        ],
                    ],
                ];

                $this->sendToCustomer($customer, 'laporan_status_updated', $custHtml, $custWa, $laporan, $custMarkup);
            }

            // 2. Notifikasi ke Seller (bila diubah oleh admin)
            $sellerUser = $context['seller_user'] ?? null;
            if ($sellerUser) {
                $sellerHtml = "<b>🔄 Update Status Tiket {$ticketNumber}</b>\n\n" .
                              "Status tiket kendala toko Anda telah diperbarui oleh Admin:\n\n" .
                              "• <b>Status Sebelumnya:</b> " . ucfirst($oldStatus) . "\n" .
                              "• <b>Status Terkini:</b> {$statusLabel}\n" .
                              "• <b>Kendala:</b> " . TelegramClient::escape($laporan->judul) . "\n\n" .
                              "Silakan koordinasikan lebih lanjut melalui dashboard seller.";

                $sellerWa = "🔄 *Update Status Tiket Toko {$ticketNumber}*\n\n" .
                            "Status tiket diperbarui menjadi: " . ucfirst($newStatus) . "\n" .
                            "• Kendala: {$laporan->judul}\n\n" .
                            "Cek dashboard: {$sellerUrl}";

                $sellerMarkup = [
                    'inline_keyboard' => [
                        [
                            ['text' => '💬 Balas', 'callback_data' => 'reply_ticket_' . $laporan->id],
                            ['text' => '🌐 Buka di Website', 'url' => $sellerUrl],
                        ],
                    ],
                ];

                $this->sendToSeller($sellerUser, 'laporan_status_updated_seller', $sellerHtml, $sellerWa, $laporan, $sellerMarkup);
            }
        } catch (\Throwable $e) {
            Log::error('NotificationRouter: Kesalahan saat mengirim notifikasi update status: ' . $e->getMessage());
        }
    }

    /**
     * Notifikasi saat tiket dinyatakan selesai (ajakan memberi ulasan).
     */
    public function notifyTicketCompleted(Laporan $laporan): void
    {
        try {
            $laporan->loadMissing('user.customer');
            $customer = $laporan->user;

            $context = $this->resolveTicketContext($laporan);
            $ticketNumber = '#' . str_pad($laporan->id, 5, '0', STR_PAD_LEFT);
            $ticketUrl = url('/premium/laporan');
            $sellerUrl = url('/seller/dashboard');

            // 1. Notifikasi ke Customer
            if ($customer) {
                $custHtml = "<b>✅ Tiket Laporan Selesai ({$ticketNumber})</b>\n\n" .
                            "Kabar baik! Laporan kendala Anda telah dinyatakan selesai oleh tim support:\n\n" .
                            "• <b>Kendala:</b> " . TelegramClient::escape($laporan->judul) . "\n" .
                            "• <b>Status:</b> 🟢 Selesai\n\n" .
                            "Terima kasih atas kesabaran Anda. Kami sangat menghargai jika Anda bersedia memberikan ulasan atas penanganan ini di website.";

                $custWa = "✅ *Tiket Laporan Selesai ({$ticketNumber})*\n\n" .
                          "Kabar baik! Laporan kendala Anda telah dinyatakan selesai:\n" .
                          "• Kendala: {$laporan->judul}\n" .
                          "• Status: Selesai\n\n" .
                          "Beri ulasan dan lihat detail: {$ticketUrl}";

                $custMarkup = [
                    'inline_keyboard' => [
                        [['text' => '⭐ Beri Ulasan / Buka Tiket', 'url' => $ticketUrl]],
                    ],
                ];

                $this->sendToCustomer($customer, 'laporan_completed', $custHtml, $custWa, $laporan, $custMarkup);
            }

            // 2. Notifikasi ke Seller
            $sellerUser = $context['seller_user'] ?? null;
            if ($sellerUser) {
                $sellerHtml = "<b>✅ Tiket Laporan Selesai ({$ticketNumber})</b>\n\n" .
                              "Tiket kendala pada toko Anda telah diselesaikan:\n\n" .
                              "• <b>Kendala:</b> " . TelegramClient::escape($laporan->judul) . "\n" .
                              "• <b>Status:</b> 🟢 Selesai\n\n" .
                              "Terima kasih telah memberikan layanan terbaik bagi customer.";

                $sellerWa = "✅ *Tiket Laporan Selesai ({$ticketNumber})*\n\n" .
                            "Tiket kendala toko Anda telah berstatus Selesai.\n" .
                            "• Kendala: {$laporan->judul}\n\n" .
                            "Cek dashboard: {$sellerUrl}";

                $sellerMarkup = [
                    'inline_keyboard' => [
                        [
                            ['text' => '💬 Balas', 'callback_data' => 'reply_ticket_' . $laporan->id],
                            ['text' => '🌐 Buka di Website', 'url' => $sellerUrl],
                        ],
                    ],
                ];

                $this->sendToSeller($sellerUser, 'laporan_completed_seller', $sellerHtml, $sellerWa, $laporan, $sellerMarkup);
            }
        } catch (\Throwable $e) {
            Log::error('NotificationRouter: Kesalahan saat mengirim notifikasi tiket selesai: ' . $e->getMessage());
        }
    }

    /**
     * Notifikasi saat ada pesan balasan baru di thread laporan.
     */
    public function notifyNewThreadMessage(LaporanPesan $pesan): void
    {
        try {
            $pesan->loadMissing(['laporan.user.customer', 'sender']);
            $laporan = $pesan->laporan;
            if (!$laporan) {
                return;
            }

            $sender = $pesan->sender;
            $customer = $laporan->user;
            $context = $this->resolveTicketContext($laporan);
            $ticketNumber = '#' . str_pad($laporan->id, 5, '0', STR_PAD_LEFT);
            $ticketUrl = url('/premium/laporan/' . $laporan->id);
            $sellerUrl = url('/premium/laporan/' . $laporan->id);
            $adminUrl = url('/premium/laporan/' . $laporan->id);

            $senderName = $sender?->name ?: ucfirst($pesan->sender_role);
            $senderRoleLabel = match ($pesan->sender_role) {
                'admin' => 'Admin Support',
                'seller' => 'Seller (' . ($context['toko_nama'] ?? 'Penjual') . ')',
                'customer' => 'Customer (' . ($customer?->name ?: 'Pelanggan') . ')',
                default => 'Sistem',
            };

            $bodySnippet = \Illuminate\Support\Str::limit($pesan->body ?: '(Kirim lampiran berkas)', 250);
            $hasAttachment = !empty($pesan->lampiran);

            // 1. Jika pengirim adalah Customer -> Beritahu Seller dan Admin
            if ($pesan->sender_role === 'customer') {
                $sellerUser = $context['seller_user'] ?? null;
                if ($sellerUser && $sellerUser->id !== $sender?->id) {
                    $sellerHtml = "<b>💬 Balasan Baru pada Tiket {$ticketNumber}</b>\n\n" .
                                  "Customer mengirim balasan untuk kendala:\n" .
                                  "• <b>Tiket:</b> {$ticketNumber} - " . TelegramClient::escape($laporan->judul) . "\n" .
                                  "• <b>Pengirim:</b> " . TelegramClient::escape($senderName) . "\n" .
                                  "• <b>Pesan:</b>\n<i>\"" . TelegramClient::escape($bodySnippet) . "\"</i>\n";

                    if ($hasAttachment) {
                        $sellerHtml .= "• <b>Lampiran:</b> 📎 Berkas terlampir\n";
                    }

                    $sellerHtml .= "\nBalas langsung chat ini atau klik tombol di bawah:";

                    $sellerWa = "💬 *Balasan Baru pada Tiket {$ticketNumber}*\n\n" .
                                "Customer membalas tiket {$ticketNumber}:\n" .
                                "\"{$bodySnippet}\"\n\n" .
                                "Buka tiket: {$sellerUrl}";

                    $sellerMarkup = [
                        'inline_keyboard' => [
                            [
                                ['text' => '💬 Balas', 'callback_data' => 'reply_ticket_' . $laporan->id],
                                ['text' => '🌐 Buka di Website', 'url' => $sellerUrl],
                            ],
                        ],
                    ];

                    $this->sendToSeller($sellerUser, 'laporan_pesan_seller', $sellerHtml, $sellerWa, $laporan, $sellerMarkup);
                }

                // Notifikasi ke Admin Group
                $adminHtml = "<b>💬 Balasan Baru pada Tiket {$ticketNumber}</b>\n\n" .
                             "• <b>Pengirim:</b> Customer (" . TelegramClient::escape($senderName) . ")\n" .
                             "• <b>Tiket:</b> {$ticketNumber} - " . TelegramClient::escape($laporan->judul) . "\n" .
                             "• <b>Pesan:</b>\n<i>\"" . TelegramClient::escape($bodySnippet) . "\"</i>\n";

                if ($hasAttachment) {
                    $adminHtml .= "• <b>Lampiran:</b> 📎 Berkas terlampir\n";
                }

                $adminMarkup = [
                    'inline_keyboard' => [
                        [['text' => '🌐 Buka di Dashboard', 'url' => $adminUrl]],
                    ],
                ];

                $this->sendToAdmin('laporan_pesan_admin', $adminHtml, $laporan, $adminMarkup);
            }

            // 2. Jika pengirim adalah Admin atau Seller -> Beritahu Customer
            if ($pesan->sender_role === 'admin' || $pesan->sender_role === 'seller') {
                if ($customer && $customer->id !== $sender?->id) {
                    $custHtml = "<b>💬 Balasan Baru pada Tiket {$ticketNumber}</b>\n\n" .
                                "Ada tanggapan baru dari <b>{$senderRoleLabel}</b>:\n\n" .
                                "• <b>Tiket:</b> {$ticketNumber} - " . TelegramClient::escape($laporan->judul) . "\n" .
                                "• <b>Pesan:</b>\n<i>\"" . TelegramClient::escape($bodySnippet) . "\"</i>\n";

                    if ($hasAttachment) {
                        $custHtml .= "• <b>Lampiran:</b> 📎 Berkas terlampir di website\n";
                    }

                    $custHtml .= "\nAnda dapat membalas langsung chat ini atau klik tombol di bawah:";

                    $custWa = "💬 *Balasan Baru pada Tiket {$ticketNumber}*\n\n" .
                              "Tanggapan dari {$senderRoleLabel}:\n" .
                              "\"{$bodySnippet}\"\n\n" .
                              "Buka dan balas: {$ticketUrl}";

                    $custMarkup = [
                        'inline_keyboard' => [
                            [
                                ['text' => '💬 Balas', 'callback_data' => 'reply_ticket_' . $laporan->id],
                                ['text' => '🌐 Buka di Website', 'url' => $ticketUrl],
                            ],
                        ],
                    ];

                    $this->sendToCustomer($customer, 'laporan_pesan_customer', $custHtml, $custWa, $laporan, $custMarkup);
                }
            }
        } catch (\Throwable $e) {
            Log::error('NotificationRouter: Kesalahan saat mengirim notifikasi pesan thread: ' . $e->getMessage());
        }
    }

    /**
     * Kirim notifikasi ke Customer dengan urutan fallback.
     */
    public function sendToCustomer(
        User $customer,
        string $event,
        string $telegramHtml,
        string $waText,
        ?Laporan $laporan = null,
        array $markup = []
    ): void {
        $preference = $customer->notif_channel_laporan ?: 'auto';
        $customerPhone = $customer->customer->first()?->nomor_telepon;

        $fallbackContext = [
            'phone' => $customerPhone,
            'message' => $waText,
        ];

        // 1. Jika customer memilih 'whatsapp'
        if ($preference === 'whatsapp') {
            $this->logAction($customer->id, $event, 'telegram', 'skipped', 'Customer memilih saluran WhatsApp', $laporan?->id);
            $this->sendWhatsAppDirectly($customer->id, $event, $customerPhone, $waText, $laporan?->id);
            return;
        }

        // 2. Jika customer memilih 'telegram' atau 'auto'
        if ($customer->hasTelegram()) {
            $log = $this->logAction($customer->id, $event, 'telegram', 'pending', null, $laporan?->id);

            SendTelegramNotification::dispatch(
                $customer->telegram_chat_id,
                $telegramHtml,
                $markup,
                $laporan?->id,
                $customer->id,
                $event,
                $log->id,
                $fallbackContext
            );
        } else {
            // Belum menautkan Telegram -> Fallback ke WhatsApp
            $skipReason = $customer->telegram_blocked_at ? 'Telegram diblokir' : 'Telegram belum tertaut';
            $this->logAction($customer->id, $event, 'telegram', 'skipped', $skipReason, $laporan?->id);

            $this->sendWhatsAppDirectly($customer->id, $event, $customerPhone, $waText, $laporan?->id);
        }
    }

    /**
     * Kirim notifikasi ke Seller dengan fallback.
     */
    public function sendToSeller(
        User $seller,
        string $event,
        string $telegramHtml,
        string $waText,
        ?Laporan $laporan = null,
        array $markup = []
    ): void {
        $sellerPhone = $seller->toko?->no_telp ?: $seller->customer->first()?->nomor_telepon;

        $fallbackContext = [
            'phone' => $sellerPhone,
            'message' => $waText,
        ];

        if ($seller->hasTelegram()) {
            $log = $this->logAction($seller->id, $event, 'telegram', 'pending', null, $laporan?->id);

            SendTelegramNotification::dispatch(
                $seller->telegram_chat_id,
                $telegramHtml,
                $markup,
                $laporan?->id,
                $seller->id,
                $event,
                $log->id,
                $fallbackContext
            );
        } else {
            $skipReason = $seller->telegram_blocked_at ? 'Telegram diblokir' : 'Telegram belum tertaut';
            $this->logAction($seller->id, $event, 'telegram', 'skipped', $skipReason, $laporan?->id);

            $this->sendWhatsAppDirectly($seller->id, $event, $sellerPhone, $waText, $laporan?->id);
        }
    }

    /**
     * Kirim notifikasi ke Admin Group Telegram.
     */
    public function sendToAdmin(string $event, string $telegramHtml, ?Laporan $laporan = null, array $markup = []): void
    {
        $adminGroupId = config('services.telegram.admin_group_id');

        if (empty($adminGroupId)) {
            $this->logAction(null, $event, 'telegram', 'skipped', 'TELEGRAM_ADMIN_GROUP_ID belum dikonfigurasi', $laporan?->id);
            return;
        }

        $log = $this->logAction(null, $event, 'telegram', 'pending', null, $laporan?->id);

        SendTelegramNotification::dispatch(
            $adminGroupId,
            $telegramHtml,
            $markup,
            $laporan?->id,
            null,
            $event,
            $log->id,
            [],
            $laporan?->telegram_topic_id
        );
    }

    /**
     * Pengiriman langsung via WhatsApp (Fonnte) dengan pencatatan log.
     */
    protected function sendWhatsAppDirectly(?int $userId, string $event, ?string $phone, string $message, ?int $laporanId = null): void
    {
        if (empty($phone)) {
            $this->logAction($userId, $event, 'whatsapp', 'skipped', 'Nomor telepon tidak tersedia untuk fallback WhatsApp', $laporanId);
            return;
        }

        $log = $this->logAction($userId, $event, 'whatsapp', 'pending', null, $laporanId);

        try {
            $response = $this->fonnte->sendText($phone, $message);
            $success = ($response['status'] ?? false) === true;

            $log->update([
                'status' => $success ? 'sent' : 'failed',
                'error' => $success ? null : ($response['reason'] ?? 'Gagal mengirim pesan via Fonnte'),
                'sent_at' => now(),
            ]);
        } catch (\Throwable $e) {
            $log->update([
                'status' => 'failed',
                'error' => $e->getMessage(),
                'sent_at' => now(),
            ]);
        }
    }

    protected function logAction(?int $userId, string $event, string $channel, string $status, ?string $error = null, ?int $laporanId = null): NotifikasiLog
    {
        return NotifikasiLog::create([
            'user_id' => $userId,
            'event' => $event,
            'channel' => $channel,
            'status' => $status,
            'error' => $error,
            'laporan_id' => $laporanId,
            'sent_at' => now(),
        ]);
    }

    /**
     * Deteksi fleksibel konteks toko, produk, order, dan seller terkait.
     */
    public function resolveTicketContext(Laporan $laporan): array
    {
        $context = [
            'order_id' => null,
            'toko_nama' => null,
            'produk_nama' => null,
            'kategori' => null,
            'seller_user' => null,
        ];

        // 1. Cek dari assigned_to jika ada dan merupakan seller
        if ($laporan->assigned_to) {
            $assignedUser = User::find($laporan->assigned_to);
            if ($assignedUser && $assignedUser->isSeller()) {
                $context['seller_user'] = $assignedUser;
                $context['toko_nama'] = $assignedUser->toko?->nama_toko;
            }
        }

        // 2. Deteksi order ID dari judul atau deskripsi tiket
        $combinedText = $laporan->judul . ' ' . $laporan->deskripsi;
        if (preg_match('/(INV-[A-Z0-9]+|[0-9A-Z]{20,30})/i', $combinedText, $matches)) {
            $orderId = $matches[1];
            $context['order_id'] = $orderId;

            // Cari pembelian premium
            $pembelian = Pembelian::with('varianLayanan.tipeLayanan.produk.toko.user')
                ->where('order_id', $orderId)
                ->first();

            if ($pembelian) {
                $produk = $pembelian->varianLayanan?->tipeLayanan?->produk;
                $toko = $produk?->toko;
                if ($toko) {
                    $context['toko_nama'] = $toko->nama_toko;
                    $context['seller_user'] = $context['seller_user'] ?: $toko->user;
                }
                if ($produk) {
                    $context['produk_nama'] = $produk->nama_produk;
                    $context['kategori'] = $produk->kategori;
                }
            } else {
                // Cari order legacy
                $legacy = BeliProdukModel::with('toko.user')
                    ->where('order_id', $orderId)
                    ->first();
                if ($legacy && $legacy->toko) {
                    $context['toko_nama'] = $legacy->toko->nama_toko;
                    $context['seller_user'] = $context['seller_user'] ?: $legacy->toko->user;
                }
            }
        }

        // 3. Fallback: jika seller belum terdeteksi, gunakan pesanan terbaru dari customer
        if (!$context['seller_user'] && $laporan->user_id) {
            $latestPembelian = Pembelian::with('varianLayanan.tipeLayanan.produk.toko.user')
                ->whereHas('pembayaran', fn($q) => $q->where('status', 'success'))
                ->where('id_customer', $laporan->user_id)
                ->latest()
                ->first();

            if ($latestPembelian) {
                $produk = $latestPembelian->varianLayanan?->tipeLayanan?->produk;
                $toko = $produk?->toko;
                if ($toko) {
                    $context['toko_nama'] = $context['toko_nama'] ?: $toko->nama_toko;
                    $context['seller_user'] = $toko->user;
                }
                if ($produk) {
                    $context['produk_nama'] = $context['produk_nama'] ?: $produk->nama_produk;
                    $context['kategori'] = $context['kategori'] ?: $produk->kategori;
                }
            }
        }

        return $context;
    }
}

