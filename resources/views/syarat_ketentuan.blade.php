@extends('layouts.landing')

@section('title', 'Syarat & Ketentuan - ' . (isset($websiteSettings) && $websiteSettings->site_name ? $websiteSettings->site_name : 'Lapaktifikasi'))
@section('meta_title', 'Syarat & Ketentuan Layanan & Garansi - ' . (isset($websiteSettings) && $websiteSettings->site_name ? $websiteSettings->site_name : 'Lapaktifikasi'))
@section('meta_description', 'Syarat dan Ketentuan penggunaan platform ' . (isset($websiteSettings) && $websiteSettings->site_name ? $websiteSettings->site_name : 'Lapaktifikasi') . '. Pelajari hak, kewajiban, ketentuan garansi akun premium, dan sistem pengiriman file digital kami.')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/pages/legal.css') }}">
@endpush

@push('schema')
<script type="application/ld+json">
{
  "{{ '@context' }}": "https://schema.org",
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
      "name": "Syarat & Ketentuan",
      "item": "{{ url('/syarat-ketentuan') }}"
    }
  ]
}
</script>
@endpush

@section('content')
    <!-- HEADER -->
    <header class="page-header">
        <div class="container-custom">
            <div class="page-badge"><i class="bi bi-file-earmark-text-fill"></i> Syarat &amp; Ketentuan</div>
            <h1 class="page-title">Syarat &amp; Ketentuan Layanan</h1>
            <p class="page-subtitle">Ketentuan penggunaan platform dan jaminan garansi resmi di {{ isset($websiteSettings) && $websiteSettings->site_name ? $websiteSettings->site_name : 'Lapaktifikasi' }}</p>
        </div>
    </header>

    <!-- CONTENT -->
    <div class="container-custom" style="padding-bottom: 80px;">
        <div class="legal-content">
            <div class="legal-section">
                <h3>1. Ketentuan Umum</h3>
                <p>Dengan mendaftar, mengakses, atau membeli layanan melalui platform <strong>{{ isset($websiteSettings) && $websiteSettings->site_name ? $websiteSettings->site_name : 'Lapaktifikasi' }}</strong>, Anda menyatakan bahwa Anda telah membaca, memahami, dan menyetujui seluruh Syarat dan Ketentuan yang berlaku.</p>
            </div>

            <div class="legal-section">
                <h3>2. Akun Pengguna &amp; Tanggung Jawab</h3>
                <ul>
                    <li>Pengguna bertanggung jawab penuh untuk menjaga kerahasiaan kata sandi dan informasi akun pribadinya.</li>
                    <li>Pengguna wajib memberikan informasi yang valid, termasuk nomor WhatsApp dan email aktif untuk keperluan pengiriman kredensial akun digital.</li>
                    <li>Dilarang keras menyalahgunakan akun digital yang telah dibeli untuk aktivitas ilegal, spamming, atau melanggar hak cipta penyedia layanan resmi.</li>
                </ul>
            </div>

            <div class="legal-section">
                <h3>3. Pemesanan &amp; Pembayaran</h3>
                <ul>
                    <li>Setiap pesanan yang dibuat memiliki batas waktu pembayaran (expiry time). Pesanan yang tidak dibayar dalam batas waktu tersebut akan dibatalkan secara otomatis.</li>
                    <li>Pembayaran dinyatakan sah setelah sistem menerima konfirmasi sukses dari Payment Gateway resmi.</li>
                    <li>Kredensial layanan akun digital atau akses unduhan produk digital akan diberikan seketika setelah pembayaran sukses terverifikasi.</li>
                </ul>
            </div>

            <div class="legal-section">
                <h3>4. Kebijakan Garansi &amp; Klaim Masalah</h3>
                <p>{{ isset($websiteSettings) && $websiteSettings->site_name ? $websiteSettings->site_name : 'Lapaktifikasi' }} berkomitmen memberikan garansi penuh sesuai dengan durasi masa aktif layanan yang tertera pada setiap varian produk:</p>
                <ul>
                    <li><strong>Masa Garansi:</strong> Garansi berlaku selama masa aktif layanan masih berjalan sejak tanggal transaksi berhasil.</li>
                    <li><strong>Klaim Garansi:</strong> Jika terjadi kendala pada akun (misal: password tidak sesuai / logout), pengguna dapat mengajukan laporan melalui menu <em>Laporan Masalah</em> di dashboard atau menghubungi WhatsApp Support resmi.</li>
                    <li><strong>Solusi:</strong> Tim kami akan melakukan perbaikan atau penggantian kredensial akun baru maksimal 1x24 jam sejak laporan diverifikasi.</li>
                </ul>
            </div>

            <div class="legal-section">
                <h3>5. Kebijakan Pengembalian Dana (Refund)</h3>
                <p>Pengembalian dana (refund) hanya dapat disetujui apabila produk yang dipesan kehabisan stok atau tim teknis kami tidak dapat memberikan penggantian akun yang berfungsi dalam kurun waktu 3x24 jam.</p>
            </div>

            <div class="legal-section">
                <h3>6. Perubahan Ketentuan</h3>
                <p>{{ isset($websiteSettings) && $websiteSettings->site_name ? $websiteSettings->site_name : 'Lapaktifikasi' }} berhak memperbarui Syarat dan Ketentuan ini sewaktu-waktu tanpa pemberitahuan sebelumnya. Perubahan akan berlaku segera setelah dipublikasikan pada halaman ini.</p>
            </div>
        </div>
    </div>
@endsection
