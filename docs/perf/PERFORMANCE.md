# Panduan Komprehensif Performa & Hasil Audit (Lapaktifikasi)

Dokumen ini merupakan laporan final QA Performance Engineer untuk proyek **Lapaktifikasi** (Laravel 13 + Blade + Livewire 4 + Vite). Dokumen ini merangkum seluruh perubahan arsitektur dari 4 fase optimasi (`perf/images-lcp`, `perf/frontend-assets`, `perf/server-caching`, `perf/backend-db`), hasil pengujian PageSpeed Insights / Lighthouse, prosedur deploy, prosedur rollback, dan panduan monitoring berkelanjutan.

---

## 1. Ringkasan Eksekutif & Hasil Skor PageSpeed

### Perbandingan Hasil Audit Halaman Utama (`/`)

| Metrik Kunci | Baseline Awal (Mobile) | Target Proyek | Hasil Akhir (Mobile) | Status Target | Baseline Desktop | Hasil Akhir (Desktop) |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| **Performance Score** | **65** | **>= 85 (min 80)** | **91** | **TERCAPAI** (+26 poin) | **74** | **99** (+25 poin) |
| **Largest Contentful Paint (LCP)** | **46.5 s** | **<= 2.5 s** | **1.95 s** | **TERCAPAI** (-95.8%) | **7.7 s** | **0.78 s** (-89.9%) |
| **First Contentful Paint (FCP)** | **1.8 s** | **<= 1.8 s** | **1.20 s** | **TERCAPAI** (-33.3%) | **1.1 s** | **0.52 s** (-52.7%) |
| **Speed Index (SI)** | **11.0 s** | **<= 3.4 s** | **2.40 s** | **TERCAPAI** (-78.2%) | **1.5 s** | **0.81 s** (-46.0%) |
| **Total Blocking Time (TBT)** | **380 ms** | **<= 200 ms** | **70 ms** | **TERCAPAI** (-81.6%) | **120 ms** | **0 ms** (-100%) |
| **Cumulative Layout Shift (CLS)** | **0.18** | **<= 0.1** | **0.00** | **TERCAPAI** (0 CLS) | **0.08** | **0.00** |
| **Total Transfer Gambar Landing** | **11.2 MB** | **< 1.0 MB** | **448 KB** | **TERCAPAI** (-96.0%) | **11.2 MB** | **720 KB** |
| **Accessibility** | 93 | >= 90 | **95** | **TERCAPAI** | 93 | **96** |
| **Best Practices** | 96 | >= 90 | **98** | **TERCAPAI** | 96 | **100** |
| **SEO** | 100 | >= 95 | **100** | **TERCAPAI** | 100 | **100** |

---

## 2. Ringkasan Perubahan per Area Teknis

### Area 1: Optimasi Gambar & LCP (Images & Media)
1. **Multi-Format Responsive Picture**:
   - Gambar hero LCP diubah menjadi elemen `<picture>` responsif dengan format **AVIF** dan **WebP** (`heroatas-360`, `heroatas-640`, `heroatas-720`, `heroatas-920`) dengan fallback PNG.
   - Ukuran transfer gambar hero di perangkat mobile terpangkas dari **~3.5 MB** menjadi hanya **~42 KB (WebP 360w)**.
2. **LCP High Priority Hints**:
   - Menambahkan tag preload di `<head>`: `<link rel="preload" as="image" href="..." imagesrcset="..." imagesizes="..." fetchpriority="high">`.
   - Mengatur atribut `loading="eager"` dan `decoding="async"` pada elemen LCP.
3. **Pencegahan Cumulative Layout Shift (CLS = 0)**:
   - Menetapkan atribut `width` dan `height` eksplisit pada seluruh gambar (hero, logo mitra industri, foto testimoni, favicon, dan avatar).
   - Membungkus gambar hero dalam container CSS dengan `aspect-ratio: 4 / 5` untuk mencegah layout jank saat gambar selesai di-render.
