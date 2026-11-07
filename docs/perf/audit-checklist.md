# Checklist Audit & Verifikasi Regresi Fungsional (Lapaktifikasi)

Dokumen ini mencatat hasil audit menyeluruh dan pengujian regresi fungsional dari penggabungan seluruh branch optimasi performa (`perf/images-lcp`, `perf/frontend-assets`, `perf/server-caching`, `perf/backend-db`) ke dalam branch integrasi final `perf/final-audit`.

---

## 1. Matriks Pengujian Regresi Fungsional

| Modul / Alur Kerja | Skenario Pengujian | Hasil Pengujian | Status |
| :--- | :--- | :--- | :--- |
| **Autentikasi: Login & Register** | User customer & admin login dengan email/password | Kredensial valid masuk ke dashboard sesuai role; session Redis tersimpan dengan aman | **PASS** |
| **Autentikasi: Google OAuth** | User login menggunakan akun Google SSO | Callback Google `/auth/google/callback` memetakan user/customer tanpa error; token aman | **PASS** |
| **Autentikasi: Wajib Ganti Password** | Seller/Admin dengan flag `must_change_password = 1` | Middleware mengarahkan ke form ubah kata sandi sebelum membuka rute terlindungi | **PASS** |
| **Autentikasi: Banned Redirect** | User berstatus `is_banned = true` mencoba login / akses rute | Sesi di-logout seketika dan dialihkan ke `/banned` dengan pesan alasan penonaktifan | **PASS** |
| **Katalog: Filter Kategori** | Filter "Semua", "Akun Premium", dan "Produk Digital" | Query ter-index `idx_produk_status_tipe` menyaring produk tanpa reload seluruh halaman | **PASS** |
| **Katalog: Pencarian Produk** | Pencarian berdasarkan kata kunci nama produk & deskripsi | Menampilkan hasil relevan; pagination link mempertahankan parameter query `search` | **PASS** |
| **Katalog: Live Stock Checker** | Request Ajax ke `/varian/{id_varian}/stok` | Mengembalikan jumlah stok terkini secara instan (< 10 ms) tanpa locking | **PASS** |
| **Detail Produk: Routing & Slug** | Akses via `/produk/{slug}` dan `/toko/{slug}/produk/{slug}` | Fallback cerdas menemukan ID produk dengan query ringan tanpa eager-load tabel | **PASS** |
| **Checkout: Validasi Profil** | Checkout tanpa nomor WhatsApp / nama lengkap | Ditolak dengan pesan validasi profil; diarahkan untuk melengkapi nomor telepon WhatsApp | **PASS** |
| **Checkout: Voucher & Diskon** | Input voucher diskon (persen & nominal, limit kuota) | Kuota dicek secara atomik dengan `lockForUpdate`; potongan harga dihitung tepat | **PASS** |
| **Checkout: Pessimistic Stock Lock** | Pembeli memilih varian dan klik checkout | Stok akun di-lock via `lockForUpdate()`, status menjadi `RESERVED`, window 15 menit (`reserved_until`) | **PASS** |
| **Pembayaran: Gateway & QRIS/VA** | Generate transaksi pembayaran Midtrans / TriPay / Pakasir | Nomor VA & QRIS ter-generate dengan benar; status transaksi tersimpan sebagai `PENDING` | **PASS** |
| **Pembayaran: Polling Status API** | Polling frontend ke `/api/status/{order_id}` | Membaca status instan dari index database tanpa memanggil external API jika sudah `SUCCESS` | **PASS** |
| **Webhook Payment Gateway** | Callback notifikasi dari Midtrans, TriPay, dan Pakasir | Validasi signature SHA512/HMAC berhasil; transaksi di-update `SUCCESS`; webhook log tersimpan | **PASS** |
| **Queue WhatsApp Invoice** | Callback pembayaran memicu pengiriman invoice WA | `SendAccountInvoiceWhatsapp` di-dispatch ke antrean Redis (`tries=3`, `backoff=20s`, `timeout=30s`) | **PASS** |
| **Kredensial & Digital Download** | Pembeli sukses mengakses akun / file digital | Kredensial password didekripsi on-demand dengan `Crypt::decryptString`; file digital aman di-download | **PASS** |
| **Invoice PDF** | Pengunduhan bukti pembayaran invoice PDF | DomPDF mengompilasi template Blade ke stream PDF yang rapi | **PASS** |
| **Dashboard Admin & Seller** | Perhitungan omzet, pesanan hari ini, filter rentang tanggal | Menampilkan ringkasan tepat menggunakan subquery database hemat RAM | **PASS** |
| **Saldo Toko & Mutasi** | Mutasi kredit penjualan saat transaksi berhasil | Saldo bertambah akurat via transaksi database; histori pagination `tbl_mutasi_saldo` lancar | **PASS** |
| **Mode Maintenance** | Toggle maintenance di `tbl_setting_komisi` | Middleware `CheckMaintenanceMode` mengembalikan HTTP 503 untuk guest/seller; admin tetap dapat akses | **PASS** |
| **Scheduler Auto-Release** | Eksekusi `php artisan app:release-expired-reservations` | Transaksi pending > 15 menit berubah jadi `EXPIRED`; stok `RESERVED` kembali menjadi `TERSEDIA` | **PASS** |

---

## 2. Verifikasi Frontend & Responsivitas Viewport

Semua komponen antarmuka landing page dan halaman publik telah diuji pada 3 ukuran layar standar:

### A. Mobile Viewport (375px × 667px / 390px × 844px)
* **Gutter & Overflow**: Padding horizontal 16px konsisten, tidak ada horizontal scrollbar (`overflow-x: hidden` aman).
* **Hero Section**: Gambar hero berukuran responsif (`320px` width) menggunakan WebP/AVIF varian 360w/640w.
* **Navigation Drawer**: Mobile drawer membuka dan menutup mulus dengan backdrop blur dan `document.body.style.overflow = 'hidden'`.
* **Cumulative Layout Shift (CLS)**: **0.00**. Semua elemen gambar (`<img>`), badge mitra, dan avatar testimoni memiliki atribut `width` dan `height` eksplisit serta `aspect-ratio` CSS wrapper.

### B. Tablet Viewport (768px × 1024px)
* **Grid Layout**: Grid kategori dan katalog beradaptasi otomatis menjadi 2 kolom responsif.
* **Logo Mitra**: Marquee/grid mitra industri tersusun simetris tanpa stretching visual.

### C. Desktop Viewport (1440px × 900px / 1920px × 1080px)
* **Hero Image**: Menampilkan varian beresolusi tinggi 920w dengan ketajaman maksimal tanpa blur.
* **Layout**: Container terpusat maksimal 1280px dengan margin auto.

---

## 3. Verifikasi Browser Console & Integritas Aset

* [x] **0 Console Errors**: Tidak ada runtime JavaScript exception (Uncaught TypeError / ReferenceError).
* [x] **0 Asset 404s**: Semua path gambar WebP, AVIF, SVG ikon, file CSS bundle, dan JavaScript build terkonfirmasi ada di direktori `public/`.
* [x] **0 Mixed Content Warnings**: Seluruh link eksternal (Google Fonts, Bootstrap Icons CDN) menggunakan protokol HTTPS murni.
* [x] **PWA Service Worker Health**: `sw.js` terdaftar secara asinkron tanpa menunda event `load` utama browser.
