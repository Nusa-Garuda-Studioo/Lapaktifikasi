<div class="w-full dash-navbar bg-white py-3 px-4 sm:px-7 border-b border-gray-100 shadow-sm">
    <div class="flex flex-row justify-between items-center gap-2 sm:gap-3">
        <!-- Hamburger Menu Toggle Button (< lg) -->
        <button type="button" id="topbar-hamburger-toggle" aria-label="Buka Menu Navigasi"
            class="lg:hidden flex items-center justify-center p-2 rounded-xl bg-gray-100 hover:bg-violet-50 text-indigo-950 hover:text-violet-700 transition-colors border border-gray-200 focus:outline-none flex-shrink-0">
            <i class="bi bi-list text-2xl leading-none"></i>
        </button>

        <!-- Search bar (Collapsible/Flexible on mobile) -->
        <div class="relative flex-1 max-w-[450px]">
            <form action="{{ route('premium.katalog') }}" method="GET">
                <div class="relative">
                    <input type="text" name="search" placeholder="Cari produk / layanan..."
                        class="dash-search-input w-full py-2 sm:py-2.5 rounded-full pl-4 sm:pl-5 pr-10 sm:pr-12 border border-gray-300 text-xs sm:text-sm focus:outline-none focus:border-violet-600 transition-all">
                    <button type="submit"
                        class="absolute top-1 sm:top-1.5 right-1 sm:right-1.5 z-10 flex flex-row items-center p-1.5 sm:p-2 bg-violet-700 rounded-full hover:bg-violet-800 transition-colors border-none text-white">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" class="sm:w-[16px] sm:h-[16px]"
                            xmlns="http://www.w3.org/2000/svg">
                            <path
                                d="M11.5 21C16.7467 21 21 16.7467 21 11.5C21 6.25329 16.7467 2 11.5 2C6.25329 2 2 6.25329 2 11.5C2 16.7467 6.25329 21 11.5 21Z"
                                stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            <path opacity="0.4" d="M22 22L20 20" stroke="#fff" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </button>
                </div>
            </form>
        </div>

        <!-- User profile area -->
        <div class="user flex flex-row items-center gap-x-2 sm:gap-x-4 flex-shrink-0">
            @auth
                <div class="dropdown">
                    <button class="flex flex-row items-center gap-x-2.5 focus:outline-none bg-transparent border-none p-0 cursor-pointer" type="button" id="userProfileDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <div class="flex flex-col text-right hidden md:flex">
                            <h3 class="text-indigo-950 font-semibold text-xs sm:text-sm mb-0 truncate max-w-[150px]">
                                {{ Auth::user()->name ?: Auth::user()->email }}
                            </h3>
                            <p class="text-gray-400 text-[11px] mb-0">
                                @if(Auth::user()->role_id == \App\Enums\Role::ADMIN->value)
                                    Administrator
                                @elseif(Auth::user()->role_id == \App\Enums\Role::SELLER->value)
                                    Seller Store
                                @else
                                    Customer
                                @endif
                            </p>
                        </div>
                        <img src="{{ session('profile_picture') ? asset('assets/img/avatar/' . session('profile_picture')) : 'https://images.unsplash.com/photo-1616325629936-99a9013c29c6?q=80&w=3387&auto=format&fit=crop' }}"
                            alt="Avatar" class="h-9 w-9 sm:h-11 sm:w-11 rounded-full object-cover border-2 border-white shadow-sm flex-shrink-0">
                    </button>
                    <div class="dropdown-menu dropdown-menu-right rounded-2xl shadow-xl border-0 mt-2 p-2" aria-labelledby="userProfileDropdown">
                        <div class="px-3 py-2 border-b border-gray-100 md:hidden">
                            <p class="text-xs font-bold text-indigo-950 mb-0 truncate">{{ Auth::user()->name ?: Auth::user()->email }}</p>
                            <p class="text-[10px] text-gray-400 mb-0">
                                @if(Auth::user()->role_id == \App\Enums\Role::ADMIN->value) Administrator @elseif(Auth::user()->role_id == \App\Enums\Role::SELLER->value) Seller @else Customer @endif
                            </p>
                        </div>
                        @if(Auth::user()->role_id == \App\Enums\Role::SELLER->value)
                            <a class="dropdown-item rounded-xl text-xs sm:text-sm font-medium py-2 px-3 flex items-center gap-x-2" href="{{ url('/seller/profil') }}">
                                <i class="bi bi-shop text-gray-500"></i> Profil Toko
                            </a>
                        @elseif(Auth::user()->role_id == \App\Enums\Role::CUSTOMER->value)
                            <a class="dropdown-item rounded-xl text-xs sm:text-sm font-medium py-2 px-3 flex items-center gap-x-2" href="{{ url('/profile_customer/' . Auth::id()) }}">
                                <i class="bi bi-person text-gray-500"></i> Profil Saya
                            </a>
                        @elseif(Auth::user()->role_id == \App\Enums\Role::ADMIN->value)
                            <a class="dropdown-item rounded-xl text-xs sm:text-sm font-medium py-2 px-3 flex items-center gap-x-2" href="{{ route('admin.setting_website') }}">
                                <i class="bi bi-gear text-gray-500"></i> Pengaturan Website
                            </a>
                        @endif
                        <a class="dropdown-item rounded-xl text-xs sm:text-sm font-medium py-2 px-3 flex items-center gap-x-2" href="{{ url('ganti_password') }}">
                            <i class="bi bi-key text-gray-500"></i> Ganti Password
                        </a>
                        <div class="dropdown-divider my-1"></div>
                        <a class="dropdown-item rounded-xl text-xs sm:text-sm font-medium py-2 px-3 text-red-600 flex items-center gap-x-2" href="#" data-toggle="modal" data-target="#exampleModal">
                            <i class="bi bi-box-arrow-right text-red-600"></i> Logout
                        </a>
                    </div>
                </div>
            @else
                <a href="{{ url('login') }}" class="px-4 py-1.5 sm:px-5 sm:py-2 bg-violet-700 hover:bg-violet-800 text-white font-semibold text-xs sm:text-sm rounded-full transition-colors">Login</a>
            @endauth
        </div>
    </div>