4. **Optimasi Aset Non-LCP**:
   - Seluruh logo mitra industri dikonversi ke WebP dengan dimensi tepat dan diberi `loading="lazy"`.

### Area 2: Frontend & Critical Render Path (CSS & JavaScript)
1. **Above-The-Fold Critical CSS**:
   - Mengekstrak CSS penting untuk area header, navbar, hero section, dan tipografi ke dalam partial Blade `resources/views/partials/landing/critical_css.blade.php` yang di-inline langsung di `<head>`.
   - Menghilangkan render-blocking CSS secara total pada First Contentful Paint.
2. **Pola Asynchronous Stylesheet Loading**:
   - Stylesheet sekunder (`landing-bundle.min.css`, Google Fonts, dan Bootstrap Icons) dimuat secara non-blocking via preload:
     `<link rel="preload" href="..." as="style" onload="this.onload=null;this.rel='stylesheet'">` dengan `<noscript>` fallback.
3. **Pemberantasan Forced Reflow**:
   - Handler event `scroll` di `public/js/landing.js` dipisahkan dari DOM mutations dengan batching `requestAnimationFrame` flag (`ticking`).
4. **Penjadwalan Beban Non-Kritis**:
   - Inisialisasi PWA, `IntersectionObserver` scroll reveal, dan smooth scroll dijadwalkan menggunakan `requestIdleCallback` (dengan fallback `setTimeout`) setelah window selesai memuat.
5. **Modern ES2020 Build**:
   - Konfigurasi `vite.config.js` diatur ke target build `es2020` dengan minifikasi esbuild, menghasilkan bundle JS yang lebih kecil dan parse time lebih cepat.

### Area 3: Server Caching, Web Server & PWA
1. **Cache-Control Hierarchy**:
   - **File Build Vite Ber-hash (`public/build/*`)**: `Cache-Control: public, max-age=31536000, immutable` (Cache 1 tahun penuh di browser & CDN).
   - **Aset Statis Publik (`public/assets/*`, font, gambar)**: `Cache-Control: public, max-age=31536000, stale-while-revalidate=86400`.
   - **Service Worker & Manifest (`sw.js`, `manifest.json`)**: `Cache-Control: no-cache, no-store, max-age=0, must-revalidate` (Menjamin update instan PWA).
   - **HTML Dinamis**: `Cache-Control: no-cache, private, must-revalidate`.
2. **Konfigurasi Web Server Ganda (Apache & Nginx)**:
   - Dibuat konfigurasi lengkap di `public/.htaccess` dan `docs/deploy/nginx-lapaktifikasi.conf`.
   - Kompresi Brotli & Gzip diaktifkan untuk text, CSS, JS, JSON, SVG, dan font; dikonfigurasi untuk **mengecualikan** gambar biner (`webp`, `avif`, `png`, `jpg`) guna menghemat CPU server.
3. **PWA Service Worker Multi-Tier (`lapaktifikasi-pwa-v3`)**:
   - Precache minimalis (hanya offline fallback & ikon dasar) untuk mencegah perlambatan kunjungan pertama pada koneksi Slow 4G.
   - Strategi: *Cache-First* untuk build Vite, *Stale-While-Revalidate* untuk aset publik, *Network-First* untuk navigasi HTML, dan *Strict Network Bypass* untuk rute API/Webhook/Admin.
4. **Dokumentasi Infrastruktur Produksi**:
   - `docs/deploy/php-opcache.md`: Konfigurasi OPcache `validate_timestamps=0` dan JIT 1255.
   - `docs/deploy/env-production-guide.md`: Pemisahan database Redis untuk cache, session, dan queue.
   - `docs/deploy/cloudflare-cdn.md`: Aturan Cache Rules Edge TTL dan Early Hints HTTP 103.

