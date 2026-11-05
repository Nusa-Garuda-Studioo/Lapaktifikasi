# 🛒 Lapaktifikasi Web - Marketplace & E-Commerce Produk Digital

[![Laravel](https://img.shields.io/badge/Laravel-13.x-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.2%20--%208.4-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://php.net)
[![Livewire](https://img.shields.io/badge/Livewire-v4.4-FB70A9?style=for-the-badge&logo=livewire&logoColor=white)](https://livewire.laravel.com)
[![Multi Gateway](https://img.shields.io/badge/Payment-Midtrans%20%7C%20TriPay%20%7C%20Pakasir-0099EE?style=for-the-badge)](https://lapaktifikasi.id)
[![PWA Ready](https://img.shields.io/badge/PWA-Ready-5A0FC8?style=for-the-badge&logo=pwa&logoColor=white)](https://web.dev/progressive-web-apps/)
[![License](https://img.shields.io/badge/License-MIT-green.svg?style=for-the-badge)](LICENSE)

**Lapaktifikasi Web** adalah platform *digital marketplace & e-commerce ekosistem terpadu* yang memfasilitasi transaksi jual-beli aneka produk digital: source code aplikasi, lisensi software, akun premium legal & bergaransi, e-book, voucher, serta creative assets. Platform ini didukung pengiriman instan otomatis (*instant delivery*), multi-payment gateway, WhatsApp invoice gateway, Web Push Notification, PWA, dan arsitektur RESTful API modern.

---

## 🌟 Fitur Utama Aplikasi

### 1. 🔑 Autentikasi & Multi-Role System
- **Role Terintegrasi**:
  - **Admin**: Kontrol sistem penuh, approval toko/seller, setting komisi, banner & web settings, broadcast, verifikasi tiket/laporan.
  - **Seller (Penjual)**: Dashboard seller, pengelolaan produk/layanan digital, manajemen inventory & varian, monitoring order, mutasi saldo & penarikan dana (withdraw).
  - **Customer (Pembeli)**: Dashboard customer, checkout instan multi-payment, riwayat pembelian, akses akun/file langsung, rating & ulasan produk, top up saldo internal.
- **Keamanan & OAuth**:
  - Login via Akun Google (**Laravel Socialite**).
  - Autentikasi token RESTful API via **Laravel Sanctum**.
  - Proteksi akun banned / status verification otomatis via custom middleware (`CheckBannedUser`).

### 2. 🏪 Manajemen Toko & Komunitas (Seller Ecosystem)
- **Storefront & Subdomain Komunitas**: Pendaftaran toko terverifikasi, halaman profil toko kustom, serta program kemitraan komunitas/sekolah.
- **Seller Badges**: Evaluasi performa dan badge reputasi seller otomatis via scheduled task (`EvaluateSellerBadges`).
- **Sistem Komisi Transparan**: Konfigurasi bagi hasil komisi platform dinamis per kategori produk atau seller.

### 3. 📦 Manajemen Produk Digital & Auto-Delivery
- **Produk Digital & Akun Premium**: Dukungan varian produk, tipe layanan, file ZIP download, serta lisensi/credential akun.
- **Automated Stock Inventory**: Sistem alokasi akun & kredensial otomatis kepada pembeli dalam hitungan detik setelah pembayaran terverifikasi.
- **Review & Ulasan**: Sistem rating bintang dan testimoni pembeli yang terverifikasi.

### 4. 💳 Multi Payment Gateway & Wallet Internal
- **Pilihan Payment Gateway Terintegrasi**:
  - **Midtrans**: Snap payment, QRIS, Virtual Account, GoPay, ShopeePay, Indomaret/Alfamart.
  - **TriPay**: Multi-channel payment closed/open transactions dengan rekonsiliasi otomatis (`ReconcileTriPayTransactions`).
  - **Pakasir**: Gateway pembayaran otomatis alternatif dengan auto-expiry monitoring (`ExpirePakasirTransactions`).
- **Dompet Digital (Internal Wallet)**: Saldo pembeli/penjual, riwayat mutasi saldo, audit rekonsiliasi saldo (`RepairBalancesCommand`), dan penarikan saldo seller.
- **Voucher & Promo**: Sistem kupon diskon persentase maupun nominal tetap untuk seller dan platform.

### 5. 🔔 Notifikasi Real-time, WhatsApp & PWA
- **WhatsApp Gateway (Fonnte API)**: Pengiriman struk / invoice dan kredensial akun instan langsung ke nomor WhatsApp pembeli melalui Queue Job (`SendAccountInvoiceWhatsapp`).
- **Web Push Notifications**: Notifikasi browser native via service worker menggunakan protokol VAPID (`minishlink/web-push`).
- **Progressive Web App (PWA)**: Dilengkapi `manifest.json`, icon splash lengkap, dan Service Worker (`sw.js`) untuk pengalaman installable app di Android, iOS, dan Desktop.

### 6. 🤖 Bot Telegram & Sistem Tiket Percakapan Interaktif
- **Integrasi Telegram Bot API**: Notifikasi kejadian tiket, penautan akun aman (*deep linking*), preferensi kanal notifikasi customer (`auto`, `telegram`, `whatsapp`), serta fallback otomatis ke WhatsApp jika bot diblokir.
- **Percakapan Tiket Dua Arah (Two-Way Thread)**: Sinkronisasi pesan antara Web dan Telegram (`tbl_laporan_pesan`), balasan langsung via quote reply atau tombol `[💬 Balas]`, serta dukungan upload lampiran gambar/PDF.
- **Wizard `/lapor` & Perintah Bot**: Pembuatan tiket komplain langsung dari Telegram untuk pesanan garansi aktif, serta perintah `/tiket`, `/batal`, `/putus`, dan `/bantuan`.
- **Tombol Aksi Interaktif Seller & Admin**: Tombol callback cepat `lp:{id}:{aksi}` untuk Seller (`ambil`, `mintainfo`, `ganti`, `selesai`) dan Admin (`ambil`, `tetapkan`, `status_proses`, `selesai`) dengan validasi otorisasi server-side dan update tampilan pesan realtime via `editMessageText`.
- **Monitoring SLA & Auto-Eskalasi ke Forum Topic**: Pengecekan SLA respon per 5 menit (`laporan:check-sla`), pengingat seller 60 menit sebelum jatuh tempo, dan eskalasi otomatis ke Forum Topic khusus per tiket di Supergroup Admin.
- **Audit Trail Status Tiket**: Pencatatan riwayat perubahan status tiket di `tbl_laporan_log` untuk transparansi penanganan kendala.

### 7. 📄 Laporan, Invoice & SEO
- **Cetak Invoice PDF**: Generator invoice dan receipt resmi dengan barcode/QR Code berbasis **Laravel DomPDF**.
- **SEO & Google Rich Snippets**: Schema.org OnlineStore & FAQPage JSON-LD, sitemap dinamis (`/sitemap.xml`), `robots.txt`, dan `llms.txt`.
- **Modul Berita & Pengumuman (News)**: CMS artikel edukasi dan berita platform untuk SEO organik.

---

## 🛠️ Stack Teknologi

| Komponen | Teknologi / Paket |
| :--- | :--- |
| **Framework Backend** | [Laravel 13.x](https://laravel.com/) (Modular Architecture) |
| **Bahasa Pemrograman** | PHP `^8.2` / `^8.3` / `8.4+` |
| **Frontend Framework** | Blade Templates, [Livewire v4.4](https://livewire.laravel.com/), HTML5, Vanilla JS |
| **Styling & Icons** | Custom Modular CSS (Landing, Dashboard, Navbar, Modal), Bootstrap Icons `1.11.x` |
| **Asset Bundler** | [Vite 4.x](https://vitejs.dev/) + `laravel-vite-plugin` |
| **Database & Cache** | MySQL / MariaDB, Redis (`predis/predis ^3.6`) |
| **Payment Gateways** | Midtrans (`midtrans/midtrans-php`), TriPay API, Pakasir API |
| **Autentikasi & API** | Laravel Sanctum `^4.0`, Laravel Socialite `^5.16` |
| **PDF & Dokumen** | `barryvdh/laravel-dompdf` (`^3.2`) |
| **QR Code Engine** | `simplesoftwareio/simple-qrcode` (`^4.2`) |
| **Notifikasi Web Push** | `minishlink/web-push` (`^11.0`) |
| **WhatsApp Gateway** | Fonnte API (`guzzlehttp/guzzle ^7.8`) |
| **Telegram Bot API** | Telegram Bot API Engine (`guzzlehttp/guzzle ^7.8`) |
| **PWA Engine** | Native Service Worker (`sw.js`) + Web App Manifest |

---

## 📋 Persyaratan Sistem

- **PHP**: `>= 8.2` (Direkomendasikan PHP 8.2, 8.3, atau 8.4)
  - Ekstensi wajib: `BCMath`, `Ctype`, `cURL`, `DOM`, `Fileinfo`, `GD`, `JSON`, `Mbstring`, `OpenSSL`, `PCRE`, `PDO`, `Tokenizer`, `XML`.
- **Database**: MySQL `>= 8.0` / MariaDB `>= 10.4`
- **Composer**: `>= 2.2`
- **Node.js**: `>= 18.x` & **NPM**
- **Web Server**: Nginx / Apache / Laragon

---

## 🚀 Panduan Instalasi Lokal

### 1. Clone Repository
```bash
git clone https://github.com/ikyy2001/Lapaktifikasi.git
cd lapaktifikasi_web
```

### 2. Install Dependensi PHP
```bash
composer install
```

### 3. Install Dependensi Frontend
```bash
npm install
npm run build
```

### 4. Konfigurasi Environment (`.env`)
Salin file konfigurasi dasar:
```bash
cp .env.example .env
```
Buka file `.env` dan sesuaikan kredensial berikut:
```env
APP_NAME=Lapaktifikasi
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=lapaktifikasi_db
DB_USERNAME=root
DB_PASSWORD=

# Redis (Opsional untuk Cache/Queue)
REDIS_CLIENT=predis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379

# Midtrans Payment Gateway
MIDTRANS_SERVER_KEY=your-midtrans-server-key
MIDTRANS_CLIENT_KEY=your-midtrans-client-key
MIDTRANS_IS_PRODUCTION=false

# TriPay Payment Gateway
TRIPAY_MODE=sandbox
TRIPAY_API_KEY=your-tripay-api-key
TRIPAY_PRIVATE_KEY=your-tripay-private-key
TRIPAY_MERCHANT_CODE=your-merchant-code

# WhatsApp Gateway (Fonnte)
FONNTE_TOKEN=your-fonnte-token

# Telegram Bot API
TELEGRAM_ENABLED=true
TELEGRAM_BOT_TOKEN=your-bot-token
TELEGRAM_BOT_USERNAME=your_bot_username
TELEGRAM_WEBHOOK_SECRET=your-random-webhook-secret
TELEGRAM_ADMIN_GROUP_ID=-1001234567890

# VAPID Web Push (Generate via web-push)
VAPID_PUBLIC_KEY=
VAPID_PRIVATE_KEY=
VAPID_SUBJECT=mailto:admin@lapaktifikasi.id
```

### 5. Generate App Key & Link Storage
```bash
php artisan key:generate
php artisan storage:link
```

### 6. Migrasi & Seeder Database
```bash
php artisan migrate --seed
```

### 7. Menjalankan Server & Queue Worker
Untuk menjalankan aplikasi secara lokal dengan fungsionalitas pengiriman invoice dan scheduled tasks:

**Terminal 1 (HTTP Server):**
```bash
php artisan serve
```
Akses aplikasi melalui browser di: `http://localhost:8000`

**Terminal 2 (Queue Worker untuk Notifikasi WhatsApp):**
```bash
php artisan queue:work
```

---

## ⏰ Cron Jobs & Scheduled Tasks

Sistem dilengkapi sejumlah command otomatis yang dapat dijadwalkan di *Server Crontab*:

| Command | Fungsi |
| :--- | :--- |
| `php artisan schedule:run` | Runner scheduler bawaan Laravel |
| `php artisan laporan:check-sla` | Cek SLA respon tiket, kirim reminder seller & auto-eskalasi ke grup admin |
| `php artisan seller:enforce-telegram` | Penegakan kewajiban penautan akun Telegram mitra seller |
| `php artisan telegram:set-webhook` | Mendaftarkan URL webhook bot Telegram ke API Telegram |
| `php artisan telegram:webhook-info` | Memeriksa status konfigurasi dan koneksi webhook Telegram |
| `php artisan seller:evaluate-badges` | Evaluasi reputasi seller & perolehan badge |
| `php artisan pakasir:expire-transactions` | Otomatis membatalkan transaksi Pakasir kedaluwarsa |
| `php artisan tripay:reconcile` | Rekonsiliasi status pembayaran TriPay menggantung |
| `php artisan balance:repair` | Audit & rekonsiliasi integritas saldo wallet pengguna |

Contoh konfigurasi Cron di server Linux:
```bash
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

---

## 📱 Dokumentasi RESTful API

Platform menyediakan RESTful API lengkap untuk aplikasi mobile Android/iOS menggunakan autentikasi **Bearer Token (Sanctum)**. 

Dokumentasi detail struktur endpoint, request payload, dan response format dapat dibaca di **[`api.md`](api.md)**.

Ringkasan endpoint utama:
- `POST /api/v1/auth/register` & `POST /api/v1/auth/login` - Registrasi & Autentikasi Pengguna
- `GET /api/v1/catalog` - Katalog produk digital & filter kategori
- `POST /api/v1/checkout` - Inisialisasi transaksi & request payment token
- `POST /api/v1/midtrans/notification` & `POST /api/v1/tripay/callback` - Webhook callback pembayaran
- `GET /api/v1/seller/inventory` - Manajemen stok & kredensial akun digital seller

---

## 📁 Struktur Direktori Proyek

```text
lapaktifikasi_web/
├── app/
│   ├── Console/Commands/       # Custom Artisan Commands (Repair, Badges, Expiry)
│   ├── Http/
│   │   ├── Controllers/        # Controller Logika Bisnis (Admin, Seller, Customer, API)
│   │   ├── Middleware/         # Role, Admin/Seller Check, Banned User Guard
│   │   └── Requests/           # Form Request Validation
│   ├── Jobs/                   # Asynchronous Queue Jobs (Send WhatsApp Invoice)
│   └── Models/                 # Eloquent Models & Relasi Database
├── bootstrap/                  # Inisialisasi framework & providers (Laravel 13)
├── config/                     # Konfigurasi aplikasi, mail, database, & services
├── database/
│   ├── factories/              # Factory data testing
│   ├── migrations/             # Struktur skema tabel database
│   └── seeders/                # Data awal sistem & user role
├── public/
│   ├── assets/                 # Gambar, logo, ikon brand & hero banners
│   ├── css/ & js/              # Modular styling & landing scripts
│   ├── manifest.json & sw.js   # PWA manifest & Service Worker
│   └── index.php               # Front controller aplikasi
├── resources/
│   └── views/                  # Blade templates (Landing, Admin, Seller, Customer)
├── routes/
│   ├── api.php                 # Rute RESTful API (Sanctum)
│   └── web.php                 # Rute web portal & transaksi
├── storage/                    # Storage invoice PDF, avatar, & log error
├── api.md                      # Dokumentasi lengkap RESTful API
├── composer.json               # Dependensi PHP & Laravel package
└── README.md                   # Dokumentasi resmi proyek
```

---

## 📄 Lisensi

Proyek ini dilindungi di bawah lisensi [MIT License](LICENSE).
