@php
    $siteName = isset($websiteSettings) && $websiteSettings->site_name ? $websiteSettings->site_name : 'Lapaktifikasi';
    $siteDesc = isset($websiteSettings) && $websiteSettings->site_description ? $websiteSettings->site_description : 'Platform distribusi produk digital dan akun premium terpercaya di Indonesia. Cepat, aman, dan bergaransi resmi.';
    $contactPhone = isset($websiteSettings) && $websiteSettings->contact_phone ? $websiteSettings->contact_phone : '6287897600086';
    $phoneClean = preg_replace('/[^0-9]/', '', $contactPhone);
    $siteAddress = isset($websiteSettings) && $websiteSettings->address ? $websiteSettings->address : 'Jl. Golf RT06/08, Ciriung, Kec. Cibinong, Kab. Bogor, Jawa Barat 16918';

    $mapEmbedSrc = (isset($websiteSettings) && !empty($websiteSettings->maps_embed_url))
        ? $websiteSettings->maps_embed_url
        : 'https://maps.google.com/maps?q=' . urlencode($siteAddress) . '&t=&z=15&ie=UTF8&iwloc=&output=embed';
    $mapsDirectUrl = 'https://maps.google.com/?q=' . urlencode($siteAddress);
@endphp

<!-- FOOTER -->
<footer class="footer">
    <div class="footer-inner">
        <div class="footer-top-row">
            <div class="footer-col footer-brand">
                <a href="{{ url('/') }}" class="nav-logo">
                    @if(isset($websiteSettings) && $websiteSettings->logo_path)
                        <img src="{{ asset($websiteSettings->logo_path) }}" alt="{{ $siteName }}"
                            width="42" height="42" loading="lazy" decoding="async"
                            style="max-height: 42px; margin-right: 8px; width: auto; object-fit: contain;">
                    @else
                        <div class="nav-logo-icon">L</div>
                        <span class="nav-logo-text">{{ $siteName }}</span>
                    @endif
                </a>
                <p>{{ $siteDesc }}</p>
                <div class="footer-socials">
                    <a href="https://instagram.com/nusagarudastudio" target="_blank" rel="noopener noreferrer" class="social-btn" aria-label="Instagram"><i class="bi bi-instagram"></i></a>
                    <a href="https://wa.me/{{ $phoneClean }}" target="_blank" rel="noopener noreferrer" class="social-btn" aria-label="WhatsApp"><i class="bi bi-whatsapp"></i></a>
                    <a href="https://t.me/nusagarudastudio" target="_blank" rel="noopener noreferrer" class="social-btn" aria-label="Telegram"><i class="bi bi-telegram"></i></a>
                </div>
            </div>
            <div class="footer-col">
                <h5>Layanan</h5>
                <ul>
                    <li><a href="{{ route('premium.katalog') }}">Semua Katalog Produk</a></li>
                    <li><a href="{{ route('daftar_toko') }}">Daftar Toko / Jurusan</a></li>
                    <li><a href="{{ route('premium.katalog', ['kategori' => 'premium']) }}">Spotify &amp; Netflix Premium</a></li>
                    <li><a href="{{ route('premium.katalog', ['kategori' => 'digital']) }}">Source Code &amp; File Digital</a></li>
                </ul>
            </div>
            <div class="footer-col">
                <h5>Perusahaan</h5>
                <ul>
                    <li><a href="{{ url('/#visimisi') }}">Tentang {{ $siteName }}</a></li>
                    <li><a href="{{ url('/#visimisi') }}">Visi &amp; Misi</a></li>
                    <li><a href="{{ url('/#cara-kerja') }}">Cara Kerja</a></li>
                    <li><a href="{{ url('/#testimoni') }}">Testimoni</a></li>
                    <li><a href="{{ url('/#faq') }}">FAQ</a></li>
                </ul>
            </div>
            <div class="footer-col">
                <h5>Dukungan</h5>
                <ul>
                    <li><a href="{{ url('/#faq') }}">Pusat Bantuan</a></li>
                    <li><a href="{{ route('kebijakan.privasi') }}">Kebijakan Privasi</a></li>
                    <li><a href="{{ route('syarat.ketentuan') }}">Syarat &amp; Ketentuan</a></li>
                    <li><a href="{{ route('daftar.seller') }}">Daftar Jadi Seller</a></li>
                    <li><a href="{{ route('join.partner') }}">Join Partner</a></li>
                    <li><a href="https://wa.me/{{ $phoneClean }}?text=Halo%20Admin%20{{ urlencode($siteName) }},%20saya%20ingin%20bertanya%20mengenai%20layanan." target="_blank" rel="noopener noreferrer">Hubungi Kami</a></li>
                    @guest
                        <li><a href="{{ url('/pendaftaran') }}">Daftar Akun</a></li>
                    @endguest
                </ul>
            </div>
            <div class="footer-col footer-location-col">
                <h5>Lokasi Kami</h5>
                <div class="footer-location-content">
                    <p class="footer-address">
                        <i class="bi bi-geo-alt-fill"></i>
                        <span>{{ $siteAddress }}</span>
                    </p>
                    <div class="footer-map-frame">
                        <iframe
                            src="{{ $mapEmbedSrc }}"
                            width="100%"
                            height="110"
                            style="border:0;display:block;"
                            allowfullscreen=""
                            loading="lazy"
                            referrerpolicy="no-referrer-when-downgrade"
                            title="Peta Lokasi {{ $siteName }}">
                        </iframe>
                    </div>
                    <a href="{{ $mapsDirectUrl }}" target="_blank" class="footer-map-action" rel="noopener noreferrer">
                        <i class="bi bi-box-arrow-up-right"></i>
                        <span>Buka di Google Maps</span>
                    </a>
                </div>
            </div>
        </div>
        <div class="footer-bottom-row">
            <p>&copy; {{ date('Y') }} {{ $siteName }}. Hak Cipta Dilindungi Undang-Undang.</p>
          
        </div>
    </div>
</footer>

<!-- Back to top button -->
<button class="back-to-top" aria-label="Kembali ke atas">
    <i class="bi bi-chevron-up"></i>
</button>

<!-- PWA Install Prompt Component -->
@include('partials.pwa_install_prompt')
