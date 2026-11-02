@extends('layout')

@section('title', 'Berita & Informasi Terbaru')
@section('meta_title', 'Berita, Panduan & Edukasi Digital - Lapaktifikasi')
@section('meta_description', 'Pusat berita resmi, tips teknologi, tutorial produk digital, update akun premium, dan pengumuman promo spesial dari Lapaktifikasi.')
@section('meta_keywords', 'berita lapaktifikasi, tips produk digital, panduan akun premium, promo digital, berita teknologi, update marketplace')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/pages/customer-news.css') }}">
@endpush

@push('schema')
<script type="application/ld+json">
{
  "{{ '@context' }}": "https://schema.org",
  "@type": "CollectionPage",
  "name": "Berita & Informasi Lapaktifikasi",
  "description": "Kumpulan artikel, panduan, dan berita seputar produk digital dan akun premium.",
  "url": "{{ url('/news') }}",
  "mainEntity": {
    "@type": "ItemList",
    "name": "Daftar Berita Terbaru Lapaktifikasi",
    "itemListElement": [
      @foreach($newsList->take(20) as $index => $item)
      {
        "@type": "ListItem",
        "position": {{ $index + 1 }},
        "name": {!! json_encode($item->judul) !!},
        "url": "{{ route('news.show', $item->slug) }}"
      }@if(!$loop->last),@endif
      @endforeach
    ]
  }
}
</script>
@endpush

@section('content')

<div class="container-fluid py-2">
    <!-- Header Banner -->
    <div class="mb-4">
        <h1 class="h3 font-weight-bold text-dark mb-1">Berita & Informasi Terbaru</h1>
        <p class="text-muted small mb-0">Temukan informasi update, promo spesial, dan berita seputar platform Lapaktifikasi.</p>
    </div>

    <!-- News Grid -->
    <div class="row">
        @forelse($newsList as $item)
        <div class="col-12 col-md-6 col-lg-4 mb-4">
            <!-- Seluruh Card Clickable mengarah ke news.show -->
            <a href="{{ route('news.show', $item->slug) }}" class="news-card-link">
                <div class="news-card">
                    <!-- 1. Gambar -->
                    <div class="news-thumbnail-wrapper">
                        @if($item->gambar)
                            <img src="{{ $item->gambar_url }}" alt="{{ $item->judul }}" class="news-thumbnail-img" loading="lazy">
                        @else
                            <div class="news-placeholder-img">
                                <i class="bi bi-newspaper"></i>
                            </div>
                        @endif
                    </div>

                    <!-- 2. Konten Card: Judul & Subjudul -->
                    <div class="news-card-body">
                        <h2 class="news-title">{{ $item->judul }}</h2>
                        @if($item->subjudul)
                            <p class="news-subtitle">{{ $item->subjudul }}</p>
                        @endif
                    </div>
                </div>
            </a>
        </div>
        @empty
        <div class="col-12 text-center py-5">
            <div class="py-5 bg-white rounded-lg shadow-sm border">
                <i class="bi bi-newspaper display-4 text-muted d-block mb-3" style="opacity: 0.35;"></i>
                <h5 class="font-weight-bold text-dark">Belum Ada Berita Terbaru</h5>
                <p class="text-muted small mb-0">Saat ini belum ada artikel atau pengumuman yang dipublikasikan. Silakan cek kembali nanti!</p>
            </div>
        </div>
        @endforelse
    </div>

    <!-- Pagination -->
    @if($newsList->hasPages())
    <div class="d-flex justify-content-center mt-4">
        {{ $newsList->links() }}
    </div>
    @endif
</div>
@endsection
