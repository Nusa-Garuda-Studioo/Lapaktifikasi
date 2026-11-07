@php
    $siteName = isset($websiteSettings) && $websiteSettings->site_name ? $websiteSettings->site_name : 'Lapaktifikasi';
    $isHome = request()->is('/');
@endphp

<!-- NAVBAR -->
<nav id="navbar">
    <a href="{{ url('/') }}" class="nav-logo">
        @if(isset($websiteSettings) && $websiteSettings->logo_path)
            <img src="{{ asset($websiteSettings->logo_path) }}" alt="{{ $siteName }}"
                width="42" height="42" loading="eager" decoding="async"
                style="max-height: 42px; margin-right: 8px; width: auto; object-fit: contain;">
        @else
            <div class="nav-logo-icon">L</div>
            <span class="nav-logo-text">{{ $siteName }}</span>
        @endif
    </a>

    <ul class="nav-links">
        @if(trim($__env->yieldContent('nav_links')))
            @yield('nav_links')
        @else
            <li><a href="{{ $isHome ? '#hero' : url('/') }}" class="{{ $isHome ? 'active' : '' }}">Beranda</a></li>
            <li><a href="{{ route('premium.katalog') }}" class="{{ request()->routeIs('premium.katalog*') ? 'active' : '' }}">Katalog</a></li>
            <li><a href="{{ $isHome ? '#fitur' : url('/#fitur') }}">Kelebihan</a></li>
            <li><a href="{{ $isHome ? '#faq' : url('/#faq') }}">FAQ</a></li>
            <li><a href="{{ route('daftar.seller') }}" class="{{ request()->routeIs('daftar.seller') ? 'active' : '' }}">Jadi Seller</a></li>
            <li><a href="{{ route('join.partner') }}" class="{{ request()->routeIs('join.partner') ? 'active' : '' }}">Join Partner</a></li>
            @auth
                @if(Auth::user()->role_id == 2)
                    <li><a href="{{ route('premium.riwayat') }}" class="{{ request()->routeIs('premium.riwayat') ? 'active' : '' }}">Riwayat</a></li>
                @endif
            @endauth
        @endif
    </ul>

    <div class="nav-actions">
        @auth
            @if(Auth::user()->role_id == 1)
                <a href="{{ url('/dashboard') }}" class="btn-nav-signup"><i class="bi bi-speedometer2"></i> Dashboard</a>
            @elseif(Auth::user()->role_id == 3)
                <a href="{{ url('/seller/dashboard') }}" class="btn-nav-signup"><i class="bi bi-shop"></i> Toko Saya</a>
            @else
                <a href="{{ route('premium.katalog') }}" class="btn-nav-signup"><i class="bi bi-cart3"></i> Belanja</a>
            @endif
        @else
            <a href="{{ url('/login') }}" class="btn-nav-login">Masuk</a>
            <a href="{{ url('/pendaftaran') }}" class="btn-nav-signup">Daftar Gratis</a>
        @endauth
    </div>

    <button class="nav-toggle" id="navToggle" onclick="toggleMobileMenu()" aria-label="Toggle Navigation">
        <i class="bi bi-list"></i>
    </button>
</nav>

<!-- MOBILE MENU DRAWER -->
<div class="mobile-menu-overlay" id="mobileMenuOverlay" onclick="closeMobileMenu()"></div>
<div class="mobile-menu" id="mobileMenu">
    <button class="mobile-menu-close" onclick="closeMobileMenu()" aria-label="Close Navigation">
        <i class="bi bi-x-lg"></i>
    </button>
    <ul class="mobile-nav-links">
        @if(trim($__env->yieldContent('mobile_nav_links')))
            @yield('mobile_nav_links')
        @else
            <li><a href="{{ $isHome ? '#hero' : url('/') }}" onclick="closeMobileMenu()">Beranda</a></li>
            <li><a href="{{ route('premium.katalog') }}" onclick="closeMobileMenu()">Katalog Produk</a></li>
            <li><a href="{{ $isHome ? '#fitur' : url('/#fitur') }}" onclick="closeMobileMenu()">Kelebihan</a></li>
            <li><a href="{{ $isHome ? '#faq' : url('/#faq') }}" onclick="closeMobileMenu()">FAQ</a></li>
            <li><a href="{{ route('daftar.seller') }}" onclick="closeMobileMenu()">Jadi Seller</a></li>
            <li><a href="{{ route('join.partner') }}" onclick="closeMobileMenu()">Join Partner</a></li>
            @if($isHome)
                <li><a href="#visimisi" onclick="closeMobileMenu()">Visi &amp; Misi</a></li>
            @endif
            @auth
                @if(Auth::user()->role_id == 2)
                    <li><a href="{{ route('premium.riwayat') }}" onclick="closeMobileMenu()">Riwayat</a></li>
                @else
                    <li><a href="{{ url('/dashboard') }}" onclick="closeMobileMenu()">Dashboard</a></li>
                @endif
            @endauth
        @endif
    </ul>
    <div class="mobile-nav-actions">
        @auth
            @if(Auth::user()->role_id == 1)
                <a href="{{ url('/dashboard') }}" class="btn-nav-signup" onclick="closeMobileMenu()"><i class="bi bi-speedometer2"></i> Dashboard</a>
            @elseif(Auth::user()->role_id == 3)
                <a href="{{ url('/seller/dashboard') }}" class="btn-nav-signup" onclick="closeMobileMenu()"><i class="bi bi-shop"></i> Toko Saya</a>
            @else
                <a href="{{ route('premium.katalog') }}" class="btn-nav-signup" onclick="closeMobileMenu()"><i class="bi bi-cart3"></i> Belanja</a>
            @endif
        @else
            <a href="{{ url('/login') }}" class="btn-nav-login" onclick="closeMobileMenu()">Masuk</a>
            <a href="{{ url('/pendaftaran') }}" class="btn-nav-signup" onclick="closeMobileMenu()">Daftar Gratis</a>
        @endauth
    </div>
</div>
