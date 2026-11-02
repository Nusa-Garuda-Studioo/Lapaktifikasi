@extends('layouts.landing')

@php
    $siteName = isset($websiteSettings) && $websiteSettings->site_name ? $websiteSettings->site_name : 'Lapaktifikasi';
    $siteDesc = isset($websiteSettings) && $websiteSettings->site_description ? $websiteSettings->site_description : 'Marketplace Produk Digital, Source Code & Akun Premium Terpercaya';
    $phoneRaw = isset($websiteSettings) && $websiteSettings->contact_phone ? $websiteSettings->contact_phone : '081234567890';
    $phoneClean = preg_replace('/[^0-9]/', '', $phoneRaw);
    if (str_starts_with($phoneClean, '0')) {
        $phoneClean = '62' . substr($phoneClean, 1);
    }
    $contactEmail = isset($websiteSettings) && $websiteSettings->contact_email ? $websiteSettings->contact_email : 'partner@lapaktifikasi.com';
    $logoUrl = isset($websiteSettings) && $websiteSettings->logo_path ? asset($websiteSettings->logo_path) : asset('assets/img/brand_ambassador.jpg');
    $canonicalUrl = url('/join-partner');
    $currentHost = request()->getHost() ?: 'lapaktifikasi.com';
@endphp

@section('title', 'Join Partner Komunitas - Buka Hub E-Commerce Digital Gratis | ' . $siteName)
@section('meta_title', 'Join Partner Komunitas | ' . $siteName)
@section('meta_description', 'Bergabunglah sebagai mitra partner resmi di ' . $siteName . '. Buka website toko digital khusus komunitas Anda secara gratis dengan custom subdomain, sistem serba otomatis, dan bagi hasil komisi transparan.')
@section('og_image', $logoUrl)

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/pages/partner.css') }}">
@endpush

@push('schema')
<script type="application/ld+json">
{
  "{{ '@context' }}": "https://schema.org",
  "@type": "WebPage",
  "name": "Join Partner Komunitas - {{ $siteName }}",
  "url": "{{ $canonicalUrl }}",
  "description": "Program kemitraan komunitas dan toko regional Lapaktifikasi dengan custom website subdomain gratis dan bagi hasil komisi transaksi.",
  "breadcrumb": {
    "@type": "BreadcrumbList",
    "itemListElement": [
      {
        "@type": "ListItem",
        "position": 1,
        "name": "Beranda",
        "item": "{{ url('/') }}"
      },
      {
        "@type": "ListItem",
        "position": 2,
        "name": "Join Partner",
        "item": "{{ $canonicalUrl }}"
      }
    ]
  }
}
</script>
@endpush

@section('nav_links')
    <li><a href="{{ url('/') }}">Beranda</a></li>
    <li><a href="{{ route('premium.katalog') }}">Katalog</a></li>
    <li><a href="#kelebihan">Kelebihan</a></li>
    <li><a href="#showcase">Subdomain</a></li>
    <li><a href="#roles">Peran Mitra</a></li>
    <li><a href="#skema">Bagi Hasil</a></li>
    <li><a href="#faq">FAQ</a></li>
    <li><a href="{{ route('daftar.seller') }}">Jadi Seller</a></li>
    <li><a href="{{ route('join.partner') }}" class="active">Join Partner</a></li>
@endsection

@section('mobile_nav_links')
    <li><a href="{{ url('/') }}" onclick="closeMobileMenu()">Beranda</a></li>
    <li><a href="{{ route('premium.katalog') }}" onclick="closeMobileMenu()">Katalog Produk</a></li>
    <li><a href="#kelebihan" onclick="closeMobileMenu()">Kelebihan Kemitraan</a></li>
    <li><a href="#showcase" onclick="closeMobileMenu()">Contoh Subdomain</a></li>
    <li><a href="#roles" onclick="closeMobileMenu()">Tugas &amp; Peran</a></li>
    <li><a href="#skema" onclick="closeMobileMenu()">Skema Bagi Hasil</a></li>
    <li><a href="#faq" onclick="closeMobileMenu()">FAQ Partner</a></li>
    <li><a href="{{ route('daftar.seller') }}" onclick="closeMobileMenu()">Jadi Seller</a></li>
    <li><a href="{{ route('join.partner') }}" onclick="closeMobileMenu()">Join Partner</a></li>
