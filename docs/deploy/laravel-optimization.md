# Panduan Optimasi Laravel 13 untuk Produksi (Lapaktifikasi)

Dokumentasi ini merinci seluruh rangkaian perintah optimasi performa Laravel untuk mencapai performa TTFB (Time to First Byte) di bawah 100 ms pada server produksi.

---

## 1. Kompilasi Cache Produksi Laravel

Jalankan perintah-perintah berikut di lingkungan produksi setiap kali melakukan deploy baru:

```bash
# 1. Cache Konfigurasi (.env & config/*.php menjadi 1 file tunggal)
php artisan config:cache

# 2. Cache Routing (Memetakan seluruh rute ke array serialized cepat)
php artisan route:cache

# 3. Cache Blade Views (Pre-compile seluruh template Blade ke PHP murni)
php artisan view:cache

# 4. Cache Event & Listener Mapping
php artisan event:cache

# 5. Laravel Framework Optimizer (Menggabungkan optimasi bawaan)
php artisan optimize
```

### Mengapa Perintah Ini Sangat Berpengaruh?
* **Tanpa `config:cache`**: Laravel harus membaca puluhan file di dalam folder `config/` dan melakukan parsing `.env` pada **setiap request**.
* **Dengan `config:cache`**: Seluruh konfigurasi digabungkan ke dalam 1 file `bootstrap/cache/config.php` yang langsung dimuat oleh PHP OPcache ke memory RAM.
* **Tanpa `route:cache`**: Laravel harus mendaftarkan rute satu per satu melalui Reflection API dan closure binding.
* **Dengan `route:cache`**: Seluruh tabel rute dimuat instan dari `bootstrap/cache/routes-v7.php`.

> ⚠️ **Catatan Penting**:
> Jika Anda mengubah file `.env` di server produksi, Anda **wajib** menjalankan `php artisan config:clear && php artisan config:cache` agar perubahan terbaca oleh aplikasi.

---

## 2. Optimasi Composer Autoloader

Untuk meniadakan I/O lookup file class PHP di disk:

```bash
# Install tanpa paket dev & buat classmap definitif
composer install --no-dev --prefer-dist --optimize-autoloader --classmap-authoritative

# Atau jika dependencies sudah terpasang, cukup dump classmap teroptimasi:
composer dump-autoload -o --classmap-authoritative
```

### Manfaat `--classmap-authoritative`:
Composer akan mengompilasi seluruh class PSR-4 ke dalam satu array statis `vendor/composer/autoload_classmap.php`. PHP tidak akan pernah lagi memeriksa filesystem dengan `file_exists()` saat melakukan `use App\Models\...` atau memuat package pihak ketiga.

---

## 3. Optimasi Frontend Build (Vite)

Pastikan aset frontend di-build dengan target modern ES2020:

```bash
# Install dependencies & build production bundles
npm ci --prefer-offline
npm run build
```

Hasil build akan berada di `public/build/` dengan penamaan file ber-hash konten (misalnya `app-B48f10ae.js`). File-file ini aman di-cache oleh Nginx/Apache selama **1 tahun (immutable)**.

---

## 4. Reset & Restart Antrean (Queue)

Jika menggunakan Redis Queue Worker (misal Supervisor):

```bash
# Beritahu background worker untuk merestart proses agar membaca kode terbaru
php artisan queue:restart
```

---

## 5. Ringkasan Eksekusi Cepat (One-Liner Deploy)

Gunakan script siap pakai di `docs/deploy/deploy.sh` atau salin perintah berikut:

```bash
composer install --no-dev -o --classmap-authoritative && \
npm run build && \
php artisan config:cache && \
php artisan route:cache && \
php artisan view:cache && \
php artisan event:cache && \
php artisan optimize && \
php artisan queue:restart
```
