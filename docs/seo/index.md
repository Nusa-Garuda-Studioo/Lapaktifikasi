# SEO, AEO & GEO Master Index — Lapaktifikasi

Dokumen ini merupakan indeks kerja resmi optimasi mesin pencari tradisional (Google, Bing), Answer Engine Optimization (AEO), dan Generative Engine Optimization (GEO) untuk platform **Lapaktifikasi**.

- **Entitas:** Lapaktifikasi (Marketplace Produk Digital, Source Code & Akun Layanan Premium)
- **Status Pelaksanaan:** Implemented & Verified
- **Tanggal Audit & Rilis:** 2026-11-02
- **Laporan Ringkasan Pengiriman:** [delivery-summary.md](delivery-summary.md)
- **Berkas Pengetahuan AI Publik:** [`/llms.txt`](../../public/llms.txt) & [`/llms-full.txt`](../../public/llms-full.txt)

---

## Daftar Jalur Kerja & Hasil Eksekusi

| ID Langkah | Modul Spesialis | Status | Berkas Target / Komponen | Hasil Utama |
|---|---|---|---|---|
| STEP-01 | `seo-technical` | Completed | `public/robots.txt`, `partials/seo.blade.php`, `landing.blade.php` | Pembersihan URL lokal di robots.txt, izin bot AI crawler, perbaikan duplikasi title tag, penghapusan duplikasi `@stack('schema')`. |
| STEP-02 | `seo-aeo` & `seo-geo` | Completed | `public/llms.txt`, `public/llms-full.txt` | Standardisasi dokumentasi pengetahuan AI untuk ChatGPT, Claude, Perplexity; fakta entitas, katalog, dan garansi terverifikasi. |
| STEP-03 | `seo-schema` | Completed | `partials/seo.blade.php`, `welcome.blade.php`, `detail.blade.php`, `daftar_toko.blade.php`, `news/index.blade.php`, legalitas | Implementasi Organization (rich), OnlineStore, FAQPage, Product (MerchantListing & Instant Shipping), CollectionPage, ItemList, BreadcrumbList. |
| STEP-04 | `seo-sitemap` | Completed | `app/Http/Controllers/SitemapController.php` | Penambahan namespace `<image:image>`, tag gambar produk & berita otomatis, serta header discovery LLMs. |
| STEP-05 | `seo-content` & `seo-page` | Completed | `welcome.blade.php`, `daftar_toko.blade.php`, `news/index.blade.php` | Sinkronisasi teks HTML FAQ dengan schema JSON-LD, meta title & description unik pada direktori toko dan berita. |
| STEP-06 | `seo-commerce` | Completed | `premium_customer/detail.blade.php` | Penguatan skema MerchantListing, harga dalam IDR, garansi retur/replace, dan pengiriman instan gratis (0 IDR, 0 day). |
| STEP-07 | `seo-architecture` | Completed | Seluruh rute publik & internal link | Keterhubungan navigasi antara Beranda, Katalog, Direktori Toko, Berita, Pendaftaran Seller, dan Kemitraan Komunitas. |

---

## 27-Skill Coverage Ledger

Tinjauan 27 kapabilitas suite sesuai *Autonomous Orchestration Contract*:

1. **`seo` (Coordination & Router):** `completed` — Mengatur intake, penapisan 27 skill, orkestrasi internal, dan sintesis akhir tanpa membebani pengguna.
2. **`seo-research`:** `completed` — Target kata kunci transaksional ("beli akun premium murah", "jual source code web laravel", "marketplace produk digital indonesia").
3. **`ai-search-research`:** `completed` — Pemetaan data entitas Lapaktifikasi agar siap diekstrak oleh Perplexity, GPTBot, ClaudeBot, dan Gemini.
4. **`seo-performance`:** `completed` — Baseline meta tag, preconnect Google Fonts, lazy-loading gambar thumbnail, dan lightweight schema JSON-LD.
5. **`ai-visibility-monitor`:** `completed` — Standarisasi sumber rujukan (`/llms.txt`, `/llms-full.txt`) untuk pelacakan sitasi AI.
6. **`seo-audit`:** `completed` — Audit menyeluruh multi-lane (technical, on-page, sitemap, schema, content).
7. **`seo-page`:** `completed` — Penataan H1-H3, title tag tanpa duplikasi nama brand, dan meta description optimal (150-160 karakter).
8. **`seo-technical`:** `completed` — Konfigurasi `robots.txt`, XML sitemap header, canonical URLs, dan bot AI crawler directives.
9. **`seo-content`:** `completed` — Penyelarasan copy FAQ di landing page, teks edukasi produk, dan penjelasan garansi resmi.
10. **`seo-schema`:** `completed` — Skema terverifikasi Schema.org: Organization, WebSite, OnlineStore, FAQPage, Product, ItemList, CollectionPage, BreadcrumbList, NewsArticle.
11. **`seo-images`:** `completed` — Integrasi sitemap gambar (`<image:image>`), atribut `alt` kontekstual, dan `loading="lazy"`.
12. **`seo-sitemap`:** `completed` — XML sitemap dinamis (`/sitemap.xml`) dengan parameter lastmod, priority, changefreq, dan image metadata.
13. **`seo-hreflang`:** `completed` — Penegasan bahasa tunggal pasar Indonesia (`id-ID`, `inLanguage: id-ID`).
14. **`seo-plan`:** `completed` — Roadmap implementasi tuntas ke berkas produksi.
15. **`seo-programmatic`:** `completed` — URL slug dinamis untuk produk (`/toko/{store}/produk/{slug}-{id}`) dan toko (`/toko/{slug}`).
16. **`seo-competitor-pages`:** `not-applicable` — Lapaktifikasi tidak menggunakan halaman perbandingan merek kompetitor langsung saat ini.
17. **`seo-commerce`:** `completed` — Spesifikasi Offer, MerchantReturnPolicy, ShippingDetails (instant digital delivery), status ketersediaan InStock.
18. **`seo-local`:** `completed` — Penentuan PostalAddress (Bogor, Jawa Barat, ID), nomor kontak CS, dan mata uang IDR.
19. **`seo-video`:** `not-applicable` — Platform belum menggunakan video landing hosting publik.
20. **`seo-news-discover`:** `completed` — Penataan artikel di `/news/{slug}` dengan skema `NewsArticle` lengkap untuk potensi Google Discover.
21. **`seo-agentic`:** `completed` — Formulasi `/llms.txt` dan `/llms-full.txt` untuk pembacaan akurat oleh autonomous AI agents.
22. **`seo-architecture`:** `completed` — Struktur navigasi datar (flat hierarchy) dan breadcrumb jelas.
23. **`seo-authority`:** `completed` — Penyajian testimoni pelanggan, mitra industri resmi, dan informasi verifikasi seller.
24. **`seo-action-plan`:** `completed` — Tindakan konkret langsung diterapkan pada template Blade dan controller.
25. **`optimise-seo`:** `completed` — Perbaikan kode sumber langsung pada proyek.
26. **`seo-aeo`:** `completed` — Penyusunan jawaban langsung (direct answers) untuk pertanyaan bernilai konversi tinggi.
27. **`seo-geo`:** `completed` — Penyelarasan fakta entitas dan data klaim garansi untuk sitasi mesin generatif.
