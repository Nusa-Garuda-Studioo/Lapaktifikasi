@extends('layout')

@php
    $minPriceFormatted = number_format($minPrice, 0, ',', '.');
    $storeName = $toko ? $toko->nama_toko : 'Lapaktifikasi';
    $productCleanDesc = Str::limit(strip_tags($produk->deskripsi ?? 'Beli ' . $produk->nama_produk . ' dengan harga terbaik, garansi resmi, dan pengiriman otomatis instan di Lapaktifikasi.'), 155);
    $productImage = $produk->gambar ? asset('assets/img/produk_premium/' . $produk->gambar) : (isset($websiteSettings) && $websiteSettings->logo_path ? asset($websiteSettings->logo_path) : asset('assets/img/brand_ambassador.jpg'));
    $productRating = $toko ? (float) $toko->rating_rata_rata : 5.0;
    $productReviewCount = $toko ? (int) $toko->jumlah_review : 0;
@endphp

@section('title', $produk->nama_produk . ' - ' . $storeName)
@section('meta_title', 'Jual ' . $produk->nama_produk . ' Murah & Bergaransi - ' . $storeName)
@section('meta_description', $productCleanDesc)
@section('meta_keywords', 'jual ' . strtolower($produk->nama_produk) . ', beli ' . strtolower($produk->nama_produk) . ', ' . strtolower($produk->nama_produk) . ' murah, akun premium, produk digital, software aplikasi, ' . strtolower($storeName))
@section('og_type', 'product')
@section('og_image', $productImage)

@section('og_extra')
<meta property="product:price:amount" content="{{ $minPrice }}">
<meta property="product:price:currency" content="IDR">
<meta property="product:availability" content="in stock">
<meta property="product:retailer_item_id" content="{{ $produk->id_produk }}">
@endsection

@push('schema')
<!-- Schema.org Product Structured Data (Google Rich Results / Shopee Standard) -->
<script type="application/ld+json">
{
  "{{ '@context' }}": "https://schema.org/",
  "@type": "Product",
  "name": {!! json_encode($produk->nama_produk) !!},
  "image": [
    {!! json_encode($productImage) !!}
  ],
  "description": {!! json_encode(Str::limit(strip_tags($produk->deskripsi ?? $produk->nama_produk), 350)) !!},
  "sku": "PROD-{{ $produk->id_produk }}",
  "mpn": "{{ $produk->id_produk }}",
  "brand": {
    "@type": "Brand",
    "name": {!! json_encode($storeName) !!}
  },
  @if($productReviewCount > 0)
  "aggregateRating": {
    "@type": "AggregateRating",
    "ratingValue": "{{ number_format($productRating, 1) }}",
    "reviewCount": "{{ $productReviewCount }}",
    "bestRating": "5",
    "worstRating": "1"
  },
  @endif
  "offers": {
    "@type": "Offer",
    "url": "{{ url()->current() }}",
    "priceCurrency": "IDR",
    "price": "{{ $minPrice }}",
    "priceValidUntil": "{{ now()->addYear()->format('Y-m-d') }}",
    "itemCondition": "https://schema.org/NewCondition",
    "availability": "https://schema.org/InStock",
    "seller": {
      "@type": "Organization",
      "name": {!! json_encode($storeName) !!}
    },
    "hasMerchantReturnPolicy": {
      "@type": "MerchantReturnPolicy",
      "applicableCountry": "ID",
      "returnPolicyCategory": "https://schema.org/MerchantReturnFiniteReturnWindow",
      "merchantReturnDays": 30,
      "returnMethod": "https://schema.org/ReturnOnline",
      "returnFees": "https://schema.org/FreeReturn"
    },
    "shippingDetails": {
      "@type": "OfferShippingDetails",
      "shippingRate": {
        "@type": "MonetaryAmount",
        "value": "0",
        "currency": "IDR"
      },
      "shippingDestination": {
        "@type": "DefinedRegion",
        "addressCountry": "ID"
      },
      "deliveryTime": {
        "@type": "ShippingDeliveryTime",
        "handlingTime": {
          "@type": "QuantitativeValue",
          "minValue": 0,
          "maxValue": 0,
          "unitCode": "DAY"
        },
        "transitTime": {
          "@type": "QuantitativeValue",
          "minValue": 0,
          "maxValue": 0,
          "unitCode": "DAY"
        }
      }
    }
  }
}
</script>

