<?php

namespace App\Console\Commands;

use App\Models\Laporan;
use App\Models\SettingWebsite;
use App\Services\Laporan\LaporanService;
use App\Services\Telegram\NotificationRouter;
use App\Services\Telegram\TelegramClient;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckLaporanSlaCommand extends Command
{
    protected $signature = 'laporan:check-sla';
    protected $description = 'Periksa batas waktu SLA tiket laporan: kirim pengingat dan eskalasi otomatis jika lewat batas waktu.';

    public function handle(LaporanService $laporanService, NotificationRouter $notificationRouter): int
    {
        $now = now();
        $this->info("Memeriksa SLA tiket laporan per {$now->toDateTimeString()}...");

        // Cari tiket pending di mana belum ada first_response_at
        $pendingTickets = Laporan::with(['user.customer'])
            ->where('status', 'pending')
            ->whereNull('first_response_at')
            ->whereNotNull('sla_due_at')
            ->get();

        $remindersCount = 0;
        $escalationsCount = 0;

        foreach ($pendingTickets as $laporan) {
            $context = $notificationRouter->resolveTicketContext($laporan);
            $sellerUser = $context['seller_user'] ?? null;
            $ticketNumber = '#' . str_pad($laporan->id, 5, '0', STR_PAD_LEFT);

            // 1. Cek apakah sudah lewat sla_due_at dan belum direspons -> Eskalasi otomatis (idempoten)
            if ($now->greaterThanOrEqualTo($laporan->sla_due_at)) {
                if (is_null($laporan->escalated_at)) {
                    $this->warn("Tiket {$ticketNumber} melewati batas SLA. Melakukan eskalasi otomatis ke grup admin...");
                    $escalated = $laporanService->escalateTicket(
                        $laporan,
                        'system_sla',
                        'system_sla',
                        'Batas waktu SLA respon telah terlampaui tanpa respon dari seller'
                    );
                    if ($escalated) {
                        $escalationsCount++;
                    }
                }
                continue;
            }

            // 2. Cek apakah mendekati sla_due_at (<= 60 menit) dan belum pernah dikirim reminder
            $threshold = $laporan->sla_due_at->copy()->subMinutes(60);
            if ($now->greaterThanOrEqualTo($threshold) && is_null($laporan->sla_reminder_sent_at)) {
                if ($sellerUser) {
                    $this->info("Mengirimkan pengingat SLA tiket {$ticketNumber} ke seller...");

                    $remHtml = "⚠️ <b>PENGINGAT BATAS WAKTU SLA TIKET {$ticketNumber}</b>\n\n" .
                               "Halo <b>" . TelegramClient::escape($context['toko_nama'] ?: 'Seller') . "</b>,\n" .
                               "Tiket laporan kendala <b>{$ticketNumber}</b> belum mendapatkan respon dan akan melewati batas waktu SLA dalam <b>kurang dari 60 menit</b>:\n\n" .
                               "• <b>Batas Waktu (SLA):</b> " . $laporan->sla_due_at->format('d/m/Y H:i') . " WIB\n" .
                               "• <b>Kendala:</b> " . TelegramClient::escape($laporan->judul) . "\n\n" .
                               "Mohon segera ambil atau tanggapi tiket ini. Jika batas waktu terlampaui, tiket akan secara otomatis dieskalasi ke tim Admin.";

                    $remWa = "⚠️ *PENGINGAT BATAS WAKTU SLA TIKET {$ticketNumber}*\n\n" .
                             "Halo " . ($context['toko_nama'] ?: 'Seller') . ", tiket {$ticketNumber} belum direspons dan akan melewati batas waktu SLA dalam <60 menit (Due: " . $laporan->sla_due_at->format('d/m/Y H:i') . ").\n\n" .
                             "Segera tindak lanjuti di dashboard: " . url('/seller/dashboard');

                    $markup = $laporanService->buildSellerActionButtons($laporan);

                    $notificationRouter->sendToSeller(
                        $sellerUser,
                        'sla_reminder_seller',
                        $remHtml,
                        $remWa,
                        $laporan,
                        $markup
                    );

                    $laporan->update(['sla_reminder_sent_at' => $now]);
                    $remindersCount++;
                }
            }
        }

        $this->info("Pemeriksaan SLA selesai. Pengingat terkirim: {$remindersCount}, Eskalasi otomatis: {$escalationsCount}.");
        return self::SUCCESS;
    }
}