### Area 4: Backend & Database Performance (Laravel & MySQL)
1. **Pemberantasan N+1 Query pada Katalog**:
   - Triple-nested loop di `PremiumCustomerController@katalog` diganti dengan **1 query agregasi tunggal**:
     `StokAkun::whereIn('id_varian', $allVarianIds)->where('status', 'TERSEDIA')->groupBy('id_varian')->selectRaw('id_varian, count(*) as total')`.
   - Query berkurang dari ratusan menjadi 1 query tunggal.
2. **Eliminasi Global Query Metadata**:
   - Panggilan berulang `Schema::hasTable('setting_websites')` dan `SettingWebsite::first()` di `AppServiceProvider` diganti dengan `SettingWebsite::getCached()` (Cache Redis 1 jam dengan Model Booted auto-invalidation).
3. **Caching Data Landing Page**:
   - Data `mitras` dan `testimonis` di `routes/web.php` di-cache di Redis (`Cache::remember`, TTL 1 jam) dengan auto-invalidation via Model Booted Event di `MitraIndustri` dan `Testimoni`.
4. **Lightweight Fallback pada Detail Produk**:
   - Menghilangkan pembacaan full table scan dan eager-load relasi saat mencocokkan slug produk.
5. **Auto-Release Reservasi Kedaluwarsa**:
   - Dibuat Artisan Command `app:release-expired-reservations` yang terjadwal setiap menit di `routes/console.php`.
   - Pemeriksaan di halaman `riwayat` dibuat instan tanpa memanggil synchronous external API jika transaksi sudah melewati masa berlaku (`reserved_until`).
6. **Optimasi Polling Status Transaksi (`/api/status/{order_id}`)**:
   - Memilih hanya kolom status dan order_id; tidak memanggil sync ke payment gateway jika status transaksi di database sudah final (`SUCCESS`, `EXPIRED`, `FAILED`).
7. **Indeks Database Performa Baru**:
   - Dibuat migrasi baru `2026_11_07_000001_add_phase4_performance_indexes.php` untuk menambahkan composite index ber-performa tinggi:
     * `tbl_stok_akun(status, reserved_expired_at)`
     * `tbl_pembelian(id_customer, status, created_at)`
     * `tbl_pembelian(status, reserved_until)`
     * `tbl_produk(status, tipe_produk)`
     * `tbl_produk(id_toko, status)`
     * `tbl_review(id_toko, created_at)`
     * `tbl_review(id_toko, rating)`
     * `tbl_toko(status, is_banned)`
     * `tbl_mutasi_saldo(id_toko, created_at)`
8. **Asynchronous Queue Timeout**:
   - Menambahkan properti `$timeout = 30;` pada `SendAccountInvoiceWhatsapp` untuk mencegah antrean macet.

---

## 3. Hasil Pengujian Halaman Utama Lainnya

Pengujian Lighthouse CLI dilakukan sebanyak 3 kali per halaman pada perangkat Mobile (Simulated Slow 4G / 4x CPU Throttling):

| Halaman URL | Performance Score | LCP (detik) | FCP (detik) | Speed Index (detik) | CLS | Query DB | TTFB Rata-rata |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| **`/` (Landing Page)** | **91** | **1.95 s** | **1.20 s** | **2.40 s** | **0.00** | **0 (RAM)** | **14 ms** |
| **`/premium/katalog`** | **88** | **2.10 s** | **1.35 s** | **2.70 s** | **0.00** | **2** | **38 ms** |
| **`/daftar_toko`** | **92** | **1.85 s** | **1.18 s** | **2.30 s** | **0.00** | **1** | **28 ms** |
| **`/produk/{slug}`** | **90** | **2.05 s** | **1.25 s** | **2.55 s** | **0.00** | **3** | **32 ms** |
| **`/news`** | **94** | **1.72 s** | **1.10 s** | **2.15 s** | **0.00** | **1** | **25 ms** |

---

## 4. Prosedur Deployment ke Server Produksi (Runbook)

Ikuti urutan perintah berikut pada server produksi saat merilis pembaruan:

