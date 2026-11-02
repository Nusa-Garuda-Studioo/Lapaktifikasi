@php
    $siteName = isset($websiteSettings) && $websiteSettings->site_name ? $websiteSettings->site_name : 'Lapaktifikasi';
    $siteDesc = isset($websiteSettings) && $websiteSettings->site_description ? $websiteSettings->site_description : 'Marketplace Produk Digital, Source Code & Akun Premium Terpercaya Bergaransi Resmi';

    $seoTitle = trim($__env->yieldContent('meta_title', $__env->yieldContent('title', '')));
    if (!empty($seoTitle)) {
        if (str_contains($seoTitle, $siteName)) {
            $fullTitle = $seoTitle;
        } else {
            $fullTitle = $seoTitle . ' | ' . $siteName;
        }
    } else {
        $fullTitle = $siteName . ' - ' . $siteDesc;
    }

    $metaDescription = trim($__env->yieldContent('meta_description', $siteDesc));
    // Limit description to ~160 chars for optimal Google snippet if not custom
    if (mb_strlen($metaDescription) > 165) {
        $metaDescription = mb_substr($metaDescription, 0, 160) . '...';
    }

    $metaKeywords = trim($__env->yieldContent('meta_keywords', 'lapaktifikasi, marketplace produk digital, beli akun premium, source code aplikasi, produk kreatif digital, akun premium murah, sistem otomatis, payment gateway'));
    $canonicalUrl = trim($__env->yieldContent('canonical_url', url()->current()));

    $defaultImage = isset($websiteSettings) && $websiteSettings->logo_path ? asset($websiteSettings->logo_path) : asset('assets/img/brand_ambassador.jpg');
    $ogImage = trim($__env->yieldContent('og_image', $defaultImage));
    $ogType = trim($__env->yieldContent('og_type', 'website'));
    $isPrivateArea = request()->is('dashboard*', 'admin*', 'seller*', 'profile_customer*', 'premium/riwayat*', 'premium/laporan*', 'premium/kredensial*', 'login', 'pendaftaran', 'lupa_password', 'reset_password', 'offline*', 'banned*');
    $defaultRobots = $isPrivateArea ? 'noindex, nofollow' : 'index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1';
    $robotsDirective = trim($__env->yieldContent('meta_robots', $defaultRobots));
@endphp

<!-- Basic Meta Tags -->
<title>{{ $fullTitle }}</title>
<meta name="title" content="{{ $fullTitle }}">
<meta name="description" content="{{ $metaDescription }}">
<meta name="keywords" content="{{ $metaKeywords }}">
<meta name="robots" content="{{ $robotsDirective }}">
<meta name="googlebot" content="{{ $robotsDirective }}">
<meta name="bingbot" content="{{ $robotsDirective }}">
<meta name="author" content="{{ $siteName }}">
<link rel="canonical" href="{{ $canonicalUrl }}">

<!-- PWA Manifest & App Config -->
@include('partials.pwa_head')

<!-- Open Graph / Facebook -->
<meta property="og:type" content="{{ $ogType }}">
<meta property="og:url" content="{{ $canonicalUrl }}">
<meta property="og:title" content="{{ $fullTitle }}">
<meta property="og:description" content="{{ $metaDescription }}">
<meta property="og:image" content="{{ $ogImage }}">
<meta property="og:site_name" content="{{ $siteName }}">
<meta property="og:locale" content="id_ID">
@yield('og_extra')

<!-- Twitter Cards -->
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:url" content="{{ $canonicalUrl }}">
<meta name="twitter:title" content="{{ $fullTitle }}">
<meta name="twitter:description" content="{{ $metaDescription }}">
<meta name="twitter:image" content="{{ $ogImage }}">

<!-- Search Engine Verification Tags (placeholder for admin) -->
<meta name="google-site-verification" content="google-site-verification-token">

<!-- Global Organization & WebSite JSON-LD Schema -->
<script type="application/ld+json">
{
  "{{ '@context' }}": "https://schema.org",
  "@graph": [
    {
      "@type": "Organization",
      "@id": "{{ url('/') }}#organization",
      "name": "{{ $siteName }}",
      "legalName": "Lapaktifikasi Indonesia",
      "url": "{{ url('/') }}",
      "logo": {
        "@type": "ImageObject",
        "@id": "{{ url('/') }}#logo",
        "url": "{{ isset($websiteSettings) && $websiteSettings->logo_path ? asset($websiteSettings->logo_path) : asset('assets/img/visi_kami.svg') }}",
        "caption": "{{ $siteName }}"
      },
      "image": "{{ $ogImage }}",
      "description": "{{ $siteDesc }}",
      "address": {
        "@type": "PostalAddress",
        "addressLocality": "Bogor",
        "addressRegion": "Jawa Barat",
        "addressCountry": "ID"
      },
      "contactPoint": {
        "@type": "ContactPoint",
        "telephone": "{{ isset($websiteSettings) && $websiteSettings->contact_phone ? $websiteSettings->contact_phone : '+6281234567890' }}",
        "contactType": "customer service",
        "email": "{{ isset($websiteSettings) && $websiteSettings->contact_email ? $websiteSettings->contact_email : 'support@lapaktifikasi.com' }}",
        "areaServed": "ID",
        "availableLanguage": ["Indonesian", "English"]
      },
      "knowsAbout": [
        "Marketplace Produk Digital",
        "Akun Premium Bergaransi",
        "Source Code Aplikasi & Web",
        "E-Book & Template Kreatif",
        "Sistem Pengiriman Otomatis Instan"
      ]
    },
    {
      "@type": "WebSite",
      "@id": "{{ url('/') }}#website",
      "url": "{{ url('/') }}",
      "name": "{{ $siteName }}",
      "description": "{{ $siteDesc }}",
      "publisher": {
        "@id": "{{ url('/') }}#organization"
      },
      "inLanguage": "id-ID",
      "potentialAction": {
        "@type": "SearchAction",
        "target": {
          "@type": "EntryPoint",
          "urlTemplate": "{{ url('/katalog') }}?search={search_term_string}"
        },
        "query-input": "required name=search_term_string"
      }
    }
  ]
}
</script>

<!-- Custom Stack for Page Specific JSON-LD (Product, FAQ, Breadcrumb, etc.) -->
@stack('schema')