@endsection

@section('content')
    <!-- HERO SECTION -->
    <header id="hero-partner">
        <div class="hero-partner-inner">
           
            <h1 class="hero-title reveal" style="transition-delay: .1s;">
                Buka Hub E-Commerce Komunitas Anda<br>
                <span class="grad-text">100% Gratis Tanpa Biaya Server</span>
            </h1>
            <p class="hero-desc reveal" style="transition-delay: .2s;">
                Miliki platform e-commerce produk digital, source code, dan voucher dengan brand komunitas Anda sendiri. Kami sediakan website subdomain khusus secara cuma-cuma, sistem backend otomatis, dan Anda menikmati <strong>bagi hasil komisi transaksi otomatis</strong>!
            </p>
            <div class="hero-cta-group reveal" style="transition-delay: .3s;">
                <a href="#register" class="btn-primary"><i class="bi bi-send-fill"></i> Ajukan Kemitraan Sekarang</a>
                <a href="#showcase" class="btn-secondary"><i class="bi bi-globe"></i> Contoh Subdomain</a>
            </div>
        </div>
    </header>

    <!-- DYNAMIC MITRA INDUSTRI SECTION -->
    @if(isset($mitras) && $mitras->count() > 0)
    <section class="mitra-section reveal">
        <div class="mitra-label">Didukung &amp; Berkolaborasi Bersama Ekosistem Pendidikan &amp; Industri</div>
        <div class="mitra-grid">
            @foreach($mitras as $mitra)
                <div class="mitra-item">
                    @if($mitra->image_path && file_exists(public_path($mitra->image_path)))
                        <img src="{{ asset($mitra->image_path) }}" alt="{{ $mitra->name }}">
                    @else
                        <i class="bi bi-building-check text-primary" style="font-size: 1.4rem;"></i>
                    @endif
                    <span>{{ $mitra->name }}</span>
                </div>
            @endforeach
        </div>
    </section>
    @endif

    <!-- KELEBIHAN KEMITRAAN SECTION -->
    <section id="kelebihan" class="section-wrap alt">
        <div class="container-custom">
            <div class="section-header centered reveal">
               
                <h2 class="section-title">Solusi All-in-One untuk <span class="highlight">Komunitas Anda</span></h2>
                <p class="section-subtitle">Dukung penuh para kreator dan seller digital lokal di komunitas Anda dengan menyediakan marketplace resmi yang kredibel.</p>
            </div>
            <div class="benefits-grid">
                <div class="glass-card benefit-card reveal">
                    <div class="benefit-icon"><i class="bi bi-browser-chrome"></i></div>
                    <h3>Website Komunitas Instan</h3>
                    <p>Website khusus dengan branding komunitas Anda siap online secara instan tanpa perlu repot koding, setup domain, ataupun sewa server.</p>
                </div>
                <div class="glass-card benefit-card reveal" style="transition-delay: .1s;">
                    <div class="benefit-icon"><i class="bi bi-people-fill"></i></div>
                    <h3>Pemberdayaan Seller Komunitas</h3>
                    <p>Wadahi seller digital di dalam komunitas Anda (gaming, desain grafis, source code, jasa freelance) agar memiliki pasar resmi yang aman.</p>
                </div>
                <div class="glass-card benefit-card reveal" style="transition-delay: .2s;">
                    <div class="benefit-icon"><i class="bi bi-cash-coin"></i></div>
                    <h3>Bagi Hasil Transparan</h3>
                    <p>Tidak ada biaya bulanan. Kemitraan dibiayai murni dari persentase bagi hasil transaksi otomatis yang sangat adil dan dapat dicairkan kapan saja.</p>
                </div>
                <div class="glass-card benefit-card reveal" style="transition-delay: .3s;">
                    <div class="benefit-icon"><i class="bi bi-qr-code-scan"></i></div>
                    <h3>Multi Payment Gateway Otomatis</h3>
                    <p>Seluruh transaksi pembeli diverifikasi otomatis via QRIS instan, Transfer Bank, dan e-Wallet tanpa butuh konfirmasi struk manual.</p>
                </div>
                <div class="glass-card benefit-card reveal" style="transition-delay: .4s;">
                    <div class="benefit-icon"><i class="bi bi-shield-check"></i></div>
                    <h3>Pemeliharaan Server Berkala</h3>
                    <p>Tim IT {{ $siteName }} merawat performa server, keamanan data, dan audit sistem secara rutin demi stabilitas toko komunitas Anda.</p>
                </div>
                <div class="glass-card benefit-card reveal" style="transition-delay: .5s;">
                    <div class="benefit-icon"><i class="bi bi-bar-chart-line-fill"></i></div>
                    <h3>Laporan Real-time</h3>
                    <p>Pantau trafik kunjungan toko komunitas Anda, volume transaksi, dan akumulasi saldo bagi hasil komisi secara transparan.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- SUBDOMAIN SHOWCASE SECTION -->
    <section id="showcase" class="section-wrap">
        <div class="container-custom">
            <div class="section-header centered reveal">
                
                <h2 class="section-title">Contoh Identitas Toko <span class="highlight">Komunitas Anda</span></h2>
                <p class="section-subtitle">Setiap partner komunitas akan mendapatkan alamat website khusus dengan subdomain yang disesuaikan secara cuma-cuma.</p>
            </div>
            <div class="subdomain-grid">
                <div class="glass-card subdomain-card reveal">
                    <span class="subdomain-badge"><i class="bi bi-geo-alt-fill"></i> Komunitas Regional</span>
                    <h4>nusabogor.{{ $currentHost }}</h4>
                    <p>Website marketplace yang dirancang khusus untuk menampung para seller dan kreator digital regional Bogor dan sekitarnya.</p>
                </div>
                <div class="glass-card subdomain-card reveal" style="transition-delay: .1s;">
                    <span class="subdomain-badge"><i class="bi bi-controller"></i> Komunitas Gamers</span>
                    <h4>gamerstore.{{ $currentHost }}</h4>
                    <p>Subdomain khusus yang dirancang untuk komunitas game, mewadahi seller akun, top-up, dan voucher game internal.</p>
                </div>
                <div class="glass-card subdomain-card reveal" style="transition-delay: .2s;">
                    <span class="subdomain-badge"><i class="bi bi-palette-fill"></i> Komunitas Kreatif</span>
                    <h4>kreatifhub.{{ $currentHost }}</h4>
                    <p>Dukungan platform untuk template desain, foto, ilustrasi, font, dan aset kreatif karya anggota komunitas.</p>
                </div>
                <div class="glass-card subdomain-card reveal" style="transition-delay: .3s;">
                    <span class="subdomain-badge"><i class="bi bi-mortarboard-fill"></i> Komunitas &amp; Kampus</span>
                    <h4>edustore.{{ $currentHost }}</h4>
                    <p>Wadah teaching factory dan e-commerce karya kreatif, modul, dan software IT komunitas/institusi.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- ROLES & RESPONSIBILITIES SECTION -->
    <section id="roles" class="section-wrap alt">
        <div class="container-custom">
            <div class="section-header centered reveal">
                
                <h2 class="section-title">Bagaimana Peran <span class="highlight">Mitra Partner</span>?</h2>
                <p class="section-subtitle">Sebagai mitra, Anda bertindak sebagai penggerak utama ekosistem digital di komunitas Anda.</p>
            </div>
            <div class="roles-grid">
                <div class="glass-card role-card reveal">
                    <div class="role-number">01</div>
                    <h4>Rekrut &amp; Ajak Seller</h4>
                    <p>Tugas utama Anda adalah menemukan dan mengajak para seller serta kreator digital di komunitas Anda untuk memajang produk mereka di website toko komunitas.</p>
                </div>
                <div class="glass-card role-card reveal" style="transition-delay: .1s;">
                    <div class="role-number">02</div>
                    <h4>Kurasi &amp; Jaga Mutu</h4>
                    <p>Bertindak sebagai kurator untuk memastikan produk yang dijual anggota komunitas memiliki kualitas baik dan mematuhi ketentuan legalitas platform.</p>
                </div>
                <div class="glass-card role-card reveal" style="transition-delay: .2s;">
                    <div class="role-number">03</div>
                    <h4>Promosikan Website Toko</h4>
                    <p>Sebarluaskan alamat website subdomain komunitas Anda kepada seluruh anggota komunitas agar seluruh transaksi dilakukan secara aman dan resmi.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- REVENUE SHARING SECTION -->
    <section id="skema" class="section-wrap">
        <div class="container-custom">
            <div class="revenue-wrap">
                <div class="reveal">
                   
                    <h2 class="section-title" style="font-size: 2.2rem;">Sistem Bagi Hasil <span class="highlight">Komisi Otomatis</span></h2>
                    <p style="color:var(--text-muted); margin-bottom: 24px; line-height: 1.7;">
                        Kemitraan ini didesain 100% gratis tanpa biaya bulanan. Pendapatan Anda bersumber langsung dari persentase komisi transaksi yang dilakukan oleh seller di website komunitas Anda.
                    </p>
                    <div class="glass-card" style="padding: 24px;">
                        <div style="font-family:'Space Grotesk',sans-serif; font-weight:700; font-size:1.1rem; margin-bottom:10px; color:#000;">
                            Alur Distribusi Keuntungan:
                        </div>
                        <ul style="list-style:none; padding:0; display:flex; flex-direction:column; gap:10px; font-size:0.92rem; color:var(--text-muted);">
                            <li><i class="bi bi-check-circle-fill text-success" style="margin-right:8px;"></i> Website store komunitas memproses transaksi seller.</li>
                            <li><i class="bi bi-check-circle-fill text-success" style="margin-right:8px;"></i> Pembayaran otomatis diverifikasi via gateway terpercaya.</li>
                            <li><i class="bi bi-check-circle-fill text-success" style="margin-right:8px;"></i> Persentase bagi hasil komisi dialokasikan langsung ke saldo mitra.</li>
                            <li><i class="bi bi-check-circle-fill text-success" style="margin-right:8px;"></i> Saldo komisi dapat ditarik (*withdraw*) berkala ke rekening bank.</li>
                        </ul>
                    </div>
                </div>
                <div class="revenue-steps reveal" style="transition-delay: .15s;">
                    <div class="rev-step">
                        <div class="rev-step-num">1</div>
                        <div class="rev-step-text">
                            <h5>Ajukan Kemitraan Komunitas</h5>
                            <p>Daftarkan komunitas Anda dan ajukan nama subdomain khusus yang Anda inginkan secara gratis.</p>
                        </div>
                    </div>
                    <div class="rev-step">
                        <div class="rev-step-num">2</div>
                        <div class="rev-step-text">
                            <h5>Aktivasi Website Toko</h5>
                            <p>Tim IT {{ $siteName }} menyiapkan subdomain komunitas Anda dalam waktu cepat.</p>
                        </div>
                    </div>
                    <div class="rev-step">
                        <div class="rev-step-num">3</div>
                        <div class="rev-step-text">
                            <h5>Ajak Seller Berjualan</h5>
                            <p>Pandu seller digital di komunitas Anda untuk mendaftar dan memajang produk mereka.</p>
                        </div>
                    </div>
                    <div class="rev-step">
                        <div class="rev-step-num">4</div>
                        <div class="rev-step-text">
                            <h5>Nikmati Bagi Hasil Komisi</h5>
                            <p>Setiap transaksi sukses di website Anda menghasilkan saldo bagi hasil komisi otomatis.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- FAQ SECTION -->
    <section id="faq" class="section-wrap alt">
        <div class="container-custom">
            <div class="section-header centered reveal">
                
                <h2 class="section-title">Pertanyaan Seputar <span class="highlight">Join Partner</span></h2>
                <p class="section-subtitle">Pelajari seluk-beluk program kemitraan komunitas store dan cara memulai kolaborasi bersama kami.</p>
            </div>
            <div class="faq-accordion reveal">
                <div class="faq-item">
                    <button class="faq-question" onclick="toggleFaq(this)">
                        <span>Apa itu program Join Partner Komunitas {{ $siteName }}?</span>
                        <i class="bi bi-chevron-down"></i>
                    </button>
                    <div class="faq-answer">
                        Program kemitraan resmi yang memungkinkan komunitas (regional, gamer, hobi, kampus, atau sekolah) memiliki website e-commerce digital sendiri dengan subdomain khusus secara gratis tanpa biaya server.
                    </div>
                </div>
                <div class="faq-item">
                    <button class="faq-question" onclick="toggleFaq(this)">
                        <span>Apakah ada biaya pembuatan website atau biaya bulanan?</span>
                        <i class="bi bi-chevron-down"></i>
                    </button>
                    <div class="faq-answer">
                        Sama sekali tidak ada biaya setup, instalasi, maupun sewa bulanan (100% Gratis). Kemitraan berjalan murni dengan sistem pembagian hasil komisi transaksi yang adil dan transparan.
                    </div>
                </div>
                <div class="faq-item">
                    <button class="faq-question" onclick="toggleFaq(this)">
                        <span>Siapa yang mengelola server dan sistem pembayarannya?</span>
                        <i class="bi bi-chevron-down"></i>
                    </button>
                    <div class="faq-answer">
                        Seluruh infrastruktur server, payment gateway (QRIS, Bank, e-Wallet), keamanan enkripsi, dan otomasi pengiriman produk ditangani penuh oleh tim {{ $siteName }}. Mitra cukup fokus merangkul seller dan mempromosikan toko komunitas.
                    </div>
                </div>
                <div class="faq-item">
                    <button class="faq-question" onclick="toggleFaq(this)">
                        <span>Bagaimana cara mencairkan saldo bagi hasil komisi mitra?</span>
                        <i class="bi bi-chevron-down"></i>
                    </button>
                    <div class="faq-answer">
                        Bagi hasil komisi dihitung secara real-time dari setiap transaksi sukses di website komunitas Anda dan dapat dicairkan (*withdraw*) langsung ke rekening bank mitra kapan saja.
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CONTACT REGISTRATION SECTION -->
    <section id="register" class="section-wrap">
        <div class="container-custom">
            <div class="glass-card registration-box reveal">
                
                <h2>Mulai Hub E-Commerce Komunitas Anda Sekarang</h2>
                <p>Diskusikan pendaftaran kemitraan komunitas store Anda bersama tim representatif dari {{ $siteName }}. Hubungi kami melalui salah satu kontak di bawah ini.</p>
                <div class="action-buttons">
                    <a href="https://wa.me/{{ $phoneClean }}?text=Halo%20Tim%20{{ urlencode($siteName) }}%2C%20saya%20tertarik%20untuk%20join%20partner%20community%20store%20tim%20atau%20komunitas%20saya.%20Mohon%20informasi%20langkah%20selanjutnya." target="_blank" class="btn-whatsapp">
                        <span><i class="bi bi-whatsapp" style="font-size: 1.4rem; margin-right: 6px;"></i> Hubungi via WhatsApp</span>
                        <span class="small">Respon Cepat (Jam Kerja 09:00 - 21:00 WIB)</span>
                    </a>
                    <a href="mailto:{{ $contactEmail }}?subject=Permohonan%20Kemitraan%20Join%20Partner%20{{ urlencode($siteName) }}&body=Halo%20Tim%20Kemitraan%20{{ urlencode($siteName) }}%2C%0A%0ASaya%20mewakili%20komunitas%20ingin%20mengajukan%20kemitraan%20Join%20Partner%20Community%20Store.%0A-%20Nama%20Komunitas%3A%20%0A-%20Usulan%20Nama%20Subdomain%3A%20%0A-%20Perkiraan%20Jumlah%20Seller%20Komunitas%3A%20%0A%0AMohon%20tindak%20lanjutnya.%20Terima%20kasih." class="btn-gmail">
                        <span><i class="bi bi-envelope-fill" style="font-size: 1.4rem; margin-right: 6px;"></i> Hubungi via Email</span>
                        <span class="small">Respon Resmi &amp; Penawaran Kerjasama</span>
                    </a>
                </div>
            </div>
        </div>
    </section>
@endsection
