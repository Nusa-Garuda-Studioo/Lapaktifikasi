@extends('layouts.landing')

@section('title', 'Kebijakan Privasi - ' . (isset($websiteSettings) && $websiteSettings->site_name ? $websiteSettings->site_name : 'Lapaktifikasi'))
@section('meta_title', 'Kebijakan Privasi - Perlindungan & Keamanan Data Pengguna | ' . (isset($websiteSettings) && $websiteSettings->site_name ? $websiteSettings->site_name : 'Lapaktifikasi'))
@section('meta_description', 'Kebijakan Privasi ' . (isset($websiteSettings) && $websiteSettings->site_name ? $websiteSettings->site_name : 'Lapaktifikasi') . ' menjelaskan bagaimana kami mengumpulkan, mengelola, dan melindungi keamanan data pribadi transaksi Anda dengan standar enkripsi tinggi.')

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
      "name": "Kebijakan Privasi",
      "item": "{{ url('/kebijakan-privasi') }}"
    }
  ]
}
</script>
@endpush

@section('content')
    <!-- HEADER -->
    <header class="page-header">
        <div class="container-custom">
            <div class="page-badge"><i class="bi bi-shield-lock-fill"></i> Kebijakan Privasi</div>
            <h1 class="page-title">Kebijakan Privasi</h1>
            <p class="page-subtitle">Komitmen kami terhadap perlindungan dan kerahasiaan data pengguna di {{ isset($websiteSettings) && $websiteSettings->site_name ? $websiteSettings->site_name : 'Lapaktifikasi' }}</p>
        </div>
    </header>

    <!-- CONTENT -->
    <div class="container-custom" style="padding-bottom: 80px;">
        <div class="legal-content">
            <div class="legal-section">
                <h3>1. Pendahuluan</h3>
                <p>Selamat datang di <strong>{{ isset($websiteSettings) && $websiteSettings->site_name ? $websiteSettings->site_name : 'Lapaktifikasi' }}</strong>. Kami sangat menghargai privasi dan kepercayaan Anda. Kebijakan Privasi ini menjelaskan bagaimana kami mengumpulkan, menggunakan, menyimpan, dan melindungi informasi pribadi yang Anda berikan saat mengakses dan menggunakan platform kami.</p>
            </div>

            <div class="legal-section">
                <h3>2. Informasi yang Kami Kumpulkan</h3>
                <p>Kami mengumpulkan informasi yang Anda berikan secara langsung saat berinteraksi dengan platform kami, meliputi:</p>
                <ul>
                    <li><strong>Data Identitas:</strong> Nama lengkap, alamat email, dan nomor WhatsApp aktif.</li>
                    <li><strong>Data Transaksi:</strong> Riwayat pembelian produk/layanan digital, ID pesanan (order_id), metode pembayaran yang dipilih, dan status transaksi.</li>
                    <li><strong>Data Kredensial Layanan:</strong> Kredensial akun digital atau link file digital yang dikirimkan secara otomatis untuk pemenuhan pesanan Anda.</li>
                </ul>
            </div>

            <div class="legal-section">
                <h3>3. Penggunaan Informasi</h3>
                <p>Informasi yang kami kumpulkan digunakan untuk tujuan:</p>
                <ul>
                    <li>Memproses dan menyelesaikan transaksi pembelian layanan digital Anda secara otomatis.</li>
                    <li>Mengirimkan notifikasi invoice, status pembayaran, dan detail kredensial via WhatsApp/Email.</li>
                    <li>Memberikan layanan bantuan pelanggan (Customer Support) dan klaim garansi produk resmi.</li>
                    <li>Meningkatkan kualitas fitur, performa sistem, serta keamanan platform secara berkala.</li>
                </ul>
            </div>

            <div class="legal-section">
                <h3>4. Keamanan &amp; Perlindungan Data</h3>
                <p>Kami menerapkan standar keamanan teknis dan enkripsi data untuk melindungi informasi pribadi Anda dari akses tidak sah, pengubahan, pengungkapan, atau penghancuran yang melanggar hukum. Kami tidak pernah menjual atau menyewakan informasi pribadi Anda kepada pihak ketiga.</p>
            </div>

            <div class="legal-section">
                <h3>5. Pembayaran &amp; Gateway Pihak Ketiga</h3>
                <p>Seluruh transaksi pembayaran diproses melalui payment gateway resmi dan terpercaya (Midtrans / TriPay / Pakasir). Kami tidak menyimpan rincian nomor kartu kredit atau data rahasia perbankan Anda di server kami.</p>
            </div>

            <div class="legal-section">
                <h3>6. Hubungi Kami</h3>
                <p>Jika Anda memiliki pertanyaan, saran, atau keluhan terkait Kebijakan Privasi ini, silakan hubungi tim kami melalui:</p>
                <ul>
                    <li><strong>WhatsApp Support:</strong> <a href="https://wa.me/{{ isset($websiteSettings) && $websiteSettings->contact_phone ? preg_replace('/[^0-9]/', '', $websiteSettings->contact_phone) : '6287897600086' }}" target="_blank" style="color:#000;font-weight:700;text-decoration:none;">{{ isset($websiteSettings) && $websiteSettings->contact_phone ? $websiteSettings->contact_phone : '+62 878-9760-0086' }}</a></li>
                    <li><strong>Email Dukungan:</strong> <a href="mailto:{{ isset($websiteSettings) && $websiteSettings->contact_email ? $websiteSettings->contact_email : 'support@lapaktifikasi.com' }}" style="color:#000;font-weight:700;text-decoration:none;">{{ isset($websiteSettings) && $websiteSettings->contact_email ? $websiteSettings->contact_email : 'support@lapaktifikasi.com' }}</a></li>
                </ul>
            </div>
        </div>
    </div>
@endsection
