# UX Improvement Plan — Lapaktifikasi

> **Status:** Menunggu approval sebelum implementasi  
> **Scope:** UX (flow, button grouping, navigasi, feedback) — BUKAN visual redesign  
> **Constraint:** Semua fungsi yang ada harus tetap tersedia, tanpa migration/wipe DB

---

## Temuan Langsung dari Kode

Berikut masalah konkret yang ditemukan, bukan asumsi:

### 🔴 P0 — Langsung menyebabkan user bingung atau tidak bisa lanjut

| # | Halaman | Masalah |
|---|---------|---------|
| 1 | `produk/beli.blade.php` | Form checkout tanpa ringkasan pesanan: field `nama_produk`, `deskripsi`, `qty`, `harga` semua `readonly` tapi ditampilkan seperti input aktif. User tidak tahu ini hanya review. |
| 2 | `produk/beli.blade.php` | Tombol "Kembali" (merah) dan "Checkout" (hijau) tidak sejajar dan bergaya berlawanan tanpa hierarki yang jelas — user sering salah klik |
| 3 | `premium_customer/detail.blade.php` | Tombol "Masukkan Keranjang" (`addToCartMock()`) adalah mock — tidak ada fungsi keranjang sesungguhnya. User klik, tidak ada yang terjadi. |
| 4 | `premium_customer/riwayat.blade.php` | Per baris transaksi sukses: 3 tombol berderet (`Akun`/`Download`, `Invoice`, `Ulas`) tanpa pemisah hierarki. Kolom aksi min-width 260px tapi masih overflow di tablet. |
| 5 | `auth/login.blade.php` → topbar | Tombol Login di topbar (`Login`) mengarah ke `/login` ✅ tapi landing page hero CTA "Daftar Akun Gratis" mengarah ke `/pendaftaran` — perlu cek apakah ada inkonsistensi redirect setelah login berhasil per role |

### 🟡 P1 — Menurunkan kenyamanan signifikan

| # | Halaman | Masalah |
|---|---------|---------|
| 6 | `premium_customer/katalog.blade.php` | Category filter (btn-group Bootstrap) dan search bar ada di baris berbeda tapi tidak terhubung secara visual. Filter kategori kehilangan state saat search, dan sebaliknya. |
| 7 | `premium_customer/katalog.blade.php` | Profil toko muncul di atas katalog hanya ketika `$toko` ada. Saat tidak ada toko, header langsung loncat ke produk tanpa context separator. |
| 8 | `seller/dashboard.blade.php` | Banner warning Telegram (blocked/unlinked) panjang dan berulang di setiap load, menumpuki stat cards. User tidak bisa dismiss banner ini. |
| 9 | `partials/layout/sidebar_seller.blade.php` | Sidebar seller 6 section: GENERAL, SELLER MENU, PRODUK SELLER, INVENTARIS, PENGATURAN. Terlalu banyak label section untuk navigasi sederhana — kognitif overhead. |
| 10 | Mobile (semua halaman dengan layout.blade.php) | Sidebar 250px fixed, tidak ada mobile hamburger. Layout hanya pakai `hidden lg:block` — di mobile, tidak ada navigasi sama sekali kecuali dari topbar dropdown. |
| 11 | `welcome.blade.php` | Hero section ada 2 CTA (Primary + Secondary) yang sudah bagus. Tapi section Visi/Misi hover-only tidak accessible di mobile (hover tidak bekerja di touchscreen). |

### 🟢 P2 — Nice to have, dampak rendah

| # | Halaman | Masalah |
|---|---------|---------|
| 12 | `premium_customer/riwayat.blade.php` | Tab filter status (Semua, Pending, Sukses, Gagal) pakai inline style berulang per `<a>` tag, bukan class — sulit maintain dan inkonsisten dengan desain sistem lain. |
| 13 | Semua form | Pesan error validasi Laravel (`@error`) tidak ada di semua form yang diperiksa — error hanya lewat SweetAlert dari session, tidak ada inline field error. |
| 14 | `produk/show.blade.php` | Halaman screenshot produk (carousel) pakai Bootstrap 4 lama tanpa breadcrumb, tombol Kembali merah `btn-danger` tidak konsisten. |

---

## Rencana Implementasi

Urutan: P0 dulu → P1 → P2. Tidak ada yang dihapus, hanya reorganized/grouped.

---

### Fase 1 — P0: Flow-Breaker Fixes (Prioritas Tertinggi)

#### F1.1 — Checkout (`produk/beli.blade.php`)
**Masalah:** Form input readonly terasa seperti form aktif, tombol tidak jelas hierarkinya.

