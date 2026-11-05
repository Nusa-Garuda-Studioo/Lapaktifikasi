
Proyek: Lapaktifikasi, marketplace akun premium dan file digital.
Stack: Laravel 13, PHP 8.2+, Livewire 4, MySQL, Redis, queue database/redis,
Sanctum, Fonnte untuk WhatsApp (job SendAccountInvoiceWhatsapp), Vite.
Role: Super Admin (1), Customer (2), Seller (3).
Sistem laporan/garansi sudah ada: customer di /premium/laporan, admin di
/premium/laporan-admin, status pending -> proses -> selesai, upload screenshot.

Tujuan fitur: menambah Telegram sebagai channel tambahan untuk sistem laporan
dan support (bukan untuk pembelian). Website tetap jalur utama.
Customer: Telegram opsional. Seller: wajib menaut sebelum toko aktif tayang.
Admin: menerima alert di grup Telegram.

ATURAN UMUM
1. Pelajari dulu struktur repo (routes, controller, model, job, middleware,
   migrasi) dan temukan nama tabel serta model laporan yang sudah ada. Jangan
   menebak nama; tulis asumsi Anda sebelum mengubah kode.
2. Ikuti pola kode yang sudah ada (gaya controller, service, job, request
   validation, nama tabel berawalan tbl_). Jangan refactor kode lama.
3. Jangan mengubah perilaku fitur yang sudah berjalan. Semua perubahan skema
   lewat migrasi BARU, bersifat additive, dan bisa di-rollback.
4. Semua fitur Telegram harus mati total jika TELEGRAM_ENABLED=false.
5. Password atau kredensial akun digital TIDAK BOLEH dikirim, diterima, atau
   dicatat lewat Telegram. Bot hanya boleh mengirim tautan ke dashboard.
6. Jangan menulis token bot, secret, atau token penautan ke log.
7. Gunakan DB::transaction untuk perubahan yang menyentuh beberapa tabel.
8. Semua teks yang tampil ke user berbahasa Indonesia.
9. Tulis test (Pest atau PHPUnit, ikuti yang sudah dipakai repo) untuk logika
   baru, lalu jalankan dan laporkan hasilnya.
10. Di akhir, ringkas: file yang dibuat/diubah, migrasi yang harus dijalankan,
    env baru, cara mengujinya, dan hal yang belum selesai.