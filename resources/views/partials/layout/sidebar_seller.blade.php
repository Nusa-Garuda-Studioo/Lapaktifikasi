<div class="flex flex-col justify-between h-full">
    <div>
        <!-- Sidebar Brand Logo Header -->
        <div class="logo flex flex-row items-center justify-start gap-x-2 mb-8 px-2">
            <a href="{{ url('/seller/dashboard') }}" class="flex items-center gap-x-2">
                @if(isset($websiteSettings) && $websiteSettings->logo_path)
                    <img src="{{ asset($websiteSettings->logo_path) }}" alt="{{ $websiteSettings->site_name }}" class="h-10 max-w-[200px] w-auto object-contain">
                @else
                    <svg id="logo-85" width="36" height="36" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path class="ccustom" fill-rule="evenodd" clip-rule="evenodd"
                            d="M10 0C15.5228 0 20 4.47715 20 10V0H30C35.5228 0 40 4.47715 40 10C40 15.5228 35.5228 20 30 20C35.5228 20 40 24.4772 40 30C40 32.7423 38.8961 35.2268 37.1085 37.0334L37.0711 37.0711L37.0379 37.1041C35.2309 38.8943 32.7446 40 30 40C27.2741 40 24.8029 38.9093 22.999 37.1405C22.9756 37.1175 22.9522 37.0943 22.9289 37.0711C22.907 37.0492 22.8852 37.0272 22.8635 37.0051C21.0924 35.2009 20 32.728 20 30C20 35.5228 15.5228 40 10 40C4.47715 40 0 35.5228 0 30V20H10C4.47715 20 0 15.5228 0 10C0 4.47715 4.47715 0 10 0ZM18 10C18 14.4183 14.4183 18 10 18V2C14.4183 2 18 5.58172 18 10ZM38 30C38 25.5817 34.4183 22 30 22C25.5817 22 22 25.5817 22 30H38ZM2 22V30C2 34.4183 5.58172 38 10 38C14.4183 38 18 34.4183 18 30V22H2ZM22 18V2L30 2C34.4183 2 38 5.58172 38 10C38 14.4183 34.4183 18 30 18H22Z"
                            fill="#5417D7"></path>
                    </svg>
                    <h2 class="font-bold text-xl text-indigo-950 mb-0 truncate">
                        {{ isset($websiteSettings) ? $websiteSettings->site_name : 'Lapaktifikasi' }}
                    </h2>
                @endif
            </a>
        </div>

        <div class="flex flex-col gap-y-7">
            <!-- 1. GRUP UTAMA -->
            <div class="flex flex-col gap-y-3">
                <h6 class="sidebar-section-title">UTAMA</h6>
                <ul class="sidebar-menu-list">
                    <li class="sidebar-menu-item {{ Request::path() == 'seller/dashboard' ? 'active' : '' }}">
                        <a href="{{ url('/seller/dashboard') }}">
                            <i class="bi bi-speedometer2 text-lg"></i>
                            Dashboard Seller
                        </a>
                    </li>
                </ul>
            </div>

            <!-- 2. GRUP PRODUK & STOK -->
            <div class="flex flex-col gap-y-3">
                <h6 class="sidebar-section-title">PRODUK & STOK</h6>
                <ul class="sidebar-menu-list">
                    <li class="sidebar-menu-item @if(Request::path() == 'menu_produk' || Request::segment(1) == 'menu_produk') active @endif">
                        <a href="{{ url('/menu_produk') }}">
                            <i class="bi bi-bag-check text-lg"></i>
                            Menu Produk Premium
                        </a>
                    </li>
                    <li class="sidebar-menu-item @if(Request::segment(1) == 'premium' && Request::segment(2) == 'inventaris') active @endif">
                        <a href="{{ route('premium.inventaris.index') }}">
                            <i class="bi bi-box-seam text-lg"></i>
                            Inventaris Premium
                        </a>
                    </li>
                    <li class="sidebar-menu-item @if(Request::segment(2) == 'histori') active @endif">
                        <a href="{{ route('premium.histori.index') }}">
                            <i class="bi bi-journal-text text-lg"></i>
                            Histori Premium
                        </a>
                    </li>
                    <li class="sidebar-menu-item @if(Request::path() == 'menu_produk_digital' || Request::segment(1) == 'menu_produk_digital') active @endif">
                        <a href="{{ url('/menu_produk_digital') }}">
                            <i class="bi bi-file-earmark-code text-lg"></i>
                            Menu Produk Digital
                        </a>
                    </li>
                    <li class="sidebar-menu-item @if(Request::segment(1) == 'digital' && Request::segment(2) == 'inventaris') active @endif">
                        <a href="{{ route('digital.inventaris.index') }}">
                            <i class="bi bi-cloud-arrow-down text-lg"></i>
                            Inventaris Digital
                        </a>
                    </li>
                    <li class="sidebar-menu-item @if(Request::path() == 'produk_terjual') active @endif">
                        <a href="{{ url('produk_terjual') }}">
                            <i class="bi bi-cart-dash text-lg"></i>
                            Produk Terjual
                        </a>
                    </li>
                </ul>
            </div>

            <!-- 3. GRUP TOKO & PENGATURAN -->
            <div class="flex flex-col gap-y-3">
                <h6 class="sidebar-section-title">TOKO & PENGATURAN</h6>
                <ul class="sidebar-menu-list">
                    <li class="sidebar-menu-item @if(Request::path() == 'seller/profil') active @endif">
                        <a href="{{ url('seller/profil') }}">
                            <i class="bi bi-shop text-lg"></i>
                            Profil Toko
                        </a>
                    </li>
                    <li class="sidebar-menu-item">
                        <a href="{{ url('seller/profil#telegram') }}" class="flex items-center justify-between w-full">
                            <span class="flex items-center gap-x-2">
                                <i class="bi bi-telegram text-lg text-sky-500"></i>
                                Integrasi Telegram
                            </span>
                            @if(auth()->check() && auth()->user()->hasTelegram())
                                <span class="px-2 py-0.5 text-[10px] font-semibold bg-emerald-100 text-emerald-800 rounded-full">Aktif</span>
                            @elseif(auth()->check() && auth()->user()->telegram_blocked_at)
                                <span class="px-2 py-0.5 text-[10px] font-semibold bg-rose-100 text-rose-800 rounded-full">Diblokir</span>
                            @else
                                <span class="px-2 py-0.5 text-[10px] font-semibold bg-amber-100 text-amber-800 rounded-full">Belum</span>
                            @endif
                        </a>
                    </li>
                    <li class="sidebar-menu-item @if(Request::path() == 'seller/badges') active @endif">
                        <a href="{{ route('seller.badges') }}">
                            <i class="bi bi-patch-check text-lg"></i>
                            Badge Toko
                        </a>
                    </li>
                    <li class="sidebar-menu-item @if(Request::segment(2) == 'voucher') active @endif">
                        <a href="{{ route('seller.voucher.index') }}">
                            <i class="bi bi-ticket-detailed text-lg"></i>
                            Voucher Toko
                        </a>
                    </li>
                    <li class="sidebar-menu-item @if(Request::path() == 'ganti_password') active @endif">
                        <a href="{{ url('ganti_password') }}">
                            <i class="bi bi-key text-lg"></i>
                            Ganti Password
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>
