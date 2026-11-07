<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- LCP Image Preload & High Priority Hints -->
    @stack('preload')

    <!-- SEO & Metadata -->
    @include('partials.seo')

    <!-- Favicon -->
    <link rel="icon" type="image/png"
        href="{{ isset($websiteSettings) && $websiteSettings->favicon_path ? asset($websiteSettings->favicon_path) : asset('assets/img/favicon.png') }}">

    <!-- Above-The-Fold Critical CSS (Inlined for instant FCP/LCP & 0 Render Blocking) -->
    @include('partials.landing.critical_css')

    <!-- Third-party Preconnect & DNS Prefetch -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="dns-prefetch" href="https://cdn.jsdelivr.net">
    <link rel="preload" as="style" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Space+Grotesk:wght@400;500;600;700;800&display=swap">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Space+Grotesk:wght@400;500;600;700;800&display=swap" media="print" onload="this.media='all'">
    <noscript>
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Space+Grotesk:wght@400;500;600;700;800&display=swap">
    </noscript>

    <!-- Bootstrap Icons (Non-blocking Async Loading) -->
    <link rel="preload" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    </noscript>

    <!-- Bundled Modular Styles (Non-blocking Async Loading) -->
    <link rel="preload" href="{{ asset('css/landing/landing-bundle.min.css') }}?v={{ file_exists(public_path('css/landing/landing-bundle.min.css')) ? filemtime(public_path('css/landing/landing-bundle.min.css')) : time() }}" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript>
        <link rel="stylesheet" href="{{ asset('css/landing/landing-bundle.min.css') }}">
    </noscript>

    <!-- Page Specific Stylesheets -->
    @stack('styles')

    @if(config('app.halloween_theme', false))
    <!-- HALLOWEEN:START -->
    <link rel="stylesheet" href="{{ asset('halloween/halloween.css') }}">
    <script src="{{ asset('halloween/halloween.js') }}" defer></script>
    <!-- HALLOWEEN:END -->
    @endif
</head>

<body>
    <!-- Ambient Background Blobs & Grid Pattern -->
    <div class="bg-blobs">
        <div class="blob blob-1"></div>
        <div class="blob blob-2"></div>
        <div class="blob blob-3"></div>
    </div>
    <div class="bg-grid"></div>

    <!-- Unified Navbar & Mobile Navigation Drawer -->
    @include('partials.landing.navbar')

    <!-- Main Page Content -->
    <main>
        @yield('content')
    </main>

    <!-- Unified Footer with Location & Socials -->
    @include('partials.landing.footer')

    <!-- Global Customer & Admin Login Modal -->
    @include('partials.landing.login_modal')

    <!-- Landing Interactive JavaScript (Deferred) -->
    <script src="{{ asset('js/landing.js') }}" defer></script>

    <!-- Page Specific Scripts -->
    @stack('scripts')
</body>

</html>
