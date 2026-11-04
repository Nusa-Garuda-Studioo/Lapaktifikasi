@extends('layout')

@php
    $newsDesc = Str::limit(strip_tags($news->subjudul ?? $news->konten), 160);
    $newsImg = $news->gambar_url ?? (isset($websiteSettings) && $websiteSettings->logo_path ? asset($websiteSettings->logo_path) : asset('assets/img/brand_ambassador.jpg'));
@endphp

@section('title', $news->judul)
@section('meta_title', $news->judul . ' - Berita Lapaktifikasi')
@section('meta_description', $newsDesc)
@section('og_type', 'article')
@section('og_image', $newsImg)

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/pages/customer-news.css') }}">
@endpush

@push('schema')
<!-- Schema.org NewsArticle Structured Data -->
<script type="application/ld+json">
{
  "{{ '@context' }}": "https://schema.org",
  "@type": "NewsArticle",
  "headline": {!! json_encode($news->judul) !!},
  "image": [
    {!! json_encode($newsImg) !!}
  ],
  "datePublished": "{{ $news->published_at ? $news->published_at->toAtomString() : $news->created_at->toAtomString() }}",
  "dateModified": "{{ $news->updated_at ? $news->updated_at->toAtomString() : now()->toAtomString() }}",
  "author": [{
      "@type": "Person",
      "name": "{{ $news->admin ? $news->admin->name : 'Redaksi Lapaktifikasi' }}"
  }],
  "publisher": {
      "@type": "Organization",
      "name": "Lapaktifikasi",
      "logo": {
        "@type": "ImageObject",
        "url": "{{ isset($websiteSettings) && $websiteSettings->logo_path ? asset($websiteSettings->logo_path) : asset('assets/img/visi_kami.svg') }}"
      }
  },
  "description": {!! json_encode($newsDesc) !!}
}
</script>
@endpush

@section('content')

<div class="container-fluid py-2">
    <!-- Tombol Kembali -->
    <div class="mb-4">
        <a href="{{ route('news.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left mr-1"></i> Kembali ke Semua Berita
        </a>
    </div>

    <!-- Artikel Card -->
    <div class="news-detail-card p-4 p-md-5 mb-5 mx-auto" style="max-width: 960px;">
        <!-- 1. Gambar Besar di Atas -->
        @if($news->gambar)
            <img src="{{ $news->gambar_url }}" alt="{{ $news->judul }}" class="news-detail-cover shadow-sm" loading="lazy">
        @endif

        <!-- 2. Judul Berita -->
        <h1 class="news-detail-title">{{ $news->judul }}</h1>

        <!-- Subjudul jika ada -->
        @if($news->subjudul)
            <p class="news-detail-subtitle">{{ $news->subjudul }}</p>
        @endif

        <!-- 3. Info Penulis & Tanggal Publikasi -->
        <div class="news-meta-bar">
            <div class="d-flex align-items-center">
                <i class="bi bi-person-circle mr-2 text-primary font-weight-bold" style="font-size: 1.1rem;"></i>
                <span>Ditulis oleh <strong>{{ $news->admin?->name ?? 'Admin Lapaktifikasi' }}</strong></span>
            </div>
            <div class="d-flex align-items-center">
                <i class="bi bi-calendar3 mr-2 text-muted"></i>
                <span>{{ $news->published_at ? $news->published_at->translatedFormat('d F Y - H:i') . ' WIB' : 'Draft' }}</span>
            </div>
            <div class="d-flex align-items-center">
                <i class="bi bi-clock mr-2 text-muted"></i>
                <span>{{ $news->published_at ? $news->published_at->diffForHumans() : '-' }}</span>
            </div>
        </div>

        <!-- 4. Konten Lengkap -->
        {{--
            SECURITY REMINDER:
            Jika input konten dari admin menggunakan Rich Text Editor (HTML), konten ditampilkan menggunakan {!! !!}.
            Pastikan input telah melewati sanitasi (misal: HTMLPurifier / strip_tags pada tag berbahaya)
            sebelum disimpan ke database untuk mencegah serangan XSS.
            Di bawah ini diformat agar mendukung paragraf teks maupun elemen HTML yang valid.
        --}}
        <div class="news-content-body">
            @if(strip_tags($news->konten) === $news->konten)
                {!! nl2br(e($news->konten)) !!}
            @else
                {!! $news->clean_konten !!}
            @endif
        </div>
    </div>
</div>
@endsection
