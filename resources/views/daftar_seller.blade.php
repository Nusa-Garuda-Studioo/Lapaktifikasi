@extends('layouts.landing')

@php
    $siteName = isset($websiteSettings) && $websiteSettings->site_name ? $websiteSettings->site_name : 'Lapaktifikasi';
    $siteDesc = isset($websiteSettings) && $websiteSettings->site_description ? $websiteSettings->site_description : 'Marketplace Produk Digital, Source Code & Akun Premium Terpercaya';
    $phoneRaw = isset($websiteSettings) && $websiteSettings->contact_phone ? $websiteSettings->contact_phone : '081234567890';
    $phoneClean = preg_replace('/[^0-9]/', '', $phoneRaw);
    if (str_starts_with($phoneClean, '0')) {
        $phoneClean = '62' . substr($phoneClean, 1);
    }
    $contactEmail = isset($websiteSettings) && $websiteSettings->contact_email ? $websiteSettings->contact_email : 'support@lapaktifikasi.com';
    $logoUrl = isset($websiteSettings) && $websiteSettings->logo_path ? asset($websiteSettings->logo_path) : asset('assets/img/brand_ambassador.jpg');
    $canonicalUrl = url('/daftar-jadi-seller');
@endphp

@section('title', 'Daftar Jadi Seller Resmi - Jual Produk Digital & Akun Premium | ' . $siteName)
@section('meta_title', 'Daftar Jadi Seller Resmi | ' . $siteName)
@section('meta_description', 'Bergabunglah sebagai mitra seller resmi di ' . $siteName . '. Jual akun premium, source code aplikasi, file digital, dan produk kreatif dengan sistem pembayaran serba otomatis dan bebas komisi di bulan pertama.')
@section('og_image', $logoUrl)

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/pages/seller.css') }}">
@endpush