<!-- BreadcrumbList Schema for Google Navigation Trail -->
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
      "name": "Katalog Produk",
      "item": "{{ route('premium.katalog') }}"
    },
    {
      "@type": "ListItem",
      "position": 3,
      "name": {!! json_encode($produk->nama_produk) !!},
      "item": "{{ url()->current() }}"
    }
  ]
}
</script>
@endpush

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/pages/customer-detail.css') }}">
@endpush

@section('content')

@if($error = Session::get('error'))
<script>
    Swal.fire({ title: "Gagal", text: "{{ $error }}", icon: "error" });
</script>
@endif

<div class="shopee-detail-container">
    <!-- Breadcrumb Nav (Toko > Kategori > Nama Produk) -->
    <div class="shopee-breadcrumb">
        @if($toko)
            <a href="{{ route('premium.katalog', ['id_toko' => $toko->id_toko]) }}"><i class="bi bi-shop mr-1"></i> {{ $toko->nama_toko }}</a>
            <span class="mx-2">&gt;</span>
        @else
            <a href="{{ route('premium.katalog') }}"><i class="bi bi-shop mr-1"></i> Toko</a>
            <span class="mx-2">&gt;</span>
        @endif
        
        <a href="{{ route('premium.katalog', ['kategori' => $produk->kategori ?? $produk->tipe_produk]) }}">
            {{ $produk->kategori ?? ($produk->tipe_produk === 'digital' ? 'Produk Digital' : 'Akun Premium') }}
        </a>
        <span class="mx-2">&gt;</span>
        <span class="text-dark font-weight-bold">{{ $produk->nama_produk }}</span>
    </div>

    <!-- MAIN PRODUCT CARD (2 KOLOM DESKTOP, 1 KOLOM MOBILE) -->
    <div class="shopee-main-card">
        <div class="row">
            <!-- KOLOM KIRI: GALERI GAMBAR -->
            <div class="col-12 col-lg-5 mb-4 mb-lg-0">
                <div class="shopee-gallery-main">
                    @if(count($gallery) > 0)
                        <img id="mainProductImg" src="{{ $gallery[0] }}" alt="{{ $produk->nama_produk }}">
                    @elseif($produk->gambar)
                        <img id="mainProductImg" src="{{ asset('assets/img/produk_premium/' . $produk->gambar) }}" alt="{{ $produk->nama_produk }}">
                    @else
                        <div class="d-flex flex-column align-items-center justify-content-center text-muted" style="height:100%;">
                            <i class="bi bi-box-seam" style="font-size: 4rem;"></i>
                            <span class="mt-2" style="font-size: 0.85rem;">Gambar tidak tersedia</span>
                        </div>
                    @endif
                </div>

                @if(count($gallery) > 1)
                <!-- BARIS THUMBNAIL MULTI GAMBAR -->
                <div class="shopee-gallery-thumbs">
                    @foreach($gallery as $idx => $imgUrl)
                        <button type="button" class="shopee-thumb-btn {{ $idx === 0 ? 'shopee-thumb-active' : '' }}" onclick="changeMainImage('{{ $imgUrl }}', this)">
                            <img src="{{ $imgUrl }}" alt="Thumb {{ $idx + 1 }}">
                        </button>
                    @endforeach
                </div>
                @endif
            </div>

            <!-- KOLOM KANAN: INFORMASI PRODUK & VARIAN -->
            <div class="col-12 col-lg-7">
                <!-- 1. Nama Produk -->
                <h1 class="shopee-title">{{ $produk->nama_produk }}</h1>

                <!-- 2. Toko & Badge Reputasi -->
                <div class="shopee-store-row">
                    @if($produk->tipe_produk === 'digital')
                        <span class="badge badge-info p-2" style="font-size:0.75rem; font-weight:700;"><i class="bi bi-file-earmark-code"></i> Produk Digital</span>
                    @else
                        <span class="badge badge-dark p-2" style="font-size:0.75rem; font-weight:700;"><i class="bi bi-shield-check"></i> Akun Premium</span>
                    @endif

                    @if($toko)
                        <a href="{{ route('premium.katalog', ['id_toko' => $toko->id_toko]) }}" class="shopee-store-badge">
                            <i class="bi bi-shop"></i> {{ $toko->nama_toko }}
                        </a>

                        @if($toko->badges && $toko->badges->isNotEmpty())
                            @foreach($toko->badges as $b)
                            <span class="badge p-2" data-toggle="tooltip" title="{{ $b->nama_badge }}: {{ $b->deskripsi }}" style="background: #0f172a; border: 1px solid #8b5cf6; color: #a78bfa; font-size: 0.75rem; border-radius: 6px; cursor: pointer;">
                                <i class="bi bi-patch-check-fill text-warning mr-1"></i> {{ $b->nama_badge }}
                            </span>
                            @endforeach
                        @endif
                    @endif
                </div>

                <!-- 3. Rating & Review Summary -->
                <div class="shopee-rating-summary">
                    @php
                        $avgRating = $toko ? (float) $toko->rating_rata_rata : 5.0;
                        $countReviews = $toko ? (int) $toko->jumlah_review : 0;
                        $fullStars = floor($avgRating);
                        $halfStar = ($avgRating - $fullStars) >= 0.5 ? 1 : 0;
                        $emptyStars = 5 - $fullStars - $halfStar;
                    @endphp
                    <span class="shopee-rating-score">{{ number_format($avgRating, 1) }}</span>
                    <div class="text-warning" style="font-size: 1.05rem;">
                        {!! str_repeat('<i class="bi bi-star-fill"></i>', $fullStars) !!}
                        {!! $halfStar ? '<i class="bi bi-star-half"></i>' : '' !!}
                        {!! str_repeat('<i class="bi bi-star"></i>', $emptyStars) !!}
                    </div>
                    <span class="text-muted">|</span>
                    <span><strong class="text-dark">{{ $countReviews }}</strong> Ulasan Pembeli</span>
                </div>

                {{-- 
                    ================================================================
                    LIVEWIRE MIGRATION SECTION (ProductVariantSelector)
                    Untuk mengaktifkan Livewire Component dan membandingkan hasilnya,
                    cukup un-comment baris di bawah dan comment-out blok Vanilla JS (section 4 s/d 8).
                    ================================================================
                    <livewire:product-variant-selector :product="$produk" />
                --}}

                <!-- 4. Price Box (Range / Single Price) [LEGACY BLADE + VANILLA JS] -->
                <div class="shopee-price-box">
                    <span class="shopee-price-label">Harga:</span>
                    <span class="shopee-price-val" id="selected-price-display">
                        @if($minPrice == $maxPrice)
                            Rp {{ number_format($minPrice, 0, ',', '.') }}
                        @else
                            Rp {{ number_format($minPrice, 0, ',', '.') }} - Rp {{ number_format($maxPrice, 0, ',', '.') }}
                        @endif
                    </span>
                </div>

                <!-- FORM CHECKOUT FORM [LEGACY BLADE + VANILLA JS] -->
                <form action="{{ url('/proses_checkout_premium') }}" method="POST" id="checkout-form">
                    @csrf
                    <input type="hidden" name="id_varian" id="input_id_varian" value="">

                    <!-- 5. Pilihan Varian (Interactive Pill Buttons) -->
                    @forelse($produk->tipeLayanan as $tipeIndex => $tipe)
                    <div class="shopee-variant-group">
                        <span class="shopee-variant-label">
                            <i class="bi bi-layers-fill mr-1"></i> {{ $tipe->nama_tipe }}
                        </span>
                        <div class="shopee-pills-wrap">
                            @foreach($tipe->varianLayanan as $vIndex => $varian)
                            <button type="button" 
                                    class="shopee-pill-btn {{ $varian->stok_tersedia <= 0 ? 'shopee-pill-disabled' : '' }}" 
                                    data-id="{{ $varian->id_varian }}"
                                    data-harga="{{ $varian->harga }}"
                                    data-harga-formatted="Rp {{ number_format($varian->harga, 0, ',', '.') }}"
                                    data-stok="{{ $varian->stok_tersedia }}"
                                    data-nama="{{ $varian->nama_varian }}"
                                    onclick="selectVarian(this)"
                                    {{ $varian->stok_tersedia <= 0 ? 'disabled' : '' }}>
                                <i class="bi bi-check2-circle"></i>
                                {{ $varian->nama_varian }}
                                @if($varian->durasi_hari)
                                    <small class="opacity-75">({{ $varian->durasi_hari }} Hari)</small>
                                @endif
                            </button>
                            @endforeach
                        </div>
                    </div>
                    @empty
                    <div class="alert alert-secondary py-2 font-weight-bold mb-3" style="font-size:0.85rem;">
                        Belum ada varian layanan yang dikonfigurasi untuk produk ini.
                    </div>
                    @endforelse

                    @if($produk->tipe_produk === 'digital')
                    <!-- Informasi Tambahan Produk Digital -->
                    <div class="alert alert-info py-2 font-weight-bold mb-3" style="font-size:0.85rem; border-radius:8px;">
                        <i class="bi bi-info-circle-fill mr-1"></i> Produk ini dikirim dalam format akses digital langsung setelah pembayaran.
                    </div>
                    @endif

                    <!-- 6. Status Stok Real-time -->
                    <div class="d-flex align-items-center mb-4" style="gap:12px;">
                        <span class="shopee-variant-label mb-0">Status Stok:</span>
                        <span id="stok-status-badge" class="shopee-stock-badge bg-light text-muted border">
                            Silakan pilih varian layanan di atas
                        </span>
                    </div>

                    <!-- 7. Input Kuantitas Stepper -->
                    <div class="shopee-qty-wrap">
                        <span class="shopee-variant-label mb-0 mr-2">Jumlah:</span>
                        <button type="button" class="shopee-qty-btn" id="btn-qty-minus" onclick="updateQty(-1)">-</button>
                        <input type="number" id="qty-input" name="qty" class="shopee-qty-input" value="1" min="1" readonly>
                        <button type="button" class="shopee-qty-btn" id="btn-qty-plus" onclick="updateQty(1)">+</button>
                    </div>

                    <!-- 8. Tombol Aksi Utama (Desktop Layout) -->
                    <div class="shopee-action-row">
                        <button type="submit" class="shopee-btn-buy" id="btn-buy-desktop">
                            <i class="bi bi-bag-check-fill" style="font-size: 1.15rem;"></i> Beli Sekarang
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- SECTION BAWAH 1: DESKRIPSI LENGKAP PRODUK -->
    <div class="shopee-section-card">
        <h3 class="shopee-section-header">Deskripsi Lengkap Produk</h3>
        <div class="text-dark" style="line-height: 1.7; font-size: 0.95rem; white-space: pre-line;">
            {{ $produk->deskripsi ?? 'Tidak ada deskripsi tambahan untuk produk ini.' }}
        </div>
    </div>

    <!-- SECTION BAWAH 2: ULASAN PEMBELI -->
    @if($toko && $reviews)
    <div class="shopee-section-card">
        <h3 class="shopee-section-header">Penilaian & Ulasan Pembeli</h3>

        <!-- Rating Breakdown Summary -->
        <div class="shopee-rating-box">
            <div class="row align-items-center">
                <div class="col-lg-3 text-center mb-3 mb-lg-0 border-right">
                    <h1 class="font-weight-bold text-dark mb-0" style="font-size: 3rem;">{{ number_format($avgRating, 1) }}</h1>
                    <div class="text-warning my-1" style="font-size: 1.15rem;">
                        {!! str_repeat('<i class="bi bi-star-fill"></i>', $fullStars) !!}
                        {!! $halfStar ? '<i class="bi bi-star-half"></i>' : '' !!}
                        {!! str_repeat('<i class="bi bi-star"></i>', $emptyStars) !!}
                    </div>
                    <span class="text-muted font-weight-bold" style="font-size: 0.85rem;">{{ $countReviews }} total ulasan</span>
                </div>

                <div class="col-lg-9 pl-lg-4">
                    <div style="font-size: 0.82rem;">
                        @for($star = 5; $star >= 1; $star--)
                            @php
                                $count = $ratingDistribution[$star] ?? 0;
                                $percent = $countReviews > 0 ? ($count / $countReviews) * 100 : 0;
                            @endphp
                            <div class="d-flex align-items-center mb-1">
                                <span class="mr-2 font-weight-bold" style="width: 14px;">{{ $star }}</span>
                                <i class="bi bi-star-fill text-warning mr-2" style="font-size: 0.75rem;"></i>
                                <div class="progress flex-grow-1" style="height: 8px; border-radius: 4px; background-color: #e2e8f0;">
                                    <div class="progress-bar bg-dark" style="width: {{ $percent }}%; border-radius: 4px;"></div>
                                </div>
                                <span class="ml-3 text-muted font-weight-bold" style="width: 35px; text-align: right;">{{ $count }}</span>
                            </div>
                        @endfor
                    </div>
                </div>
            </div>
        </div>

        <!-- Review List -->
        <div class="shopee-reviews-list">
            @forelse($reviews as $rev)
                <div class="card border mb-3" style="border-radius: 12px; background: #ffffff; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.02);">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-start flex-wrap mb-2" style="gap: 8px;">
                            <div>
                                @php
                                    $name = $rev->customer->user->name ?? 'Pelanggan';
                                    $parts = explode(' ', trim($name));
                                    $maskedName = $parts[0];
                                    if (count($parts) > 1) {
                                        $maskedName .= ' ' . substr($parts[1], 0, 1) . '.';
                                    }
                                @endphp
                                <strong class="text-dark" style="font-size: 0.95rem;">{{ $maskedName }}</strong>
                                <div class="text-warning mt-1" style="font-size: 0.88rem;">
                                    {!! str_repeat('<i class="bi bi-star-fill"></i>', $rev->rating) !!}
                                    {!! str_repeat('<i class="bi bi-star"></i>', 5 - $rev->rating) !!}
                                </div>
                            </div>
                            <small class="text-muted">{{ $rev->created_at->format('d M Y H:i') }} WIB</small>
                        </div>
                        
                        @if($rev->komentar)
                            <p class="text-dark mb-0 mt-2" style="font-size: 0.92rem; line-height: 1.5;">
                                {!! nl2br(e($rev->komentar)) !!}
                            </p>
                        @else
                            <p class="text-muted mb-0 mt-2 font-italic" style="font-size: 0.88rem;">
                                Pembeli tidak menuliskan komentar ulasan.
                            </p>
                        @endif
                    </div>
                </div>
            @empty
                <div class="text-center py-4 border rounded bg-white" style="border-radius: 12px;">
                    <i class="bi bi-chat-left-dots text-muted" style="font-size: 3rem;"></i>
                    <h5 class="text-muted mt-3">Belum ada ulasan untuk toko ini.</h5>
                </div>
            @endforelse

            <div class="d-flex justify-content-center mt-4">
                {{ $reviews->links() }}
            </div>
        </div>
    </div>
    @endif
