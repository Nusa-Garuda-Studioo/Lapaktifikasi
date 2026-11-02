<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- SEO & Metadata -->
    @include('partials.seo')

    <!-- Favicon -->
    <link rel="icon" type="image/png"
        href="{{ isset($websiteSettings) && $websiteSettings->favicon_path ? asset($websiteSettings->favicon_path) : asset('assets/img/favicon.png') }}">

    <!-- Google Fonts Preconnect & Stylesheet -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Space+Grotesk:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- Modular Landing Styles -->
    <link rel="stylesheet" href="{{ asset('css/landing/base.css') }}">
    <link rel="stylesheet" href="{{ asset('css/landing/navbar.css') }}">
    <link rel="stylesheet" href="{{ asset('css/landing/footer.css') }}">
    <link rel="stylesheet" href="{{ asset('css/landing/modal.css') }}">
    <link rel="stylesheet" href="{{ asset('css/landing/animations.css') }}">

    <!-- Page Specific Stylesheets -->
    @stack('styles')
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

    <!-- Landing Interactive JavaScript -->
    <script src="{{ asset('js/landing.js') }}"></script>

    <!-- Page Specific Scripts -->
    @stack('scripts')
</body>

</html>