**Solusi:**
- Ubah tampilan field `readonly` dari `<input>` menjadi blok summary (label + value, bukan input field). Tidak ada perubahan fungsi POST — hanya tampilan.
- Tombol "Kembali" pindah ke kiri, "Checkout" di kanan. Tambahkan `disabled` state saat profil belum lengkap (sekarang tombol hilang — lebih baik muncul tapi disabled dengan tooltip "Lengkapi profil dulu").

**File:** `resources/views/produk/beli.blade.php`

---

#### F1.2 — Tombol Mock Keranjang (`premium_customer/detail.blade.php`)
**Masalah:** `addToCartMock()` tidak melakukan apa-apa. User klik → tidak ada respons → kebingungan.

**Solusi:** Dua opsi (pilih salah satu):
- **Opsi A (aman):** Sembunyikan tombol "Masukkan Keranjang" sampai fitur keranjang sesungguhnya ada. Tombol "Beli Sekarang" jadi satu-satunya CTA.
- **Opsi B:** Ganti mock menjadi toast informatif: "Fitur keranjang akan hadir segera — langsung beli sekarang untuk memesan."

> [!IMPORTANT]
> Saya tidak akan menghapus fungsi apapun. Opsi A hanya menyembunyikan tombol yang saat ini tidak berfungsi. Konfirmasi opsi mana yang diinginkan.

**File:** `resources/views/premium_customer/detail.blade.php`

---

#### F1.3 — Tombol Bertumpuk di Riwayat (`premium_customer/riwayat.blade.php`)
**Masalah:** Per baris: `Akun`, `Invoice`, `Ulas` — 3 tombol sejajar tanpa hierarki.

**Solusi:**
- **Desktop:** Tombol utama `Akun`/`Download` tetap primary (btn-dark). `Invoice` dan `Ulas` dimasukkan ke dropdown `•••` (Bootstrap dropdown kecil) di kanan tombol utama.
- **Mobile:** Sudah ada card terpisah. Tombol utama full-width, lainnya di bawahnya lebih kecil (outline).
- Semua link tetap ada — hanya grouping visual.

**File:** `resources/views/premium_customer/riwayat.blade.php`

---

### Fase 2 — P1: Navigation & Layout

#### F2.1 — Mobile Navigation (Hamburger Menu)
**Masalah:** Sidebar `hidden lg:block` — di mobile tidak ada navigasi.

**Solusi:**
- Tambahkan hamburger button di topbar (sudah ada topbar, tambahkan di kiri search bar).
- Sidebar menjadi offcanvas/drawer saat mobile: overlay dengan close button.
- Ini **CSS + sedikit JS vanilla** — tidak perlu library baru. Bootstrap 4 sudah ada `data-toggle` offcanvas.

**File:** `resources/views/partials/layout/topbar.blade.php`, tambah partial `sidebar_mobile_toggle.blade.php`.

---

#### F2.2 — Seller Sidebar: Collapsible Sections
**Masalah:** 6 section label untuk ~12 item navigasi — terlalu padat.

**Solusi:**
- Merge "PRODUK SELLER" + "INVENTARIS" menjadi satu section `PRODUK & STOK` yang bisa di-collapse.
- Section yang aktif (berdasarkan `Request::path()`) otomatis terbuka.
- Tidak ada item yang dihapus.

**File:** `resources/views/partials/layout/sidebar_seller.blade.php`

---

#### F2.3 — Seller Dashboard: Dismissible Telegram Banner
**Masalah:** Banner warning Telegram tidak bisa di-dismiss, menumpuki konten utama.

**Solusi:**
- Tambahkan tombol × (dismiss) di kanan banner.
- State dismiss simpan di `sessionStorage` (bukan localStorage — reset setiap sesi, agar user masih diingatkan).
- Jika toko sudah nonaktif karena Telegram, banner tidak bisa di-dismiss (terlalu penting).

**File:** `resources/views/seller/dashboard.blade.php`

---

#### F2.4 — Katalog: Filter + Search Terhubung
**Masalah:** Category filter dan search terpisah — filter hilang saat search.

**Solusi:**
- Wrap keduanya dalam satu `<form>` dengan `method="GET"`.
- Kategori aktif tersimpan sebagai `<input type="hidden">` saat user submit search.
- Ini sudah hampir ada di kode (`request('kategori')` sebagai hidden input) tapi tidak konsisten di semua path.

**File:** `resources/views/premium_customer/katalog.blade.php`

---

#### F2.5 — Landing Page: Visi/Misi Mobile Fix
**Masalah:** Hover-reveal tidak bekerja di touchscreen.

