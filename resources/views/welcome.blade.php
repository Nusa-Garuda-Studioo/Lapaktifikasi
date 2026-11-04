@extends('layouts.landing')

@section('title', (isset($websiteSettings) ? $websiteSettings->site_name : 'Lapaktifikasi') . ' - Marketplace Produk Digital & Akun Premium')
@section('meta_title', (isset($websiteSettings) ? $websiteSettings->site_name : 'Lapaktifikasi') . ' - Marketplace Produk Digital, Source Code & Akun Premium')
@section('meta_description', 'Platform marketplace produk digital, source code aplikasi, dan akun premium murah terpercaya. Pengiriman otomatis instan kurang dari 5 detik, bergaransi resmi, dan didukung payment gateway terpercaya.')
@section('og_image', isset($websiteSettings) && $websiteSettings->logo_path ? asset($websiteSettings->logo_path) : asset('assets/img/brand_ambassador.jpg'))

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/pages/welcome.css') }}?v={{ file_exists(public_path('css/pages/welcome.css')) ? filemtime(public_path('css/pages/welcome.css')) : time() }}">
@endpush

@push('schema')
<!-- Schema.org OnlineStore Structured Data -->
<script type="application/ld+json">
{
  "{{ '@context' }}": "https://schema.org",
  "@type": "OnlineStore",
  "name": "{{ isset($websiteSettings) && $websiteSettings->site_name ? $websiteSettings->site_name : 'Lapaktifikasi' }}",
  "url": "{{ url('/') }}",
  "description": "Marketplace Produk Digital, Source Code Aplikasi & Akun Layanan Premium Bergaransi Resmi.",
  "currenciesAccepted": "IDR",
  "paymentAccepted": "QRIS, Bank Transfer, Virtual Account, GoPay, OVO, Dana, ShopeePay",
  "priceRange": "Rp 5.000 - Rp 1.500.000",
  "hasMerchantReturnPolicy": {
    "@type": "MerchantReturnPolicy",
    "applicableCountry": "ID",
    "returnPolicyCategory": "https://schema.org/MerchantReturnFiniteReturnWindow",
    "merchantReturnDays": 30,
    "returnMethod": "https://schema.org/ReturnOnline",
    "returnFees": "https://schema.org/FreeReturn"
  }
}
</script>

<!-- Schema.org FAQPage Structured Data (Google Rich Results SERP) -->
<script type="application/ld+json">
{
  "{{ '@context' }}": "https://schema.org",
  "@type": "FAQPage",
  "mainEntity": [
    {
      "@type": "Question",
      "name": "Apakah akun premium yang dijual di Lapaktifikasi aman dan legal?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "Ya, seluruh produk yang kami jual merupakan akun premium yang aman untuk digunakan. Kami berkomitmen untuk menjaga keamanan dan kenyamanan setiap pelanggan dengan sistem yang terverifikasi."
      }
    },
    {
      "@type": "Question",
      "name": "Berapa lama waktu yang dibutuhkan untuk menerima akun setelah pembayaran?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "Sistem kami memproses pengiriman secara otomatis. Setelah pembayaran Anda dikonfirmasi oleh Payment Gateway, kredensial akun akan langsung tersedia di halaman riwayat belanja dalam hitungan detik — umumnya kurang dari 5 detik."
      }
    },
    {
      "@type": "Question",
      "name": "Metode pembayaran apa saja yang tersedia?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "Kami menyediakan berbagai metode pembayaran terverifikasi termasuk QRIS, transfer bank (BCA, BNI, BRI, Mandiri), e-wallet (GoPay, OVO, Dana, ShopeePay), dan Virtual Account melalui 3 payment gateway terpercaya (Midtrans, Duitku, Pakasir)."
      }
    },
    {
      "@type": "Question",
      "name": "Apa yang terjadi jika akun yang saya beli bermasalah?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "Kepuasan pelanggan adalah prioritas kami. Jika Anda mengalami masalah dengan akun yang dibeli, segera hubungi tim dukungan kami melalui WhatsApp atau menu Laporan, dan kami akan memberikan garansi replace/refund sesuai ketentuan."
      }
    }
  ]
}
</script>
@endpush