</div>

<!-- STICKY BOTTOM ACTION BAR UNTUK TABLET & MOBILE -->
<div class="shopee-mobile-sticky-bar">
    <button type="button" class="btn-mobile-buy" id="btn-buy-mobile" onclick="triggerMobileBuy()">
        <i class="bi bi-bag-check-fill"></i> Beli Sekarang
    </button>
</div>

<script>
    // 1. Pergantian Gambar Utama Galeri (dengan Transisi Fade Halus)
    function changeMainImage(src, element) {
        const mainImg = document.getElementById('mainProductImg');
        if (!mainImg) return;

        mainImg.style.opacity = '0';

        setTimeout(function() {
            mainImg.src = src;
            mainImg.style.opacity = '1';
        }, 150);

        document.querySelectorAll('.shopee-thumb-btn').forEach(btn => {
            btn.classList.remove('shopee-thumb-active');
        });
        element.classList.add('shopee-thumb-active');
    }

    // 2. Pemilihan Varian (Interactive Pill Buttons)
    let selectedVarianId = null;
    let selectedVarianStok = 0;

    function selectVarian(button) {
        document.querySelectorAll('.shopee-pill-btn').forEach(btn => {
            btn.classList.remove('shopee-pill-active');
        });

        button.classList.add('shopee-pill-active');

        selectedVarianId = button.getAttribute('data-id');
        selectedVarianStok = parseInt(button.getAttribute('data-stok')) || 0;
        const hargaFormatted = button.getAttribute('data-harga-formatted');

        // Update Hidden Input Form
        document.getElementById('input_id_varian').value = selectedVarianId;

        // Update Dynamic Price Display
        const priceDisplay = document.getElementById('selected-price-display');
        if (priceDisplay) {
            priceDisplay.style.opacity = '0';
            setTimeout(function() {
                priceDisplay.innerText = hargaFormatted;
                priceDisplay.style.opacity = '1';
            }, 100);
        }

        // Update Stock Badge & Button States
        const stokBadge = document.getElementById('stok-status-badge');
        const buyDesktop = document.getElementById('btn-buy-desktop');
        const buyMobile = document.getElementById('btn-buy-mobile');
        const cartDesktop = document.getElementById('btn-cart-desktop');
        const cartMobile = document.getElementById('btn-cart-mobile');

        if (selectedVarianStok > 0) {
            if (stokBadge) {
                stokBadge.className = 'shopee-stock-badge bg-success text-white';
                stokBadge.innerHTML = '<i class="bi bi-check-circle-fill"></i> Stok Tersedia (Stok: ' + selectedVarianStok + ')';
            }
            if (buyDesktop) buyDesktop.disabled = false;
            if (buyMobile) buyMobile.disabled = false;
            if (cartDesktop) cartDesktop.disabled = false;
            if (cartMobile) cartMobile.disabled = false;
        } else {
            if (stokBadge) {
                stokBadge.className = 'shopee-stock-badge bg-danger text-white';
                stokBadge.innerHTML = '<i class="bi bi-x-circle-fill"></i> Stok Habis';
            }
            if (buyDesktop) buyDesktop.disabled = true;
            if (buyMobile) buyMobile.disabled = true;
            if (cartDesktop) cartDesktop.disabled = true;
            if (cartMobile) cartMobile.disabled = true;
        }

        // Reset Qty input if larger than stock
        const qtyInput = document.getElementById('qty-input');
        if (qtyInput) {
            let currentQty = parseInt(qtyInput.value) || 1;
            if (currentQty > selectedVarianStok && selectedVarianStok > 0) {
                qtyInput.value = 1;
            }
        }
    }

    // Auto-select first available variant on load
    document.addEventListener('DOMContentLoaded', function() {
        const firstAvailablePill = document.querySelector('.shopee-pill-btn:not(.shopee-pill-disabled)');
        if (firstAvailablePill) {
            selectVarian(firstAvailablePill);
        }
    });

    // 3. Stepper Kuantitas (+/-)
    function updateQty(change) {
        const qtyInput = document.getElementById('qty-input');
        if (!qtyInput) return;

        let currentQty = parseInt(qtyInput.value) || 1;
        currentQty += change;

        if (currentQty < 1) {
            currentQty = 1;
        }

        if (selectedVarianStok > 0 && currentQty > selectedVarianStok) {
            currentQty = selectedVarianStok;
            Swal.fire({
                title: "Batas Stok",
                text: "Kuantitas tidak dapat melebihi stok yang tersedia (" + selectedVarianStok + ")",
                icon: "warning",
                timer: 2000,
                showConfirmButton: false
            });
        }

        qtyInput.value = currentQty;
    }

    // 4. Trigger Beli dari Sticky Bottom Bar di Mobile (Mendukung Legacy & Livewire)
    function triggerMobileBuy() {
        const livewireForm = document.getElementById('livewire-checkout-form');
        if (livewireForm) {
            const hiddenInput = document.getElementById('livewire_input_id_varian');
            if (!hiddenInput || !hiddenInput.value) {
                Swal.fire({
                    title: "Pilih Varian",
                    text: "Silakan pilih salah satu varian produk terlebih dahulu.",
                    icon: "warning"
                });
                return;
            }
            livewireForm.submit();
            return;
        }

        const isUserAuth = {{ auth()->check() ? 'true' : 'false' }};
        if (!isUserAuth) {
            Swal.fire({
                title: "Perlu Login",
                text: "Silakan masuk ke akun Anda terlebih dahulu untuk melanjutkan proses pembelian.",
                icon: "info",
                showCancelButton: true,
                confirmButtonText: "Login Sekarang",
                cancelButtonText: "Nanti Saja",
                confirmButtonColor: "#000000"
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = "{{ route('login') }}?redirect=" + encodeURIComponent(window.location.href);
                }
            });
            return;
        }

        const legacyForm = document.getElementById('checkout-form');
        if (legacyForm) {
            if (!selectedVarianId) {
                Swal.fire({
                    title: "Pilih Varian",
                    text: "Silakan pilih salah satu varian produk terlebih dahulu.",
                    icon: "warning"
                });
                return;
            }
            if (selectedVarianStok <= 0) {
                Swal.fire({
                    title: "Stok Habis",
                    text: "Maaf, stok untuk varian ini sedang habis.",
                    icon: "error"
                });
                return;
            }
            legacyForm.submit();
        }
    }

    // Listener Event Livewire Variant Changed (Sinkronisasi Mobile Bar & State JS)
    window.addEventListener('variant-changed', function(event) {
        const data = event.detail && event.detail[0] ? event.detail[0] : (event.detail || {});
        selectedVarianId = data.id_varian;
        selectedVarianStok = data.stok || 0;

        const buyMobile = document.getElementById('btn-buy-mobile');
        const cartMobile = document.getElementById('btn-cart-mobile');
        if (buyMobile) buyMobile.disabled = !data.is_available;
        if (cartMobile) cartMobile.disabled = !data.is_available;
    });

    // 5. Mock Add To Cart Toast
    function addToCartMock() {
        if (!selectedVarianId) {
            Swal.fire({
                title: "Pilih Varian",
                text: "Silakan pilih varian layanan terlebih dahulu.",
                icon: "info"
            });
            return;
        }
        if (selectedVarianStok <= 0) {
            Swal.fire({
                title: "Stok Habis",
                text: "Maaf, stok untuk varian ini sedang habis.",
                icon: "error"
            });
            return;
        }

        Swal.fire({
            title: "Berhasil!",
            text: "Produk telah ditambahkan ke keranjang Anda.",
            icon: "success",
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000
        });
    }

    // 6. Submit Form Validation
    document.getElementById('checkout-form').addEventListener('submit', function(e) {
        const isUserAuth = {{ auth()->check() ? 'true' : 'false' }};
        if (!isUserAuth) {
            e.preventDefault();
            Swal.fire({
                title: "Perlu Login",
                text: "Silakan masuk ke akun Anda terlebih dahulu untuk melanjutkan pembelian.",
                icon: "info",
                showCancelButton: true,
                confirmButtonText: "Login Sekarang",
                cancelButtonText: "Nanti Saja",
                confirmButtonColor: "#000000"
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = "{{ route('login') }}?redirect=" + encodeURIComponent(window.location.href);
                }
            });
            return;
        }

        if (!selectedVarianId) {
            e.preventDefault();
            Swal.fire({
                title: "Pilih Varian",
                text: "Silakan pilih salah satu varian produk sebelum melanjutkan pembelian.",
                icon: "warning"
            });
        } else if (selectedVarianStok <= 0) {
            e.preventDefault();
            Swal.fire({
                title: "Stok Habis",
                text: "Maaf, stok untuk varian ini sedang habis.",
                icon: "error"
            });
        }
    });
</script>

@endsection