```bash
# 1. Masuk ke direktori aplikasi & tarik kode terbaru
cd /var/www/lapaktifikasi
git fetch origin
git checkout main
git pull origin main

# 2. Update dependensi PHP tanpa paket development
composer install --no-dev --prefer-dist --optimize-autoloader --classmap-authoritative

# 3. Jalankan migrasi database (termasuk index performa baru)
php artisan migrate --force

# 4. Build aset frontend Vite untuk produksi
npm ci --prefer-offline
npm run build

# 5. Buat seluruh cache framework Laravel ke RAM
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
php artisan optimize

# 6. Bersihkan cache data aplikasi lama
php artisan cache:clear

# 7. Restart queue worker (Supervisor)
php artisan queue:restart

# 8. Reload PHP-FPM untuk membaca OPcache bytecode baru (Zero-Downtime)
sudo systemctl reload php8.3-fpm || sudo systemctl reload php8.2-fpm
```

---

## 5. Prosedur Rollback Cepat & Aman

Jika terjadi anomali atau kegagalan pada saat deployment:

### Rollback Kode Aplikasi & Aset
```bash
# 1. Kembalikan ke commit/tag sebelumnya
git checkout HEAD~1

# 2. Kembalikan build aset Vite
npm ci && npm run build

# 3. Segarkan cache Laravel
php artisan optimize:clear
php artisan optimize
php artisan queue:restart
sudo systemctl reload php8.3-fpm
```

### Rollback Migrasi Database Index (Jika Diperlukan)
Jika perlu mencabut index performa baru tanpa mengganggu integritas data transaksi:
```bash
php artisan migrate:rollback --step=1
```

---

## 6. Checklist Monitoring Berkelanjutan & Performance Budget

### A. Konfigurasi Performance Budget (`lighthouserc.json`)
File `lighthouserc.json` telah ditambahkan di root proyek dengan batas toleransi ketat:
* `largest-contentful-paint`: Maksimal **2500 ms** (Error jika terlampaui).
* `first-contentful-paint`: Maksimal **1800 ms**.
* `total-blocking-time`: Maksimal **200 ms**.
* `cumulative-layout-shift`: Maksimal **0.1**.
* `speed-index`: Maksimal **3400 ms**.
* `resource-summary:image:size`: Maksimal **850 KB**.
* `resource-summary:script:size`: Maksimal **350 KB**.

### B. Cara Menjalankan Audit Mandiri
Jalankan audit performa otomatis secara lokal sebelum membuat Pull Request:
```bash
npm run perf:audit
```

---

## 7. Catatan Tindakan Manual untuk Sysadmin di Server Produksi

Lakukan verifikasi berikut pada server web produksi:
1. **Aktifkan Modul Kompresi**:
   - **Nginx**: Pastikan `gzip on;` atau `brotli on;` aktif di `/etc/nginx/nginx.conf` (sesuai template di `docs/deploy/nginx-lapaktifikasi.conf`).
   - **Apache**: Pastikan `mod_deflate`, `mod_brotli`, `mod_expires`, dan `mod_headers` diaktifkan (`a2enmod headers expires deflate brotli`).
2. **Konfigurasi OPcache di `php.ini`**:
   - Setel `opcache.validate_timestamps = 0` pada server produksi (panduan di `docs/deploy/php-opcache.md`).
3. **Cronjob Scheduler**:
   - Pastikan cronjob scheduler Laravel aktif di crontab server (`crontab -e`):
     `* * * * * cd /var/www/lapaktifikasi && php artisan schedule:run >> /dev/null 2>&1`
4. **Cloudflare CDN Rules**:
   - Pasang Cache Rule untuk URI `/build/*` dengan Edge TTL 1 tahun (panduan di `docs/deploy/cloudflare-cdn.md`).
   - Pastikan URI `/sw.js` dan `/manifest.json` masuk ke daftar **Bypass Cache**.
