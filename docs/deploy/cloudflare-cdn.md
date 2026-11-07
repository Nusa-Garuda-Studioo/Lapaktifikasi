# Panduan Konfigurasi Cloudflare & CDN untuk Lapaktifikasi

Jika domain `lapaktifikasi.com` berada di balik Cloudflare (Proxy Status: **Proxied / Orange Cloud**), terapkan konfigurasi berikut untuk memangkas LCP, memaksimalkan cache ratio, dan menghemat bandwidth server hingga 85%.

---

## 1. SSL/TLS Settings
* **Mode SSL/TLS**: Pilih **Full (Strict)**.
  * Pastikan server asal (Origin Server Nginx/Apache) memiliki sertifikat SSL yang valid (Let's Encrypt).
  * Jangan gunakan *Flexible* karena akan menyebabkan redirect loop pada Laravel `url()->to()`.
* **Always Use HTTPS**: **ON**.
* **Minimum TLS Version**: **TLS 1.2** (atau 1.3).
* **Automatic HTTPS Rewrites**: **ON**.

---

## 2. Speed & Optimization Settings

Masuk ke menu **Speed > Optimization**:

### A. Protocol & Compression
* **Brotli**: **ON** (Cloudflare akan mengompresi response dengan Brotli level 11 untuk client yang mendukung).
* **Early Hints (HTTP 103)**: **ON** (Cloudflare akan mengirimkan petunjuk preload font dan LCP image sebelum respons HTML server selesai di-generate).
* **HTTP/2 & HTTP/3 (with QUIC)**: **ON**.
* **0-RTT Connection Resumption**: **ON**.

### B. Image Optimization (Fitur Pro/Business jika tersedia)
* **Polish**: **Lossless** atau **WebP**.
* **Mirage**: **OFF** (dapat mengintervensi responsive `<picture>` dan lazy load native).

### C. Auto Minify & Rocket Loader
* **Auto Minify**:
  * JavaScript: **OFF** (Vite sudah melakukan minifikasi ES2020 via esbuild).
  * CSS: **ON**.
  * HTML: **ON**.
* **Rocket Loader**: **OFF**.
  * ⚠️ **PENTING**: Rocket Loader menunda eksekusi JavaScript dengan membungkus script ke sandbox worker. Hal ini dapat merusak inisialisasi PWA Service Worker (`sw.js`), token Livewire CSRF, dan Web Push registration.

---

## 3. Cache Rules (Page Rules / Cache Rules Modern)

Masuk ke menu **Caching > Cache Rules** dan buat aturan-aturan berikut sesuai urutan prioritas:

### Rule 1: Vite Immutable Hashed Assets (Cache Maksimal)
* **Rule Name**: `Vite Hashed Builds`
* **If incoming request matches**:
  * `URI Path starts with` `/build/`
* **Then**:
  * **Cache Eligibility**: `Eligible for cache`
  * **Edge Cache TTL**: `Override origin` > `1 year (31536000 seconds)`
  * **Browser Cache TTL**: `Override origin` > `1 year (31536000 seconds)`
  * **Cache Deception Armor**: `ON`

### Rule 2: Static Assets (Images, Fonts, CSS)
* **Rule Name**: `Static Public Assets`
* **If incoming request matches**:
  * `URI Path starts with` `/assets/` OR `URI Path starts with` `/css/`
* **Then**:
  * **Cache Eligibility**: `Eligible for cache`
  * **Edge Cache TTL**: `Override origin` > `1 month`
  * **Browser Cache TTL**: `Override origin` > `1 month`

### Rule 3: BYPASS PWA & Service Worker (Wajib Real-Time)
* **Rule Name**: `PWA Strict Bypass`
* **If incoming request matches**:
  * `URI Path equals` `/sw.js` OR `URI Path equals` `/manifest.json`
* **Then**:
  * **Cache Eligibility**: `Bypass cache` (Jangan pernah di-cache di Edge Cloudflare agar update PWA selalu terbaca).

### Rule 4: BYPASS Admin, Seller, Transaksi & Dinamis
* **Rule Name**: `Dynamic Application Bypass`
* **If incoming request matches**:
  * `URI Path starts with` `/admin` OR
  * `URI Path starts with` `/seller` OR
  * `URI Path starts with` `/dashboard` OR
  * `URI Path starts with` `/metode_pembayaran` OR
  * `URI Path starts with` `/api` OR
  * `URI Path starts with` `/webpush` OR
  * `URI Path starts with` `/livewire` OR
  * `Cookie contains` `lapaktifikasi_session`
* **Then**:
  * **Cache Eligibility**: `Bypass cache`

---

## 4. Header Early Hints (Link Preload)

Ketika Nginx mengirimkan header `Link`, Cloudflare otomatis mengonversinya menjadi **103 Early Hints** untuk browser modern:

Contoh respons Nginx untuk landing page:
```http
HTTP/2 103 Early Hints
Link: </assets/img/heroatas-920.webp>; rel=preload; as=image; type=image/webp; fetchpriority=high
Link: <https://fonts.googleapis.com>; rel=preconnect
Link: <https://fonts.gstatic.com>; rel=preconnect; crossorigin
```
Browser pengguna akan mulai men-download gambar hero LCP bahkan sebelum byte pertama dokumen HTML sampai ke HP client.
