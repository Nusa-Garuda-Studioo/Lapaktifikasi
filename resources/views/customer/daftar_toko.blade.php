@extends('layout')

@section('title', 'Direktori Toko & Seller Terverifikasi')
@section('meta_title', 'Direktori Toko & Seller Terverifikasi - Lapaktifikasi')
@section('meta_description', 'Temukan toko seller digital resmi dan terverifikasi di Lapaktifikasi. Beli source code aplikasi, produk kreatif, dan akun premium dari penjual terpercaya dengan garansi resmi.')
@section('meta_keywords', 'daftar toko, direktori seller, marketplace digital, beli source code, akun premium, seller terverifikasi, toko lapaktifikasi')

@push('schema')
<script type="application/ld+json">
{
  "{{ '@context' }}": "https://schema.org",
  "@type": "CollectionPage",
  "name": "Direktori Toko & Seller Terverifikasi Lapaktifikasi",
  "description": "Daftar toko resmi dan penjual terverifikasi di marketplace Lapaktifikasi.",
  "url": "{{ url('/daftar_toko') }}",
  "mainEntity": {
    "@type": "ItemList",
    "name": "Daftar Toko Resmi Lapaktifikasi",
    "itemListElement": [
      @foreach($shops->take(30) as $index => $shop)
      {
        "@type": "ListItem",
        "position": {{ $index + 1 }},
        "name": {!! json_encode($shop->nama_toko) !!},
        "url": "{{ url('/toko/' . ($shop->slug ?: 'store')) }}"
      }@if(!$loop->last),@endif
      @endforeach
    ]
  }
}
</script>
@endpush

@section('content')

@if($success = Session::get('success'))
<script>
    Swal.fire({ title: "Berhasil", text: "{{ $success }}", icon: "success" });
</script>
@endif

<div class="row mb-4">
    <div class="col-12">
        <div class="p-4 p-md-5 rounded-2xl shadow-sm text-white" style="background: linear-gradient(135deg, #000000 0%, #1a1a1a 100%); border: 1px solid rgba(0,0,0,0.1); border-radius: 20px;">
            <div class="hero-inner">
                <span class="badge px-3 py-1.5 mb-2 font-weight-bold" style="background: rgba(255,255,255,0.15); border-radius: 50px; font-size: 0.78rem; letter-spacing: 1px;"><i class="bi bi-shop-window mr-1"></i> DIREKTORI SELLER RESMI</span>
                <h2 class="font-weight-bold mb-2" style="font-family: 'Space Grotesk', sans-serif; font-size: clamp(1.5rem, 3vw, 2.2rem);">Direktori Toko &amp; Seller Terverifikasi</h2>
                <p class="lead mb-0 text-white-50" style="font-size: 1rem; max-width: 750px;">Temukan dan jelajahi berbagai toko seller terpercaya di platform Lapaktifikasi untuk mendapatkan source code aplikasi, file kreatif, dan layanan digital berkualitas tinggi.</p>
            </div>
        </div>
    </div>
</div>

<div class="row">
    @forelse($shops as $shop)
    <div class="col-12 col-md-6 col-lg-4">
        <div class="card card-primary card-hover shadow-sm" style="transition: transform 0.2s; border-radius: 8px; overflow: hidden;">
            <!-- Store Logo Header -->
            <div class="d-flex align-items-center justify-content-center bg-light p-4" style="height: 150px; border-bottom: 1px solid #eaeaea;">
                @if($shop->logo_toko)
                    <img src="{{ asset('assets/img/logo_toko/' . $shop->logo_toko) }}" alt="{{ $shop->nama_toko }}" style="max-height: 110px; max-width: 100%; object-fit: contain; border-radius: 4px;">
                @else
                    <div class="rounded-circle bg-secondary d-flex align-items-center justify-content-center text-white" style="width: 80px; height: 80px; font-size: 2rem;">
                        <i class="fas fa-store"></i>
                    </div>
                @endif
            </div>

            <!-- Card Body -->
            <div class="card-body d-flex flex-column" style="min-height: 200px;">
                <h5 class="card-title text-dark mb-1"><strong>{{ $shop->nama_toko }}</strong></h5>
                
                <div class="mb-2 d-flex align-items-center" style="gap: 5px;">
                    @php
                        $rating = (float) $shop->rating_rata_rata;
                        $jumlahReview = (int) $shop->jumlah_review;
                        $fullStars = floor($rating);
                        $halfStar = ($rating - $fullStars) >= 0.5 ? 1 : 0;
                        $emptyStars = 5 - $fullStars - $halfStar;
                    @endphp
                    <span class="text-warning" style="font-size: 0.9rem;">
                        {!! str_repeat('<i class="bi bi-star-fill"></i>', $fullStars) !!}
                        {!! $halfStar ? '<i class="bi bi-star-half"></i>' : '' !!}
                        {!! str_repeat('<i class="bi bi-star"></i>', $emptyStars) !!}
                    </span>
                    <small class="text-muted" style="font-size: 0.8rem;">({{ number_format($rating, 1) }} / {{ $jumlahReview }} ulasan)</small>
                </div>
                
                <p class="text-muted flex-grow-1" style="font-size: 0.9rem;">
                    {{ $shop->informasi_toko ? Str::limit($shop->informasi_toko, 120) : 'Belum ada deskripsi profil untuk toko ini.' }}
                </p>

                <div class="my-2" style="font-size: 0.85rem;">
                    <div class="d-flex align-items-center mb-1">
                        <i class="fas fa-phone text-muted mr-2" style="width: 16px;"></i>
                        <span>{{ $shop->no_telp }}</span>
                    </div>
                    <div class="d-flex align-items-center">
                        <i class="fab fa-telegram text-info mr-2" style="width: 16px;"></i>
                        <span class="text-info">@&nbsp;{{ $shop->akun_telegram }}</span>
                    </div>
                </div>

                <a href="{{ url('toko/' . $shop->slug . '/produk') }}" class="btn btn-primary btn-block mt-3">
                    <i class="fas fa-shopping-bag mr-1"></i> Kunjungi Toko
                </a>
            </div>
        </div>
    </div>
    @empty
    <div class="col-12 text-center py-5">
        <div class="empty-state" data-height="300">
            <div class="empty-state-icon bg-secondary text-white">
                <i class="fas fa-store-slash"></i>
            </div>
            <h2>Belum ada toko aktif</h2>
            <p class="lead">Saat ini belum ada toko seller aktif yang dapat dikunjungi. Silakan periksa kembali nanti.</p>
        </div>
    </div>
    @endforelse
</div>

<!-- Pagination Links -->
<div class="row">
    <div class="col-12 d-flex justify-content-center mt-3">
        {{ $shops->links() }}
    </div>
</div>

@endsection