</div>

@push('scripts')
<script>
    // Vanilla JS handler for Mobile Drawer Navigation (F2.1)
    (function() {
        function setupMobileDrawer() {
            var toggleBtns = document.querySelectorAll('#topbar-hamburger-toggle, #mobile-menu-toggle');
            var overlay = document.getElementById('mobile-drawer-overlay');
            var drawer = document.getElementById('mobile-drawer');
            var closeBtn = document.getElementById('mobile-drawer-close');

            if (!drawer || !overlay) return;

            function openDrawer(e) {
                if (e) e.preventDefault();
                overlay.classList.remove('hidden');
                requestAnimationFrame(function() {
                    overlay.classList.add('opacity-100');
                    overlay.classList.remove('opacity-0');
                    drawer.classList.remove('-translate-x-full');
                    document.body.classList.add('overflow-hidden');
                });
            }

            function closeDrawer(e) {
                if (e && e.preventDefault) e.preventDefault();
                drawer.classList.add('-translate-x-full');
                overlay.classList.add('opacity-0');
                overlay.classList.remove('opacity-100');
                setTimeout(function() {
                    overlay.classList.add('hidden');
                    document.body.classList.remove('overflow-hidden');
                }, 300);
            }

            toggleBtns.forEach(function(btn) {
                btn.addEventListener('click', openDrawer);
            });

            if (closeBtn) {
                closeBtn.addEventListener('click', closeDrawer);
            }

            if (overlay) {
                overlay.addEventListener('click', closeDrawer);
            }

            // Menutup drawer ketika link menu sidebar diklik
            var menuLinks = drawer.querySelectorAll('.sidebar-menu-item a');
            menuLinks.forEach(function(link) {
                link.addEventListener('click', function() {
                    closeDrawer();
                });
            });

            // Menutup drawer dengan tombol Escape (ESC)
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && !drawer.classList.contains('-translate-x-full')) {
                    closeDrawer();
                }
            });
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', setupMobileDrawer);
        } else {
            setupMobileDrawer();
        }
    })();
</script>
@endpush
