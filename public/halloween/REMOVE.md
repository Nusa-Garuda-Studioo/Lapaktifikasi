# Panduan Pembersihan Dekorasi & Tema Halloween (Lapaktifikasi)

Dokumen ini berisi informasi lengkap mengenai seluruh file, aset visual, lisensi, dan prosedur penonaktifan atau pembersihan tema Halloween pada Lapaktifikasi.

---

## 1. Daftar Lengkap File yang Dibuat

Semua kode dan aset baru terisolasi sepenuhnya di dalam satu folder khusus: `public/halloween/`.

| File Path | Deskripsi | Ukuran |
| :--- | :--- | :--- |
| `public/halloween/halloween.css` | Stylesheet tema lengkap & dekorasi (scoped di bawah `body.halloween-theme`, prefix `halloween-` / `--halloween-`) | ~9.8 KB |
| `public/halloween/halloween.js` | Logika aktivasi tema (`HALLOWEEN_ENABLED`), injeksi environmental layers & ornamen tiap section | ~8.9 KB |
| `public/halloween/assets/halloween-full-moon.webp` | Bulan purnama bercahaya resolusi tinggi untuk langit Hero | ~24.2 KB |
| `public/halloween/assets/halloween-fog.webp` | Tekstur kabut malam berulang (*seamless drifting mist*) | ~11.5 KB |
| `public/halloween/assets/halloween-bat-silhouette.webp` | Siluet kelelawar terbang untuk kawanan & Hero | ~2.8 KB |
| `public/halloween/assets/halloween-ghost.webp` | Siluet hantu transparan melayang lembut di section Fitur/Visi Misi | ~1.6 KB |
| `public/halloween/assets/halloween-skull.webp` | Ilustrasi tengkorak artistik klasik di section Testimoni/Cara Kerja | ~8.2 KB |
| `public/halloween/assets/halloween-candle.webp` | Lilin transparan menyala temaram di section Stats | ~3.1 KB |
| `public/halloween/assets/halloween-pumpkin-corner.webp` | Labu ukir artistik bercahaya di sudut Footer & CTA | ~21.8 KB |
| `public/halloween/assets/halloween-spiderweb-corner.webp` | Jaring laba-laba transparan sudut atas layar & kartu | ~13.6 KB |
| `public/halloween/assets/halloween-cursor.png` | Kursor kustom 32x32 transparan labu Halloween | ~0.4 KB |
| `public/halloween/REMOVE.md` | Panduan dokumentasi dan prosedur pembersihan ini | ~4.5 KB |

### Informasi Sumber & Lisensi Aset Gambar
Semua gambar visual adalah gambar transparan asli berlisensi bebas / Public Domain / CC0 (tanpa emoji, icon font, atau SVG icon generik):
1. **`halloween-full-moon.webp`**
   - **Sumber**: NASA Galileo spacecraft / Wikimedia Commons ([File:Full moon.png](https://commons.wikimedia.org/wiki/File:Full_moon.png))
   - **Lisensi**: Public Domain.
2. **`halloween-ghost.webp`**
   - **Sumber**: Kurtkaiser / Wikimedia Commons ([File:Ghost front.png](https://commons.wikimedia.org/wiki/File:Ghost_front.png))
   - **Lisensi**: Creative Commons Zero (CC0) / Public Domain Dedication.
3. **`halloween-skull.webp`**
   - **Sumber**: F. A. Brockhaus / Wikimedia Commons ([File:Skull icon.png](https://commons.wikimedia.org/wiki/File:Skull_icon.png))
   - **Lisensi**: Public Domain (Bebas hak cipta / pra-1911).
4. **`halloween-candle.webp`**
   - **Sumber**: William Warby / Perquirius via Wikimedia Commons ([File:White candle (transparent).png](https://commons.wikimedia.org/wiki/File:White_candle_%28transparent%29.png))
   - **Lisensi**: Creative Commons Attribution 2.0 (CC BY 2.0).
5. **`halloween-bat-silhouette.webp`**
   - **Sumber**: Ryan Kissinger, National Institute of Allergy and Infectious Diseases (NIAID NIH BioArt) via Wikimedia Commons ([File:Bat Silhouette](https://commons.wikimedia.org/wiki/File:Bat_Silhouette_(NIH_BioArt_49_-_627853).png))
   - **Lisensi**: Public Domain (Karya pemerintah federal AS / NIH).
6. **`halloween-pumpkin-corner.webp`**
   - **Sumber**: Bayerischer / Wikimedia Commons ([File:Pumpkin.png](https://commons.wikimedia.org/wiki/File:Pumpkin.png))
   - **Lisensi**: Creative Commons Zero (CC0) / Public Domain Dedication.
7. **`halloween-spiderweb-corner.webp`**
   - **Sumber**: Pixabay / Wikimedia Commons ([File:Corner cobwebs.png](https://commons.wikimedia.org/wiki/File:Corner_cobwebs.png))
   - **Kreator**: Clker-Free-Vector-Images
   - **Lisensi**: Creative Commons Zero (CC0) / Public Domain Dedication.
8. **`halloween-fog.webp` & `halloween-cursor.png`**
   - Dibuat khusus (*custom-crafted*) untuk proyek ini, berlisensi bebas.

---

## 2. Daftar File Existing yang Diubah

Hanya **1 (satu)** file existing yang disentuh:
- **`resources/views/layouts/landing.blade.php`**
  - **Baris yang ditambahkan**:
    ```html
    <!-- HALLOWEEN:START -->
    <link rel="stylesheet" href="{{ asset('halloween/halloween.css') }}">
    <script src="{{ asset('halloween/halloween.js') }}" defer></script>
    <!-- HALLOWEEN:END -->
    ```
  - **Lokasi**: Di dalam tag `<head>`, tepat sebelum penutup `</head>`.

---

## 3. Langkah Menghapus / Menonaktifkan Tema

### Cara A: Cepat (Nonaktifkan Sementara Tanpa Menghapus File)
Buka file `public/halloween/halloween.js`, ubah baris ke-7:
```javascript
const HALLOWEEN_ENABLED = false;
```
Saat disetel ke `false`, class `halloween-theme` pada body dan seluruh elemen ornamen tidak akan dimuat sama sekali, sehingga tampilan kembali 100% normal.

### Cara B: Bersih Total (Menghapus Seluruh Jejak Fitur)
1. Hapus folder `public/halloween/`:
   - Di PowerShell:
     ```powershell
     Remove-Item -Recurse -Force "public/halloween"
     ```
   - Di Bash/Linux:
     ```bash
     rm -rf public/halloween
     ```
2. Buka file `resources/views/layouts/landing.blade.php`, lalu hapus 4 baris blok penanda berikut:
   ```html
   <!-- HALLOWEEN:START -->
   <link rel="stylesheet" href="{{ asset('halloween/halloween.css') }}">
   <script src="{{ asset('halloween/halloween.js') }}" defer></script>
   <!-- HALLOWEEN:END -->
   ```

---

## 4. Perintah Verifikasi (Memastikan Bersih Tanpa Sisa)

Jalankan perintah berikut untuk memastikan tidak ada sisa kode bertema Halloween yang tertinggal di proyek:

### PowerShell:
```powershell
Get-ChildItem -Recurse -File | Select-String -Pattern "halloween" -CaseSensitive:$false
```

### Bash / Linux / Git Bash:
```bash
git grep -i "halloween"
```

*Jika dekorasi telah dihapus dengan benar, perintah di atas tidak akan menghasilkan output apa pun.*