**Solusi:**
- Ubah interaksi dari `hover` ke `click/tap` untuk mobile: tambahkan tombol "Lihat Detail" kecil di bawah judul kartu.
- Desktop tetap hover seperti sekarang.
- Pure CSS dengan `@media (hover: none)` — tidak perlu JS.

**File:** `resources/views/welcome.blade.php`, `public/css/pages/welcome.css`

---

#### F2.6 — Breadcrumb Universal
**Masalah:** User tidak tahu posisinya. Hanya `detail.blade.php` yang ada breadcrumb.

**Solusi:**
- Tambahkan breadcrumb sederhana (1 baris teks: `Beranda > Katalog > [Nama Produk]`) di semua halaman yang belum punya.
- Halaman yang perlu: `katalog.blade.php`, `riwayat.blade.php`, `beli.blade.php`, `seller/profil.blade.php`.
- Gunakan partial `@include('partials.breadcrumb', ['items' => [...]])` agar konsisten.

**File:** Buat `resources/views/partials/breadcrumb.blade.php`, update halaman-halaman di atas.

---

#### F2.7 — Pesan Error Inline Form
**Masalah:** Error validasi hanya lewat SweetAlert dari session — tidak ada inline field error.

**Solusi:**
- Tambahkan `@error('field')` di bawah setiap field form yang kritis (login, pendaftaran, profil customer, profil seller).
- SweetAlert untuk error sukses/gagal global tetap ada.
- Hanya tambahkan — tidak mengubah logika validasi backend.

**File:** `resources/views/auth/login.blade.php`, `auth/pendaftaran.blade.php`, `customer/index.blade.php`, `seller/profil.blade.php`.

---

### Fase 3 — P2: Polish

#### F3.1 — Riwayat Tab: Pindahkan Inline Style ke Class
Ekstrak styling tab status ke CSS file `customer-history.css` yang sudah ada.

#### F3.2 — Screenshot Produk: Breadcrumb + Tombol Konsisten
`produk/show.blade.php`: tambahkan breadcrumb, ubah tombol "Kembali" dari `btn-danger` ke `btn-outline-dark`.

#### F3.3 — `produk/beli.blade.php` (Legacy Produk)
Halaman ini adalah flow lama (bukan premium). Pastikan tombol Kembali mengarah ke URL yang benar berdasarkan context (dari mana user datang).

---

## File yang Akan Diubah

```
resources/views/
  produk/beli.blade.php                      ← F1.1, F3.3
  premium_customer/
    detail.blade.php                          ← F1.2
    katalog.blade.php                         ← F2.4
    riwayat.blade.php                         ← F1.3, F3.1
  partials/layout/
    topbar.blade.php                          ← F2.1
    sidebar_seller.blade.php                  ← F2.2
    breadcrumb.blade.php                      ← F2.6 (BARU)
  seller/dashboard.blade.php                  ← F2.3
  welcome.blade.php                           ← F2.5
  auth/login.blade.php                        ← F2.7
  auth/pendaftaran.blade.php                  ← F2.7
  customer/index.blade.php                    ← F2.7
  seller/profil.blade.php                     ← F2.7
  produk/show.blade.php                       ← F3.2

public/css/pages/
  welcome.css                                 ← F2.5
```

**Tidak ada perubahan controller, route, migration, atau logika backend.**

---

## Yang TIDAK Akan Diubah

- Warna, font, komponen visual yang sudah ada
- Logika controller/service apapun
- Route/middleware
- Database/migration
- Admin panel (diabaikan per permintaan)
- Fungsi apapun yang saat ini bekerja

---

## Pertanyaan Terbuka Sebelum Eksekusi

> [!IMPORTANT]
> Butuh keputusan dari kamu sebelum mulai:

1. **F1.2 Tombol Keranjang** — Opsi A (sembunyikan tombol) atau Opsi B (toast informasi)?
2. **F2.1 Mobile Nav** — Apakah ada halaman spesifik yang sudah punya hamburger/mobile menu yang harus jadi referensi? Atau bebas implementasi selama konsisten dengan Bootstrap 4?
3. **F2.6 Breadcrumb** — Halaman publik (landing, daftar toko) juga perlu breadcrumb, atau hanya halaman dalam-app?
4. **Urutan fase** — Eksekusi Fase 1 dulu lalu report, baru lanjut Fase 2? Atau semua sekaligus?

---

## Patokan Sukses

- Semua tombol utama dapat ditemukan dalam 2 klik
- Tidak ada tombol yang klik tapi tidak terjadi apa-apa tanpa penjelasan
- Navigasi mobile tersedia (hamburger/drawer)
- Filter + search terhubung di katalog
- Status pesanan jelas terbaca di riwayat tanpa scan seluruh baris
- Error form langsung terlihat di field yang salah, bukan hanya di popup