@push('schema')
<script type="application/ld+json">
{
  "{{ '@context' }}": "https://schema.org",
  "@type": "WebPage",
  "name": "Daftar Jadi Seller Resmi - {{ $siteName }}",
  "url": "{{ $canonicalUrl }}",
  "description": "Program kemitraan seller resmi Lapaktifikasi untuk kreator file digital dan penyedia akun premium.",
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
        "name": "Daftar Jadi Seller",
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
    <li><a href="#alur">Alur Daftar</a></li>
    <li><a href="#skema">Skema Komisi</a></li>
    <li><a href="#faq">FAQ</a></li>
    <li><a href="{{ route('daftar.seller') }}" class="active">Jadi Seller</a></li>
    <li><a href="{{ route('join.partner') }}">Join Partner</a></li>
@endsection

@section('mobile_nav_links')
    <li><a href="{{ url('/') }}" onclick="closeMobileMenu()">Beranda</a></li>
    <li><a href="{{ route('premium.katalog') }}" onclick="closeMobileMenu()">Katalog Produk</a></li>
    <li><a href="#kelebihan" onclick="closeMobileMenu()">Kelebihan Seller</a></li>
    <li><a href="#alur" onclick="closeMobileMenu()">Alur Pendaftaran</a></li>
    <li><a href="#skema" onclick="closeMobileMenu()">Skema &amp; Ketentuan</a></li>
    <li><a href="#faq" onclick="closeMobileMenu()">FAQ Seller</a></li>
    <li><a href="{{ route('daftar.seller') }}" onclick="closeMobileMenu()">Jadi Seller</a></li>
    <li><a href="{{ route('join.partner') }}" onclick="closeMobileMenu()">Join Partner</a></li>
@endsection

@section('content')
    <!-- HERO SECTION -->
    <header id="hero-seller">
        <div class="hero-seller-inner">
           
            <h1 class="hero-title reveal" style="transition-delay: .1s;">
                Raih Penghasilan Maksimal dari<br>
                <span class="grad-text">Produk Digital &amp; Akun Premium</span>
            </h1>
            <p class="hero-desc reveal" style="transition-delay: .2s;">
                Bergabunglah bersama puluhan seller dan kreator di ekosistem <strong>{{ $siteName }}</strong>. Dapatkan akses ke ribuan pembeli potensial dengan sistem transaksi serba otomatis, multi payment gateway resmi, dan <strong>gratis biaya komisi di bulan pertama</strong>!
            </p>
            <div class="hero-cta-group reveal" style="transition-delay: .3s;">
                <a href="#daftar" class="btn-primary"><i class="bi bi-send-fill"></i> Hubungi &amp; Daftar Sekarang</a>
                <a href="#skema" class="btn-secondary"><i class="bi bi-shield-check"></i> Skema Komisi Transparan</a>
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

    <!-- KELEBIHAN SELLER SECTION -->
    <section id="kelebihan" class="section-wrap alt">
        <div class="container-custom">
            <div class="section-header centered reveal">
                
                <h2 class="section-title">Infrastruktur Unggul untuk <span class="highlight">Bisnis Digital Anda</span></h2>
                <p class="section-subtitle">Kami sediakan seluruh teknologi backend, gerbang pembayaran, hingga perlindungan garansi agar Anda bisa fokus memproduksi produk bermutu.</p>
            </div>
            <div class="benefits-grid">
                <div class="glass-card benefit-card reveal">
                    <div class="benefit-icon"><i class="bi bi-credit-card-2-front-fill"></i></div>
                    <h3>Gerbang Pembayaran Otomatis</h3>
                    <p>Mendukung QRIS instan, Transfer Bank (BCA, BNI, BRI, Mandiri), dan e-Wallet. Pembayaran otomatis diverifikasi dalam hitungan detik tanpa perlu konfirmasi manual.</p>
                </div>
                <div class="glass-card benefit-card reveal" style="transition-delay: .1s;">
                    <div class="benefit-icon"><i class="bi bi-gift-fill"></i></div>
                    <h3>Bebas Biaya Komisi Bulan Ke-1</h3>
                    <p>Nikmati <strong>100% margin keuntungan</strong> Anda selama bulan pertama bergabung. Potongan komisi ringan baru diberlakukan pada bulan kedua dari transaksi yang berhasil.</p>
                </div>
                <div class="glass-card benefit-card reveal" style="transition-delay: .2s;">
                    <div class="benefit-icon"><i class="bi bi-sliders2-vertical"></i></div>
                    <h3>Dashboard Seller Mandiri</h3>
                    <p>Kelola inventaris akun premium, upload file ZIP/source code, atur varian harga, dan pantau grafik penjualan harian secara terpadu di dashboard modern Anda.</p>
                </div>
                <div class="glass-card benefit-card reveal" style="transition-delay: .3s;">
                    <div class="benefit-icon"><i class="bi bi-shield-lock-fill"></i></div>
                    <h3>Keamanan &amp; Anti-Fraud</h3>
                    <p>Sistem kami dilengkapi pembatasan login, enkripsi kata sandi kredensial akun, serta perlindungan token transaksi untuk mencegah penipuan.</p>
                </div>
                <div class="glass-card benefit-card reveal" style="transition-delay: .4s;">
                    <div class="benefit-icon"><i class="bi bi-people-fill"></i></div>
                    <h3>Jangkauan Ribuan Pembeli</h3>
                    <p>Terhubung langsung dengan jaringan siswa, mahasiswa, profesional IT, komunitas gaming, dan institusi pendidikan yang rutin mencari produk digital.</p>
                </div>
                <div class="glass-card benefit-card reveal" style="transition-delay: .5s;">
                    <div class="benefit-icon"><i class="bi bi-headset"></i></div>
                    <h3>Bantuan Dukungan Prioritas</h3>
                    <p>Tim support {{ $siteName }} siap membantu Anda mengatasi kendala operasional toko, transaksi pembeli, hingga integrasi teknis setiap saat.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- ALUR PENDAFTARAN (4 STEPS) -->
    <section id="alur" class="section-wrap">
        <div class="container-custom">
            <div class="section-header centered reveal">
                
                <h2 class="section-title">4 Langkah Cepat Menjadi <span class="highlight">Seller Aktif</span></h2>
                <p class="section-subtitle">Tanpa formulir rumit atau proses birokrasi berbelit. Toko Anda bisa online dan menghasilkan dalam hitungan jam.</p>
            </div>
            <div class="steps-grid">
                <div class="glass-card step-card reveal">
                    <div class="step-num">01</div>
                    <h4>Hubungi Tim Kami</h4>
                    <p>Hubungi admin via WhatsApp atau Email dengan menyertakan portofolio atau jenis produk digital yang ingin Anda jual.</p>
                </div>
                <div class="glass-card step-card reveal" style="transition-delay: .1s;">
                    <div class="step-num">02</div>
                    <h4>Kurasi &amp; Diskusi</h4>
                    <p>Tim kami akan meninjau kelayakan produk dan menyepakati penamaan toko serta kategori produk Anda.</p>
                </div>
                <div class="glass-card step-card reveal" style="transition-delay: .2s;">
                    <div class="step-num">03</div>
                    <h4>Aktivasi Akun Toko</h4>
                    <p>Akun seller Anda diaktifkan dan Anda mendapatkan akses langsung ke dashboard manajemen toko.</p>
                </div>
                <div class="glass-card step-card reveal" style="transition-delay: .3s;">
                    <div class="step-num">04</div>
                    <h4>Mulai Jualan</h4>
                    <p>Masukkan stok akun atau unggah file ZIP karya Anda. Terima hasil penjualan otomatis langsung ke rekening!</p>
                </div>
            </div>
        </div>
    </section>

    <!-- KETENTUAN & MAINTENANCE SECTION -->
    <section id="skema" class="section-wrap alt">
        <div class="container-custom">
            <div class="section-header centered reveal">
                
                <h2 class="section-title">Infrastruktur &amp; <span class="highlight">Skema Kerjasama</span></h2>
                <p class="section-subtitle">Informasi penting mengenai skema potongan komisi, integrasi gerbang pembayaran, dan jadwal pemeliharaan server berkala demi keandalan bisnis Anda.</p>
            </div>
            <div class="benefits-grid">
                <div class="glass-card benefit-card reveal">
                    <div class="benefit-icon"><i class="bi bi-shield-fill-check"></i></div>
                    <h3>Gerbang Pembayaran Resmi</h3>
                    <p>Seluruh transaksi pelanggan diproses melalui payment gateway terverifikasi resmi. Biaya administrasi gateway dibebankan secara transparan kepada pembeli per transaksi.</p>
                </div>
                <div class="glass-card benefit-card reveal" style="transition-delay: .1s;">
                    <div class="benefit-icon"><i class="bi bi-award-fill"></i></div>
                    <h3>Skema Komisi Ringan</h3>
                    <p>Nikmati <strong>gratis biaya potongan komisi pada bulan pertama</strong> Anda bergabung. Potongan komisi baru akan diberlakukan pada bulan kedua murni dari transaksi yang berhasil.</p>
                </div>
                <div class="glass-card benefit-card reveal" style="transition-delay: .2s;">
                    <div class="benefit-icon"><i class="bi bi-clock-history"></i></div>
                    <h3>Jadwal Pemeliharaan Rutin</h3>
                    <p>Pemeliharaan rutin terjadwal setiap <strong>hari Minggu pukul 24:00 s.d 01:00 WIB</strong> (1 jam), serta pemeliharaan bulanan antara pukul <strong>22:00 s.d 03:00 WIB</strong>.</p>
                </div>
                <div class="glass-card benefit-card reveal" style="transition-delay: .3s;">
                    <div class="benefit-icon"><i class="bi bi-file-earmark-bar-graph-fill"></i></div>
                    <h3>Laporan &amp; Changelog Terbuka</h3>
                    <p>Kami menyajikan laporan berkala dan <strong>changelog transparan</strong> hasil pemeliharaan sistem agar seller selalu mengetahui peningkatan performa platform.</p>
                </div>
                <div class="glass-card benefit-card reveal" style="transition-delay: .4s;">
                    <div class="benefit-icon"><i class="bi bi-cpu-fill"></i></div>
                    <h3>Audit Keamanan Berkala</h3>
                    <p>Setiap maintenance berfokus pada <strong>keamanan sistem</strong>, optimasi performa database query, dan pencegahan bug untuk menjamin uptime transaksi di atas 99.8%.</p>
                </div>
                <div class="glass-card benefit-card reveal" style="transition-delay: .5s;">
                    <div class="benefit-icon"><i class="bi bi-palette-fill"></i></div>
                    <h3>Pembaruan Tampilan UI/UX</h3>
                    <p>Untuk menjaga antusiasme pembeli, platform {{ $siteName }} melakukan <strong>redesain layout &amp; tampilan berkala</strong> agar selalu segar, elegan, dan tingkat konversi penjualan tetap tinggi.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- FAQ SECTION -->
    <section id="faq" class="section-wrap">
        <div class="container-custom">
            <div class="section-header centered reveal">
                
                <h2 class="section-title">Pertanyaan Seputar <span class="highlight">Seller</span></h2>
                <p class="section-subtitle">Temukan jawaban atas pertanyaan yang paling sering ditanyakan seputar pendaftaran dan pengelolaan toko di {{ $siteName }}.</p>
            </div>
            <div class="faq-accordion reveal">
                <div class="faq-item">
                    <button class="faq-question" onclick="toggleFaq(this)">
                        <span>Berapa biaya pendaftaran menjadi seller di {{ $siteName }}?</span>
                        <i class="bi bi-chevron-down"></i>
                    </button>
                    <div class="faq-answer">
                        Pendaftaran seller 100% gratis tanpa biaya formulir ataupun sewa bulanan. Anda bahkan menikmati gratis potongan komisi pada bulan pertama Anda bergabung.
                    </div>
                </div>
                <div class="faq-item">
                    <button class="faq-question" onclick="toggleFaq(this)">
                        <span>Produk apa saja yang dapat saya jual di sini?</span>
                        <i class="bi bi-chevron-down"></i>
                    </button>
                    <div class="faq-answer">
                        Anda dapat menjual akun premium (seperti streaming, tools produktivitas, lisensi software, edukasi) serta produk file digital (source code project, template website, e-book, modul edukasi, dan aset grafis).
                    </div>
                </div>
                <div class="faq-item">
                    <button class="faq-question" onclick="toggleFaq(this)">
                        <span>Bagaimana cara mencairkan saldo hasil penjualan?</span>
                        <i class="bi bi-chevron-down"></i>
                    </button>
                    <div class="faq-answer">
                        Seluruh dana transaksi diverifikasi otomatis oleh Payment Gateway. Saldo penjualan di dashboard seller Anda dapat ditarik (*withdraw*) langsung ke rekening bank atau dompet digital Anda kapan saja sesuai jam operasional pencairan.
                    </div>
                </div>
                <div class="faq-item">
                    <button class="faq-question" onclick="toggleFaq(this)">
                        <span>Berapa lama proses verifikasi hingga toko saya aktif?</span>
                        <i class="bi bi-chevron-down"></i>
                    </button>
                    <div class="faq-answer">
                        Setelah Anda menghubungi admin via WhatsApp atau Email dan menyampaikan katalog produk Anda, akun seller dan toko digital Anda akan diverifikasi dan siap beroperasi dalam kurun waktu kurang dari 24 jam.
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CONTACT REGISTRATION SECTION -->
    <section id="daftar" class="section-wrap alt">
        <div class="container-custom">
            <div class="glass-card registration-box reveal">
               
                <h2>Mulai Langkah Sukses Toko Digital Anda</h2>
                <p>Kami tidak menggunakan formulir yang panjang dan rumit. Hubungi tim kami langsung melalui WhatsApp atau Email untuk pendaftaran instan.</p>
                <div class="action-buttons">
                    <a href="https://wa.me/{{ $phoneClean }}?text=Halo%20Admin%20{{ urlencode($siteName) }}%2C%20saya%20tertarik%20untuk%20mendaftar%20sebagai%20seller%20toko%20produk%20digital.%20Mohon%20informasi%20langkah%20selanjutnya." target="_blank" class="btn-whatsapp">
                        <span><i class="bi bi-whatsapp" style="font-size: 1.4rem; margin-right: 6px;"></i> Hubungi via WhatsApp</span>
                        <span class="small">Respon Cepat &amp; Diskusi Langsung</span>
                    </a>
                    <a href="mailto:{{ $contactEmail }}?subject=Pendaftaran%20Seller%20{{ urlencode($siteName) }}&body=Halo%20Admin%20{{ urlencode($siteName) }}%2C%0A%0ASaya%20tertarik%20untuk%20mendaftar%20sebagai%20seller%20di%20platform%20Anda.%20Berikut%20detail%20singkat%20saya%3A%0A-%20Nama%20Toko%3A%20%0A-%20Kategori%20Produk%3A%20%0A%0AMohon%20panduannya.%20Terima%20kasih." class="btn-gmail">
                        <span><i class="bi bi-envelope-fill" style="font-size: 1.4rem; margin-right: 6px;"></i> Hubungi via Email</span>
                        <span class="small">Kirim Penawaran &amp; Proposal Resmi</span>
                    </a>
                </div>
            </div>
        </div>
    </section>
@endsection