@section('content')
    <!-- HERO -->
    <header id="hero">
        <div class="hero-inner">
            <div class="hero-content">
                <!-- <div class="hero-badge"><i class="bi bi-patch-check-fill"></i> Platform Ekosistem Digital &amp; Kreatif Terpercaya</div> -->
                <h1 class="hero-title">Marketplace Produk Digital &amp;<br><span class="grad-text">AKUN PREMIUM TERPERCAYA</span></h1>
                <p class="hero-desc">Pusat Marketplace Produk Digital &amp; Akun Premium Terpercaya. Platform terdepan yang menyediakan aneka source code aplikasi, file kreatif, e-book berkualitas, serta akun premium terverifikasi dengan sistem otomatis instan 24/7 dan bergaransi resmi.</p>
                <div class="hero-cta">
                    @auth
                        @if(Auth::user()->role_id == 2)
                            <a href="{{ route('premium.katalog') }}" class="btn-primary"><i class="bi bi-bag-fill"></i> Mulai
                                Jelajah Katalog</a>
                            <a href="{{ route('premium.riwayat') }}" class="btn-secondary"><i class="bi bi-clock-history"></i>
                                Riwayat Pembelian</a>
                        @else
                            <a href="{{ url('/dashboard') }}" class="btn-primary"><i class="bi bi-speedometer2"></i> Masuk
                                Dashboard</a>
                        @endif
                    @else
                        <a href="{{ route('premium.katalog') }}" class="btn-primary"><i class="bi bi-bag-fill"></i>
                            Belanja Produk Digital</a>
                        <a href="{{ url('/pendaftaran') }}" class="btn-secondary"><i class="bi bi-person-plus"></i> Daftar
                            Akun Gratis</a>
                    @endauth
                </div>
                <!-- <div class="hero-trust">
                    <div class="trust-item"><i class="bi bi-check-circle-fill"></i> Akun Premium &amp; File Digital
                    </div>
                    <div class="trust-item"><i class="bi bi-check-circle-fill"></i> Download ZIP / File Instan</div>
                    <div class="trust-item"><i class="bi bi-check-circle-fill"></i> Pembayaran QRIS &amp; Bank</div>
                </div> -->
            </div>
            <div class="hero-visual" style="flex: 0 0 auto; max-width: 480px; width: 100%; margin: 0; display: flex; justify-content: center; align-items: center;">
                <div class="hero-img-wrapper" style="position: relative; width: 100%; max-width: 460px; aspect-ratio: 4 / 5; display: flex; align-items: center; justify-content: center;">
                    <div class="hero-img-glow"></div>
                    <img src="{{ asset('assets/img/heroatas.png') }}" 
                         alt="{{ isset($websiteSettings) && $websiteSettings->site_name ? $websiteSettings->site_name : 'Lapaktifikasi' }} - Produk Digital & Akun Premium" 
                         class="hero-banner-img"
                         width="460"
                         height="575"
                         style="width: 100%; height: auto; max-height: 575px; aspect-ratio: 4 / 5; object-fit: contain; display: block;"
                         loading="eager"
                         fetchpriority="high">
                </div>
            </div>
        </div>
    </header>

    <!-- STATS -->
    <section id="stats">
        <div class="stats-grid">
            <div class="glass-card stat-card reveal">
                <div class="stat-icon fi-1"><i class="bi bi-people-fill"></i></div>
                <div class="stat-num">2.500+</div>
                <div class="stat-label">Pelanggan Terdaftar</div>
            </div>
            <div class="glass-card stat-card reveal" style="transition-delay:.1s">
                <div class="stat-icon fi-2"><i class="bi bi-bag-check-fill"></i></div>
                <div class="stat-num">15.000+</div>
                <div class="stat-label">Transaksi Berhasil</div>
            </div>
            <div class="glass-card stat-card reveal" style="transition-delay:.2s">
                <div class="stat-icon fi-3"><i class="bi bi-star-fill"></i></div>
                <div class="stat-num">4.9/5</div>
                <div class="stat-label">Rating Kepuasan</div>
            </div>
            <div class="glass-card stat-card reveal" style="transition-delay:.3s">
                <div class="stat-icon fi-4"><i class="bi bi-clock-fill"></i></div>
                <div class="stat-num">99.8%</div>
                <div class="stat-label">Uptime Sistem</div>
            </div>
        </div>
    </section>

    <!-- MITRA INDUSTRI -->
    <section id="mitra-industri" style="position: relative; z-index: 1; padding: 0 0 60px;">
        <div class="container-custom">
            <div class="section-header centered" style="margin-bottom: 40px;">
                <h2 class="section-title" style="font-size: 1.8rem; color: #1f2937;">Mitra Kami Di Lapaktifikasi</h2>
            </div>
        </div>
        <div class="mitra-wrap">
            <div class="marquee">
                <!-- Group 1 -->
                <div class="marquee-content">
                    @foreach($mitras as $mitra)
                        <div class="mitra-card">
                            <img src="{{ asset($mitra->image_path) }}" alt="{{ $mitra->name }}">
                        </div>
                    @endforeach
                </div>
                <!-- Group 2 (Duplicate for infinite scroll) -->
                <div class="marquee-content">
                    @foreach($mitras as $mitra)
                        <div class="mitra-card">
                            <img src="{{ asset($mitra->image_path) }}" alt="{{ $mitra->name }}">
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    <!-- FITUR -->
    <section id="fitur" class="section-wrap">
        <div class="container-custom">
            <div class="section-header centered">
                
                <h2 class="section-title">Mengapa <span class="highlight">Lapaktifikasi</span>?</h2>
                <p class="section-subtitle">Platform kami dirancang dengan teknologi terkini untuk memastikan pengalaman
                    belanja yang cepat, aman, dan memuaskan.</p>
            </div>
            <div class="features-grid">
                <div class="glass-card feature-card reveal">
                    <div class="feature-icon fi-1"><i class="bi bi-lightning-charge-fill"></i></div>
                    <h3>Pengiriman Instan</h3>
                    <p>Kredensial akun premium dikirim otomatis dalam hitungan detik setelah pembayaran berhasil
                        dikonfirmasi oleh sistem.</p>
                </div>
                <div class="glass-card feature-card reveal" style="transition-delay:.1s">
                    <div class="feature-icon fi-2"><i class="bi bi-shield-lock-fill"></i></div>
                    <h3>3 Payment Gateway Terpercaya</h3>
                    <p>Bekerja sama dengan Midtrans, Duitku, dan Pakasir. Transaksi menggunakan Midtrans tersertifikasi
                        oleh Bank Indonesia, Kominfo, AES, dan PCI DSS serta memiliki standar internasional ISO/IEC
                        27001.</p>
                </div>
                <div class="glass-card feature-card reveal" style="transition-delay:.2s">
                    <div class="feature-icon fi-3"><i class="bi bi-clock-history"></i></div>
                    <h3>Reservasi Stok 15 Menit</h3>
                    <p>Sistem kami mengamankan stok akun pilihan Anda selama 15 menit saat checkout agar tidak diambil
                        pembeli lain.</p>
                </div>
                <div class="glass-card feature-card reveal" style="transition-delay:.3s">
                    <div class="feature-icon fi-4"><i class="bi bi-headset"></i></div>
                    <h3>Dukungan Responsif</h3>
                    <p>Tim kami siap membantu Anda kapan saja melalui berbagai kanal yang tersedia untuk penyelesaian
                        masalah.</p>
                </div>
                <div class="glass-card feature-card reveal" style="transition-delay:.4s">
                    <div class="feature-icon fi-5"><i class="bi bi-collection-fill"></i></div>
                    <h3>Pilihan Produk Lengkap</h3>
                    <p>Temukan berbagai layanan digital premium dalam satu platform — hiburan, produktivitas, hingga
                        pendidikan digital.</p>
                </div>
                <div class="glass-card feature-card reveal" style="transition-delay:.5s">
                    <div class="feature-icon fi-6"><i class="bi bi-arrow-repeat"></i></div>
                    <h3>Riwayat Jelas &amp; Transparan</h3>
                    <p>Semua transaksi tersimpan rapi. Akses kembali kredensial Anda kapan saja melalui halaman riwayat
                        belanja.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- VISI MISI -->
    <section id="visimisi" class="section-wrap alt" style="padding: 90px 5%;">
        <div class="container-custom">
            <div class="section-header centered mb-12">
              
                <h2 class="section-title">Visi &amp; Misi <span class="highlight-cyan">Lapaktifikasi</span></h2>
                <p class="section-subtitle">Lapaktifikasi berkomitmen membangun ekosistem digital terpercaya yang memberdayakan kreator, developer, dan talenta muda berbakat untuk menggerakkan potensi ekonomi kreatif di Indonesia.
                </p>
            </div>

            <div class="vm-grid-container">
                <!-- CARD 1: VISI KAMI -->
                <div class="vm-card-interactive reveal">
                    <!-- Default Unhovered State: Logo + Title -->
                    <div class="vm-default-view">
                        @php
                            $visiLogo = (isset($websiteSettings) && $websiteSettings->visi_logo_path && file_exists(public_path($websiteSettings->visi_logo_path)))
                                ? asset($websiteSettings->visi_logo_path)
                                : asset('assets/img/visi_kami.svg');
                        @endphp
                        <div style="width: 170px; height: 170px; display: flex; align-items: center; justify-content: center;">
                            <img src="{{ $visiLogo }}" alt="Visi Kami" style="max-width: 160px; max-height: 160px; object-fit: contain; filter: drop-shadow(0 4px 12px rgba(0,0,0,0.05));">
                        </div>
                        <h3 style="font-family: 'Space Grotesk', sans-serif; font-size: 1.65rem; font-weight: 800; color: #000000; margin-top: 14px;">Visi Kami</h3>
                    </div>

                    <!-- Hovered State: Full Text Content -->
                    <div class="vm-hover-view">
                        <h3 style="font-family: 'Space Grotesk', sans-serif; font-size: 1.5rem; font-weight: 800; color: #000000; margin-bottom: 18px;">Visi Kami</h3>
                        <p style="font-size: 0.95rem; color: #555555; line-height: 1.8; margin-bottom: 24px;">
                            Menjadi platform marketplace produk digital &amp; akun premium terdepan yang menginspirasi kemandirian ekonomi digital, inovasi karya teknologi, serta mempermudah akses produk kreatif berkualitas di seluruh Indonesia.
                        </p>
                        <div style="padding: 16px 20px; border-radius: 16px; background: #fafafa; border: 1px solid #e5e5e5; display: flex; align-items: center; gap: 12px; width: 100%;">
                            <i class="bi bi-patch-check-fill" style="color: #5417D7; font-size: 1.4rem; flex-shrink: 0;"></i>
                            <span style="font-size: 0.84rem; font-weight: 700; color: #000000;">Kemandirian Ekonomi &amp; Inovasi Digital</span>
                        </div>
                    </div>
                </div>

                <!-- CARD 2: MISI UTAMA -->
                <div class="vm-card-interactive reveal" style="transition-delay:.15s">
                    <!-- Default Unhovered State: Logo + Title -->
                    <div class="vm-default-view">
                        @php
                            $misiLogo = (isset($websiteSettings) && $websiteSettings->misi_logo_path && file_exists(public_path($websiteSettings->misi_logo_path)))
                                ? asset($websiteSettings->misi_logo_path)
                                : asset('assets/img/misi_utama.svg');
                        @endphp
                        <div style="width: 170px; height: 170px; display: flex; align-items: center; justify-content: center;">
                            <img src="{{ $misiLogo }}" alt="Misi Utama" style="max-width: 150px; max-height: 175px; object-fit: contain; filter: drop-shadow(0 4px 12px rgba(0,0,0,0.05));">
                        </div>
                        <h3 style="font-family: 'Space Grotesk', sans-serif; font-size: 1.65rem; font-weight: 800; color: #000000; margin-top: 14px;">Misi Utama</h3>
                    </div>

                    <!-- Hovered State: Full Bulleted Points Content (Matching Screenshot 1) -->
                    <div class="vm-hover-view">
                        <h3 style="font-family: 'Space Grotesk', sans-serif; font-size: 1.5rem; font-weight: 800; color: #000000; margin-bottom: 18px;">Misi Utama</h3>
                        <ul style="list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 12px; width: 100%;">
                            <li style="display: flex; align-items: flex-start; gap: 10px; font-size: 0.88rem; color: #555555; line-height: 1.55;">
                                <i class="bi bi-chevron-right" style="color: #000000; font-weight: 800; font-size: 0.85rem; margin-top: 2px; flex-shrink: 0;"></i>
                                <span>Membuka wadah distribusi karya digital kreatif (Source Code, Aplikasi, ZIP, E-Book, Desain) &amp; Akun Premium terverifikasi.</span>
                            </li>
                            <li style="display: flex; align-items: flex-start; gap: 10px; font-size: 0.88rem; color: #555555; line-height: 1.55;">
                                <i class="bi bi-chevron-right" style="color: #000000; font-weight: 800; font-size: 0.85rem; margin-top: 2px; flex-shrink: 0;"></i>
                                <span>Mengembangkan potensi dan jiwa kewirausahaan digital para kreator, talenta muda, dan pengembang teknologi.</span>
                            </li>
                            <li style="display: flex; align-items: flex-start; gap: 10px; font-size: 0.88rem; color: #555555; line-height: 1.55;">
                                <i class="bi bi-chevron-right" style="color: #000000; font-weight: 800; font-size: 0.85rem; margin-top: 2px; flex-shrink: 0;"></i>
                                <span>Menyediakan sistem pengiriman otomatis serta unduh file digital instan yang aman, cepat, dan terpercaya.</span>
                            </li>
                            <li style="display: flex; align-items: flex-start; gap: 10px; font-size: 0.88rem; color: #555555; line-height: 1.55;">
                                <i class="bi bi-chevron-right" style="color: #000000; font-weight: 800; font-size: 0.85rem; margin-top: 2px; flex-shrink: 0;"></i>
                                <span>Mendorong pertumbuhan ekonomi kreatif digital melalui sistem komisi dan kemitraan bagi hasil yang transparan.</span>
                            </li>
                            <li style="display: flex; align-items: flex-start; gap: 10px; font-size: 0.88rem; color: #555555; line-height: 1.55;">
                                <i class="bi bi-chevron-right" style="color: #000000; font-weight: 800; font-size: 0.85rem; margin-top: 2px; flex-shrink: 0;"></i>
                                <span>Menyediakan layanan pembuatan website, integrasi sistem, dan portal digital custom untuk komunitas maupun institusi.</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CARA KERJA -->
    <section id="cara-kerja" class="section-wrap">
        <div class="container-custom">
            <div class="section-header centered">
               
                <h2 class="section-title">Transaksi Semudah <span class="highlight">4 Langkah</span></h2>
                <p class="section-subtitle">Proses belanja cepat dan sederhana — dari memilih file digital / akun hingga
                    mengunduh langsung di akun Anda.</p>
            </div>
            <div class="steps-grid">
                <div class="glass-card step-card reveal">
                    <div class="step-num">1</div>
                    <h4>Daftar Akun</h4>
                    <p>Buat akun Lapaktifikasi gratis hanya dengan email dan kata sandi. Proses verifikasi instan tanpa
                        ribet.</p>
                </div>
                <div class="glass-card step-card reveal" style="transition-delay:.12s">
                    <div class="step-num">2</div>
                    <h4>Pilih Produk</h4>
                    <p>Jelajahi berbagai pilihan akun premium, source code (ZIP), e-book, hingga file digital bermanfaat
                        lainnya.</p>
                </div>
                <div class="glass-card step-card reveal" style="transition-delay:.24s">
                    <div class="step-num">3</div>
                    <h4>Bayar Otomatis</h4>
                    <p>Lakukan pembayaran aman via QRIS, e-Wallet, atau Transfer Bank otomatis yang diproses dalam
                        hitungan detik.</p>
                </div>
                <div class="glass-card step-card reveal" style="transition-delay:.36s">
                    <div class="step-num">4</div>
                    <h4>Unduh / Akses Instan</h4>
                    <p>Unduh file ZIP/dokumen secara langsung atau lihat kredensial akun di halaman riwayat belanja Anda
                        seketika!</p>
                </div>
            </div>
        </div>
    </section>

    <!-- PRODUK -->
    <section id="produk" class="section-wrap alt">
        <div class="container-custom">
            <div class="section-header centered">
                
                <h2 class="section-title">Kategori Produk <span class="highlight">Digital Kami</span></h2>
                <p class="section-subtitle">Temukan berbagai ragam produk digital terlengkap — dari aplikasi premium
                    hingga karya file digital buatan creator &amp; siswa.</p>
            </div>
            <div class="products-grid">
                <div class="glass-card product-card reveal">
                    <div class="product-card-icon"><img src="https://cdn-icons-png.flaticon.com/512/1005/1005141.png"
                            alt="Source Code"></div>
                    <h3>Source Code &amp; ZIP File</h3>
                    <p>Script web, project laravel/php, source code absensi, template, &amp; modul siap pakai dalam
                        format ZIP/RAR.</p>
                    <div class="product-card-footer">
                        <div class="product-card-price"><small>File Digital</small><strong>Unduh Instan</strong></div>
                        <a href="{{ route('premium.katalog', ['kategori' => 'digital']) }}" class="btn-see">Cek File <i
                                class="bi bi-arrow-right"></i></a>
                    </div>
                </div>
                <div class="glass-card product-card reveal" style="transition-delay:.1s">
                    <div class="product-card-icon"><img src="https://cdn-icons-png.flaticon.com/512/3670/3670157.png"
                            alt="Spotify & Netflix"></div>
                    <h3>Spotify &amp; Netflix Premium</h3>
                    <p>Nikmati akses streaming musik tanpa iklan dan film 4K Ultra HD dengan harga ramah kantong.</p>
                    <div class="product-card-footer">
                        <div class="product-card-price"><small>Akun Premium</small><strong>Mulai Rp 15.000</strong>
                        </div>
                        <a href="{{ route('premium.katalog', ['kategori' => 'premium']) }}" class="btn-see">Beli <i
                                class="bi bi-arrow-right"></i></a>
                    </div>
                </div>
                <div class="glass-card product-card reveal" style="transition-delay:.2s">
                    <div class="product-card-icon"><img src="https://cdn-icons-png.flaticon.com/512/2970/2970785.png"
                            alt="Gambar & Desain Grafis"></div>
                    <h3>Gambar &amp; Desain Grafis</h3>
                    <p>Elemen desain, template Canva/Photoshop, ilustrasi, foto, dan aset grafis berkualitas tinggi
                    untuk kebutuhan konten.</p>
                    <div class="product-card-footer">
                        <div class="product-card-price"><small>Aset Kreatif</small><strong>Download Direct</strong>
                        </div>
                        <a href="{{ route('premium.katalog', ['kategori' => 'digital']) }}" class="btn-see">Beli <i
                                class="bi bi-arrow-right"></i></a>
                    </div>
                </div>
                <div class="glass-card product-card reveal" style="transition-delay:.3s">
                    <div class="product-card-icon"><img src="https://cdn-icons-png.flaticon.com/512/2991/2991106.png"
                            alt="Dokumen & Notepad"></div>
                    <h3>Dokumen &amp; Notepad (TXT)</h3>
                    <p>Modul pembelajaran, catatan penting, daftar prompt AI, &amp; script text yang dapat diakses
                        langsung setelah order.</p>
                    <div class="product-card-footer">
                        <div class="product-card-price"><small>File Teks / TXT</small><strong>Akses Langsung</strong>
                        </div>
                        <a href="{{ route('premium.katalog', ['kategori' => 'digital']) }}" class="btn-see">Beli <i
                                class="bi bi-arrow-right"></i></a>
                    </div>
                </div>
                <div class="glass-card product-card reveal" style="transition-delay:.4s">
                    <div class="product-card-icon"><img src="https://cdn-icons-png.flaticon.com/512/2232/2232688.png"
                            alt="E-Book & Modul Edukasi"></div>
                    <h3>E-Book &amp; Modul Edukasi</h3>
                    <p>Panduan praktis, e-book teknologi, strategi bisnis digital, hingga materi pengembangan karir profesional.</p>
                    <div class="product-card-footer">
                        <div class="product-card-price"><small>Format PDF</small><strong>Unduh Seketika</strong></div>
                        <a href="{{ route('premium.katalog', ['kategori' => 'digital']) }}" class="btn-see">Beli <i
                                class="bi bi-arrow-right"></i></a>
                    </div>
                </div>
                <div class="glass-card product-card reveal" style="transition-delay:.5s">
                    <div class="product-card-icon"><img src="https://cdn-icons-png.flaticon.com/512/3135/3135715.png"
                            alt="Karya Kreator & Developer"></div>
                    <h3>Karya Kreator &amp; Developer</h3>
                    <p>Dukung inovasi dan karya digital para kreator independen serta developer berbakat dengan membeli aset dan source code pilihan.</p>
                    <div class="product-card-footer">
                        <div class="product-card-price"><small>Inovasi Digital</small><strong>Aset Kreatif</strong>
                        </div>
                        <a href="{{ route('premium.katalog') }}" class="btn-see">Jelajahi <i
                                class="bi bi-arrow-right"></i></a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- JASA PEMBUATAN WEBSITE & KEMITRAAN BANNER -->
    <section id="jasa-website" class="section-wrap">
        <div class="container-custom">
            <div class="jasa-cta-box reveal">
                <div class="row align-items-center" style="display: flex; flex-wrap: wrap; gap: 30px;">
                    <div style="flex: 1; min-width: 280px;">
                        
                        <h2
                            style="font-family: 'Space Grotesk', sans-serif; font-size: clamp(1.6rem, 3.5vw, 2.5rem); font-weight: 700; color: #000; margin-bottom: 16px;">
                            Mau Memiliki Website Marketplace / Portal Digital Seperti Ini?
                        </h2>
                        <p style="color: var(--text-muted); font-size: 1.02rem; line-height: 1.7; margin-bottom: 24px;">
                            Tim developer dan konsultan teknologi profesional Lapaktifikasi siap membantu Anda
                            membangun website custom, sistem e-commerce, portal komunitas/institusi, hingga aplikasi
                            bisnis terintegrasi sesuai kebutuhan Anda!
                        </p>
                        <div class="jasa-btn-group" style="display: flex; gap: 16px; flex-wrap: wrap;">
                            <a href="https://wa.me/{{ isset($websiteSettings) && $websiteSettings->contact_phone ? preg_replace('/[^0-9]/', '', $websiteSettings->contact_phone) : '6287897600086' }}?text=Halo%20Tim%20Lapaktifikasi,%20saya%20tertarik%20untuk%20konsultasi%20jasa%20pembuatan%20website%20/%20platform%20digital."
                                target="_blank" class="btn-primary">
                                <i class="bi bi-whatsapp"></i> Hubungi Tim Lapaktifikasi
                            </a>
                            <a href="{{ route('join.partner') }}" class="btn-secondary">
                                <i class="bi bi-people"></i> Program Kemitraan Gratis
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- TESTIMONI -->
    <section id="testimoni" class="section-wrap alt" style="overflow: hidden;">
        <div class="container-custom" style="margin-bottom: 40px;">
            <div class="section-header centered">
                
                <h2 class="section-title">Kata Mereka yang <span class="highlight">Sudah Merasakan</span></h2>
                <p class="section-subtitle">Ribuan pelanggan &amp; seller telah merasakan kemudahan bertransaksi file
                    digital dan akun premium di Lapaktifikasi.</p>
            </div>
        </div>

        <div class="testi-marquee-wrap">
            <div class="testi-marquee">
                <!-- Group 1 -->
                <div class="testi-marquee-group">
                    @foreach($testimonis as $item)
                        <div class="glass-card testi-card">
                            <div class="testi-stars">
                                @for($i = 1; $i <= $item->rating; $i++) &#9733; @endfor
                            </div>
                            <p class="testi-text">"{{ $item->comment }}"</p>
                            <div class="testi-author">
                                <div class="testi-avatar" style="background: #fafafa; color: #000000;">
                                    {{ strtoupper(substr($item->name, 0, 1)) }}
                                </div>
                                <div>
                                    <div class="testi-name">{{ $item->name }}</div>
                                    <div class="testi-role">{{ $item->role }}</div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                <!-- Group 2 (Duplicate for infinite marquee scroll) -->
                <div class="testi-marquee-group">
                    @foreach($testimonis as $item)
                        <div class="glass-card testi-card">
                            <div class="testi-stars">
                                @for($i = 1; $i <= $item->rating; $i++) &#9733; @endfor
                            </div>
                            <p class="testi-text">"{{ $item->comment }}"</p>
                            <div class="testi-author">
                                <div class="testi-avatar" style="background: #fafafa; color: #000000;">
                                    {{ strtoupper(substr($item->name, 0, 1)) }}
                                </div>
                                <div>
                                    <div class="testi-name">{{ $item->name }}</div>
                                    <div class="testi-role">{{ $item->role }}</div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    <!-- JOIN PARTNER PROMO -->
    <section id="join-partner-promo" class="section-wrap">
        <div class="container-custom">
            <div class="vm-grid">
                <div class="hero-content reveal">
                    
                    <h2 class="section-title">Website Toko Komunitas <span class="highlight">100% Gratis</span></h2>
                    <p class="hero-desc" style="margin-bottom:28px;">
                        Miliki platform e-commerce produk digital khusus untuk komunitas atau sekolah Anda secara gratis
                        (contoh: <code>nusabogor.lapaktifikasi.my.id</code>). Ajak para seller digital di dalam
                        komunitas Anda untuk bergabung berjualan, dan nikmati bagi hasil keuntungan dari komisi
                        transaksi secara otomatis dan transparan.
                    </p>
                    <div class="hero-trust"
                        style="margin-bottom:36px; display:flex; flex-direction:column; align-items:flex-start; gap:12px;">
                        <div class="trust-item"><i class="bi bi-patch-check-fill"></i> Subdomain Khusus Komunitas (e.g.,
                            <code>nusabogor.lapaktifikasi.my.id</code>)
                        </div>
                        <div class="trust-item"><i class="bi bi-patch-check-fill"></i> Rekrut &amp; Hubungkan Seller
                            Digital dari Komunitas Anda</div>
                        <div class="trust-item"><i class="bi bi-patch-check-fill"></i> Sistem Bagi Hasil Komisi
                            Transparan dengan Tim Lapaktifikasi</div>
                    </div>
                    <a href="{{ route('join.partner') }}" class="btn-primary"><i
                            class="bi bi-arrow-right-circle-fill"></i> Pelajari Selengkapnya</a>
                </div>
                <div class="hero-visual reveal" style="transition-delay:.15s">
                    <div class="glass-card partner-promo-card">
                        <div class="card-header-row">
                            <span style="font-weight:700;font-size:1.1rem;font-family:'Space Grotesk',sans-serif;"><i
                                    class="bi bi-rocket-takeoff-fill"></i> Kemitraan Komunitas</span>
                            <!-- <span class="card-badge-live"
                                style="background:rgba(0,0,0,0.05); color:#000000; border-color:#000000;">Gratis</span> -->
                        </div>
                        <div class="product-row">
                            <div class="product-icon"><i class="bi bi-globe"></i></div>
                            <div class="product-info">
                                <h6>Subdomain Sendiri</h6>
                                <small>nusabogor.lapaktifikasi.my.id</small>
                            </div>
                        </div>
                        <div class="product-row">
                            <div class="product-icon"><i class="bi bi-people"></i></div>
                            <div class="product-info">
                                <h6>Rekrut Seller Internal</h6>
                                <small>Kumpulkan seller dari komunitas Anda</small>
                            </div>
                        </div>
                        <div class="product-row">
                            <div class="product-icon"><i class="bi bi-cash-stack"></i></div>
                            <div class="product-info">
                                <h6>Bagi Hasil Otomatis</h6>
                                <small>Komisi transparan dari tiap transaksi</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- FAQ -->
    <section id="faq" class="section-wrap">
        <div class="container-custom">
            <div class="section-header centered">
               
                <h2 class="section-title">Pertanyaan yang <span class="highlight">Sering Ditanyakan</span></h2>
                <p class="section-subtitle">Temukan jawaban atas pertanyaan umum seputar layanan dan cara penggunaan
                    Lapaktifikasi.</p>
            </div>
            <div class="faq-wrap">
                <div class="faq-item reveal">
                    <button class="faq-question" onclick="toggleFaq(this)">Apakah akun premium yang dijual di
                        Lapaktifikasi aman dan legal? <i class="bi bi-plus-lg faq-icon"></i></button>
                    <div class="faq-answer">
                        <p>Ya, seluruh produk yang kami jual merupakan akun premium yang aman untuk digunakan. Kami
                            berkomitmen untuk menjaga keamanan dan kenyamanan setiap pelanggan dengan sistem yang
                            terverifikasi.</p>
                    </div>
                </div>
                <div class="faq-item reveal" style="transition-delay:.08s">
                    <button class="faq-question" onclick="toggleFaq(this)">Berapa lama waktu yang dibutuhkan untuk
                        menerima akun setelah pembayaran? <i class="bi bi-plus-lg faq-icon"></i></button>
                    <div class="faq-answer">
                        <p>Sistem kami memproses pengiriman secara otomatis. Setelah pembayaran Anda dikonfirmasi oleh
                            Payment Gateway, kredensial akun akan langsung tersedia di halaman riwayat belanja dalam hitungan
                            detik — umumnya kurang dari 5 detik.</p>
                    </div>
                </div>
                <div class="faq-item reveal" style="transition-delay:.16s">
                    <button class="faq-question" onclick="toggleFaq(this)">Metode pembayaran apa saja yang tersedia? <i
                            class="bi bi-plus-lg faq-icon"></i></button>
                    <div class="faq-answer">
                        <p>Kami menyediakan berbagai metode pembayaran terverifikasi termasuk QRIS instan, transfer bank (BCA,
                            BNI, BRI, Mandiri), e-wallet (GoPay, OVO, Dana, ShopeePay), dan Virtual Account melalui 3 payment
                            gateway terpercaya (Midtrans, Duitku, Pakasir). Semua transaksi diproses secara aman dan serba otomatis 24/7.</p>
                    </div>
                </div>
                <div class="faq-item reveal" style="transition-delay:.24s">
                    <button class="faq-question" onclick="toggleFaq(this)">Apa yang terjadi jika akun yang saya beli
                        bermasalah? <i class="bi bi-plus-lg faq-icon"></i></button>
                    <div class="faq-answer">
                        <p>Kepuasan pelanggan adalah prioritas kami. Jika Anda mengalami masalah dengan akun yang
                            dibeli, segera hubungi tim dukungan kami melalui WhatsApp atau menu Laporan/Pusat Bantuan, dan kami akan memberikan garansi replace (ganti baru) atau refund sesuai ketentuan.</p>
                    </div>
                </div>
                <div class="faq-item reveal" style="transition-delay:.32s">
                    <button class="faq-question" onclick="toggleFaq(this)">Apakah data pribadi dan pembayaran saya aman?
                        <i class="bi bi-plus-lg faq-icon"></i></button>
                    <div class="faq-answer">
                        <p>Keamanan data Anda adalah hal utama bagi kami. Seluruh data pribadi dienkripsi dan
                            dilindungi. Proses pembayaran dikelola sepenuhnya oleh Midtrans yang telah tersertifikasi
                            PCI-DSS, sehingga informasi kartu atau rekening Anda tidak pernah kami simpan.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA BOTTOM -->
    <section id="cta" class="section-wrap">
        <div class="container-custom">
            <div class="cta-box reveal">
                <h2>Siap Menikmati <span class="grad-text">Premium Digital</span>?</h2>
                <p>Bergabunglah dengan ribuan pelanggan yang sudah mempercayai Lapaktifikasi. Daftar gratis sekarang dan
                    dapatkan akun premium impian Anda.</p>
                <div class="cta-actions">
                    @auth
                        @if(Auth::user()->role_id == 2)
                            <a href="{{ route('premium.katalog') }}" class="btn-primary"><i class="bi bi-bag-fill"></i> Mulai
                                Belanja</a>
                        @else
                            <a href="{{ url('/dashboard') }}" class="btn-primary"><i class="bi bi-speedometer2"></i> Ke
                                Dashboard</a>
                        @endif
                    @else
                        <a href="{{ url('/pendaftaran') }}" class="btn-primary"><i class="bi bi-person-plus-fill"></i>
                            Daftar Gratis Sekarang</a>
                        <a href="#" class="btn-secondary" onclick="openModal(); return false;"><i
                                class="bi bi-box-arrow-in-right"></i> Sudah Punya Akun</a>
                    @endauth
                </div>
            </div>
        </div>
    </section>
@endsection
