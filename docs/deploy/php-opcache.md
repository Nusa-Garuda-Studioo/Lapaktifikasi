# Rekomendasi Konfigurasi PHP OPcache & JIT untuk Lapaktifikasi

OPcache mengompilasi kode PHP menjadi bytecode dan menyimpannya langsung di memori RAM server, meniadakan pembacaan file PHP dari disk pada setiap request. Di lingkungan produksi, OPcache wajib diaktifkan dengan timestamp checking dimatikan (`validate_timestamps = 0`).

---

## 1. Lokasi File Konfigurasi `php.ini`

Tergantung instalasi OS dan versi PHP (PHP 8.2 / 8.3 / 8.4):
* **Ubuntu / Debian (PHP-FPM)**: `/etc/php/8.3/fpm/conf.d/10-opcache.ini` atau `/etc/php/8.3/fpm/php.ini`
* **Ubuntu / Debian (CLI)**: `/etc/php/8.3/cli/conf.d/10-opcache.ini`
* **CentOS / RHEL / AlmaLinux**: `/etc/php.d/10-opcache.ini`

---

## 2. Snippet Konfigurasi Produksi yang Direkomendasikan

Salin konfigurasi berikut ke bagian `[opcache]` di file `php.ini` atau `10-opcache.ini`:

```ini
[opcache]
; Mengaktifkan OPcache untuk web server
opcache.enable = 1

; Mengaktifkan OPcache untuk CLI (berguna untuk queue worker & cron)
opcache.enable_cli = 1

; Alokasi memori RAM untuk bytecode PHP (rekomendasi: 256MB atau 512MB)
opcache.memory_consumption = 256

; Alokasi memori untuk string internal/statis (variabel nama method, class, function)
opcache.interned_strings_buffer = 16

; Jumlah maksimum file PHP yang dapat disimpan dalam hash table (rekomendasi: 20000 - 30000)
; Laravel + vendor memiliki sekitar 10.000 - 15.000 file
opcache.max_accelerated_files = 30000

; PERSENTASE MEMORI YANG TERBUANG SEBELUM RESTART
opcache.max_wasted_percentage = 10

; SANGAT PENTING UNTUK PRODUKSI: 
; Matikan validasi timestamp file. PHP tidak akan memeriksa ke disk apakah file diubah.
; Meningkatkan throughput CPU hingga 30-50%.
opcache.validate_timestamps = 0

; Jika validate_timestamps = 1 (mode dev/staging), periksa setiap N detik
opcache.revalidate_freq = 0

; Tetap simpan comment untuk anotasi Livewire, PHP 8 Attributes, dan doctrine docblock
opcache.save_comments = 1

; Cepat mematikan worker saat reload
opcache.fast_shutdown = 1

; Lindungi dari race condition saat update file
opcache.file_update_protection = 0

; ==============================================================================
; PHP 8 JIT (JUST-IN-TIME COMPILER)
; ==============================================================================
; Ukuran buffer memory untuk mesin JIT (rekomendasi: 128M - 256M)
opcache.jit_buffer_size = 128M

; Mode JIT Tracing (Paling optimal untuk web framework berbasis request seperti Laravel)
; 1255 = JIT Tracing Mode
opcache.jit = 1255
```

---

## 3. Penjelasan Parameter Kritis

| Parameter | Nilai Rekomendasi | Alasan & Manfaat Performa |
| :--- | :--- | :--- |
| `opcache.enable` | `1` | Mengaktifkan cache bytecode PHP ke RAM. |
| `opcache.memory_consumption` | `256` | Memastikan seluruh framework Laravel, package vendor, dan custom code muat di RAM tanpa terjadi cache thrashing / purge. |
| `opcache.max_accelerated_files` | `30000` | Jumlah file yang di-cache. Laravel 13 + Livewire + vendor melampaui batas default PHP (10.000). Nilai 30.000 memastikan 100% file ter-cache. |
| `opcache.validate_timestamps` | `0` | **Kunci Performa**: Mencegah ribuan syscall `stat()` ke disk pada setiap request. File dibaca murni dari RAM. |
| `opcache.save_comments` | `1` | Diperlukan agar PHP 8 Attributes dan anotasi Livewire / Sanctum tetap berfungsi normal. |
| `opcache.jit` | `1255` | Mengaktifkan tracing JIT untuk mengompilasi hot bytecode langsung ke instruksi mesin CPU. |

---

## 4. Cara Me-reload OPcache Setelah Deployment Baru

Karena `opcache.validate_timestamps = 0`, PHP tidak akan otomatis mendeteksi perubahan file saat Anda melakukan `git pull`.

Oleh karena itu, **setiap deployment baru wajib me-reload PHP-FPM**:

```bash
# Untuk Ubuntu / Debian PHP 8.3:
sudo systemctl reload php8.3-fpm

# Atau jika menggunakan PHP 8.2:
sudo systemctl reload php8.2-fpm

# Jika menggunakan cPanel / AlmaLinux:
sudo systemctl reload ea-php82-php-fpm || sudo systemctl reload ea-php83-php-fpm
```

*Perintah `reload` tidak akan memutuskan koneksi aktif yang sedang berjalan (zero downtime).*

---

## 5. Cara Verifikasi Status OPcache

Buat file sementara (atau gunakan Laravel Tinker) untuk memeriksa:

```bash
php -r "print_r(opcache_get_status(false));"
```

Pastikan:
* `opcache_enabled` bernilai `true`.
* `cache_full` bernilai `false`.
* `jit.enabled` bernilai `true`.
