# Panduan Konfigurasi Environment Produksi (.env) Lapaktifikasi

Panduan ini berisi rekomendasi parameter `.env` untuk server produksi untuk memaksimalkan performa, keamanan, dan konkurensi database tanpa memuat credential rahasia asli.

---

## 1. Template Nilai `.env` Produksi yang Direkomendasikan

Salin dan sesuaikan variabel-variabel kunci berikut pada file `.env` di server produksi:

```dotenv
# ==============================================================================
# APLIKASI UTAMA
# ==============================================================================
APP_NAME="Lapaktifikasi"
APP_ENV=production
APP_KEY=base64:GENERATE_VIA_PHP_ARTISAN_KEY_GENERATE
APP_DEBUG=false
APP_URL=https://lapaktifikasi.com

# Matikan stack trace publik
DEBUGBAR_ENABLED=false

# ==============================================================================
# LOGGING (PERFORMANCE FRIENDLY)
# ==============================================================================
LOG_CHANNEL=daily
LOG_LEVEL=warning
LOG_DEPRECATIONS_CHANNEL=null

# ==============================================================================
# DRIVER PERFORMA TINGGI (REDIS STACK)
# ==============================================================================
# Menggunakan Redis untuk Cache data agar query berulang tidak menyentuh MySQL
CACHE_STORE=redis
CACHE_DRIVER=redis

# Menggunakan Redis untuk Session agar I/O session cepat dan mendukung auto-scale
SESSION_DRIVER=redis
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=.lapaktifikasi.com
SESSION_SECURE_COOKIE=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax

# Menggunakan Redis untuk Antrean (Asynchronous Background Jobs)
QUEUE_CONNECTION=redis

# File storage
FILESYSTEM_DISK=public

# ==============================================================================
# KONEKSI DATABASE MYSQL
# ==============================================================================
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=nama_database_produksi
DB_USERNAME=nama_user_database
DB_PASSWORD="GunakanPasswordKuatDiSini"

# Connection pooling & collation
DB_CHARSET=utf8mb4
DB_COLLATION=utf8mb4_unicode_ci

# ==============================================================================
# KONEKSI REDIS (PREDIS ATAU PHPREDIS)
# ==============================================================================
# Rekomendasi client: phpredis (C extension) atau predis (pure PHP)
REDIS_CLIENT=predis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Pisahkan database ID Redis agar cache dan session tidak saling menimpa
REDIS_DB=0          # Default / General
REDIS_CACHE_DB=1    # Cache data aplikasi
REDIS_SESSION_DB=2  # Session user
REDIS_QUEUE_DB=3    # Antrean background job

# ==============================================================================
# PWA & THEME CONFIGURATION
# ==============================================================================
HALLOWEEN_THEME=false
```

---

## 2. Penjelasan Driver Kunci

### A. Mengapa `CACHE_STORE=redis`?
Secara default, Laravel menggunakan `file` cache yang menulis file sementara ke `storage/framework/cache/data`. Pada traffic tinggi, jutaan I/O file kecil di hard drive/SSD menyebabkan antrean disk I/O bottleneck. Redis menyimpan cache murni di memori RAM berkecepatan mikrodetik.

### B. Mengapa `SESSION_DRIVER=redis`?
Session berbasis file mengalami disk lock saat user membuka banyak tab sekaligus (misalnya checkout, katalog, dashboard bersamaan). Redis menangani konkurensi multi-request secara instan tanpa file locking.

### C. Mengapa `QUEUE_CONNECTION=redis`?
Proses pengiriman email konfirmasi, notifikasi Telegram, Web Push notification, dan webhook payment gateway (Midtrans/Tripay) dieksekusi secara asinkron di background oleh worker Supervisor. User menerima respons HTML/JSON kurang dari 100 ms tanpa perlu menunggu email terkirim.

---

## 3. Verifikasi Koneksi Redis

Jalankan perintah ini di server untuk memastikan Redis terhubung dengan benar:

```bash
php artisan tinker --execute="Cache::put('ping', 'pong', 60); echo Cache::get('ping');"
```

Jika muncul tulisan `pong`, integrasi Redis Cache berhasil 100%.
