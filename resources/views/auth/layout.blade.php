<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta content="width=device-width, initial-scale=1, maximum-scale=1, shrink-to-fit=no" name="viewport">
    <title>@yield('title')</title>

    <link rel="icon" type="image/x-icon" href="{{ isset($websiteSettings) && $websiteSettings->favicon_path ? asset($websiteSettings->favicon_path) : asset('assets/img/favicon.png') }}">

    <!-- PWA Manifest & Meta -->
    @include('partials.pwa_head')

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Space+Grotesk:wght@600;700&display=swap" rel="stylesheet">

    <!-- General CSS Files -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css"
        integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.7.2/css/all.css"
        integrity="sha384-fnmOCqbTlWIlj8LyTjo7mOUStjsKC4pOpQbqyi7RrhN7udi9RwhKkMHpvLbHG9Sr" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- Sweetalert 2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Template CSS -->
    <link rel="stylesheet" href="{{asset('assets/css/style.css')}}">
    <link rel="stylesheet" href="{{asset('assets/css/components.css')}}">

    <style>
        :root {
            --auth-bg-color: #f1f5f9;
            --auth-card-bg: #ffffff;
            --auth-primary: #000000;
            --auth-text-main: #0f172a;
            --auth-text-muted: #64748b;
            --auth-border-color: #e2e8f0;
            --auth-radius: 24px;
            --auth-inner-radius: 20px;
        }

        body {
            background-color: var(--auth-bg-color) !important;
            background-image: 
                radial-gradient(at 40% 20%, hsla(220,100%,88%,0.3) 0px, transparent 50%),
                radial-gradient(at 80% 0%, hsla(280,100%,88%,0.25) 0px, transparent 50%),
                radial-gradient(at 0% 50%, hsla(180,100%,88%,0.25) 0px, transparent 50%),
                radial-gradient(at 80% 50%, hsla(320,100%,88%,0.2) 0px, transparent 50%),
                radial-gradient(at 0% 100%, hsla(40,100%,88%,0.25) 0px, transparent 50%),
                radial-gradient(at 80% 100%, hsla(120,100%,88%,0.25) 0px, transparent 50%) !important;
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif !important;
            color: var(--auth-text-main) !important;
            min-height: 100vh;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .auth-container {
            width: 100%;
            max-width: 1020px;
            margin: auto;
            padding: 24px 16px;
        }

        .auth-main-card {
            background: var(--auth-card-bg) !important;
            border: 1px solid var(--auth-border-color) !important;
            border-radius: var(--auth-radius) !important;
            box-shadow: 0 25px 50px -12px rgba(15, 23, 42, 0.12), 0 4px 12px rgba(0, 0, 0, 0.03) !important;
            overflow: hidden;
            position: relative;
        }

        /* Logo Brand */
        .auth-brand-logo {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            text-decoration: none !important;
            margin-bottom: 24px;
        }

        .auth-brand-img {
            max-height: 48px;
            width: auto;
            object-fit: contain;
        }

        .auth-brand-icon {
            width: 44px;
            height: 44px;
            background: #000000;
            color: #ffffff;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 1.35rem;
            font-family: 'Space Grotesk', sans-serif;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15);
        }

        .auth-brand-text {
            font-family: 'Space Grotesk', sans-serif;
            font-size: 1.35rem;
            font-weight: 800;
            color: #000000;
            letter-spacing: -0.5px;
            text-transform: uppercase;
        }

        .auth-brand-text span {
            color: #64748b;
            font-weight: 600;
        }

        /* Form elements */
        .auth-form-title {
            font-size: 1.65rem;
            font-weight: 800;
            color: #0f172a;
            margin-bottom: 6px;
            letter-spacing: -0.5px;
        }

        .auth-form-subtitle {
            font-size: 0.9rem;
            color: var(--auth-text-muted);
            margin-bottom: 24px;
        }

        .auth-label {
            font-weight: 700 !important;
            font-size: 0.78rem !important;
            letter-spacing: 0.5px !important;
            text-transform: uppercase !important;
            color: #334155 !important;
            margin-bottom: 6px !important;
            display: block;
        }

        .auth-input-group {
            position: relative;
            margin-bottom: 18px;
        }

        .auth-input-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 1.1rem;
            z-index: 5;
            pointer-events: none;
            transition: color 0.2s ease;
        }

        .auth-control {
            height: 48px !important;
            border: 1.5px solid #cbd5e1 !important;
            border-radius: 12px !important;
            padding-left: 44px !important;
            padding-right: 16px !important;
            font-size: 0.92rem !important;
            color: #0f172a !important;
            background: #ffffff !important;
            transition: all 0.2s ease !important;
        }

        .auth-control.has-toggle {
            padding-right: 44px !important;
        }

        .auth-control:focus {
            border-color: #000000 !important;
            box-shadow: 0 0 0 4px rgba(0, 0, 0, 0.08) !important;
        }

        .auth-control:focus + .auth-input-icon,
        .auth-input-group:focus-within .auth-input-icon {
            color: #000000;
        }

        .btn-toggle-password {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: transparent;
            border: none;
            color: #94a3b8;
            font-size: 1.1rem;
            cursor: pointer;
            z-index: 5;
            padding: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: color 0.2s ease;
        }

        .btn-toggle-password:hover {
            color: #000000;
        }

        /* Buttons */
        .btn-auth-primary {
            background: #000000 !important;
            border: 1.5px solid #000000 !important;
            color: #ffffff !important;
            font-weight: 700 !important;
            font-size: 0.95rem !important;
            border-radius: 12px !important;
            height: 48px !important;
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.2s ease !important;
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.15) !important;
            cursor: pointer;
        }

        .btn-auth-primary:hover {
            background: #1e293b !important;
            border-color: #1e293b !important;
            color: #ffffff !important;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.2) !important;
            transform: translateY(-1px);
        }

        .btn-auth-google {
            background: #ffffff !important;
            border: 1.5px solid #cbd5e1 !important;
            color: #1e293b !important;
            font-weight: 600 !important;
            font-size: 0.92rem !important;
            border-radius: 12px !important;
            height: 48px !important;
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: all 0.2s ease !important;
            text-decoration: none !important;
        }

        .btn-auth-google:hover {
            background: #f8fafc !important;
            border-color: #94a3b8 !important;
            color: #0f172a !important;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05) !important;
        }

        /* Divider */
        .auth-divider {
            display: flex;
            align-items: center;
            text-align: center;
            margin: 20px 0;
            color: #94a3b8;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .auth-divider::before,
        .auth-divider::after {
            content: '';
            flex: 1;
            border-bottom: 1px solid #e2e8f0;
        }

        .auth-divider span {
            padding: 0 12px;
        }

        /* Checkbox & Links */
        .custom-control-label {
            font-size: 0.88rem !important;
            font-weight: 500 !important;
            color: #475569 !important;
            cursor: pointer;
            padding-top: 2px;
        }

        .auth-link {
            color: #000000 !important;
            font-weight: 700 !important;
            text-decoration: none !important;
            transition: all 0.2s ease;
        }

        .auth-link:hover {
            text-decoration: underline !important;
            color: #334155 !important;
        }

        /* Right Panel Frame */
        .auth-visual-col {
            background: #f8fafc;
            border-left: 1px solid var(--auth-border-color);
        }

        .auth-visual-frame {
            border: 2px solid #000000 !important;
            border-radius: var(--auth-inner-radius) !important;
            background: #ffffff !important;
            padding: 32px 24px !important;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            align-items: center;
            text-align: center;
            height: 100%;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.04);
            position: relative;
        }

        .badge-dark-custom {
            background: #000000;
            color: #ffffff;
            font-weight: 700;
            font-size: 0.75rem;
            letter-spacing: 0.5px;
            padding: 8px 14px;
            border-radius: 50px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .auth-hero-img {
            max-height: 240px;
            width: auto;
            object-fit: contain;
            transition: transform 0.3s ease;
        }

        .auth-visual-frame:hover .auth-hero-img {
            transform: translateY(-4px) scale(1.02);
        }

        .auth-visual-title {
            font-weight: 800;
            color: #0f172a;
            font-size: 1.15rem;
            margin-top: 16px;
            margin-bottom: 6px;
        }

        .auth-visual-desc {
            font-size: 0.85rem;
            color: #64748b;
            line-height: 1.5;
            margin-bottom: 20px;
        }

        .auth-help-text {
            font-size: 0.83rem;
            color: #64748b;
            font-weight: 500;
        }

        .auth-help-text a {
            color: #dc2626;
            font-weight: 700;
            text-decoration: none;
        }

        .auth-help-text a:hover {
            text-decoration: underline;
        }
    </style>
</head>

<body>

    <div class="auth-container">
        <div class="auth-main-card">
            <div class="row no-gutters align-items-stretch">
                <!-- Left Side: Credentials Form -->
                <div class="col-12 col-lg-6 p-4 p-md-5 d-flex flex-column justify-content-center">
                    <div class="auth-brand">
                        <a href="{{ url('/') }}" class="auth-brand-logo">
                            @if(isset($websiteSettings) && $websiteSettings->logo_path)
                                <img src="{{ asset($websiteSettings->logo_path) }}" alt="{{ $websiteSettings->site_name ?? 'Logo' }}" class="auth-brand-img">
                            @elseif(isset($websiteSettings) && $websiteSettings->site_name)
                                <div class="auth-brand-icon">{{ strtoupper(substr($websiteSettings->site_name, 0, 1)) }}</div>
                                <div class="auth-brand-text">{{ $websiteSettings->site_name }}</div>
                            @else
                                <div class="auth-brand-icon">L</div>
                                <div class="auth-brand-text">LAPAK<span>TIFIKASI</span></div>
                            @endif
                        </a>
                    </div>

                    @yield('content')
                </div>

                <!-- Right Side: Visual Illustration Panel (Reference style) -->
                <div class="col-lg-6 d-none d-lg-block auth-visual-col p-4 p-xl-5 d-flex flex-column justify-content-center">
                    <div class="auth-visual-frame">
                        <div class="auth-visual-top w-100">
                            <span class="badge-dark-custom">
                                <!-- <i class="bi bi-shield-check text-warning"></i> Platform Marketplace & Digital -->
                            </span>
                        </div>

                        <div class="auth-visual-body my-auto py-3">
                            <img src="{{ isset($websiteSettings) && $websiteSettings->auth_hero_path ? asset($websiteSettings->auth_hero_path) : asset('assets/img/auth_hero.png') }}" alt="Auth Hero" class="img-fluid auth-hero-img">
                            <h5 class="auth-visual-title">Transaksi Cepat & Terpercaya</h5>
                            <p class="auth-visual-desc">{{ isset($websiteSettings) && $websiteSettings->site_description ? $websiteSettings->site_description : 'Beli dan jual produk digital, akun premium, serta voucher game secara instant 24/7 dengan sistem serba otomatis.' }}</p>
                        </div>

                        <div class="auth-visual-bottom w-100">
                            <div class="auth-help-text">
                                Butuh bantuan? <a href="{{ isset($websiteSettings) && $websiteSettings->contact_phone ? 'https://wa.me/' . preg_replace('/[^0-9]/', '', $websiteSettings->contact_phone) : 'https://wa.me/' }}" target="_blank">Hubungi Admin</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Password visibility toggle script -->
    <script>
        function togglePasswordVisibility(inputId, btnElement) {
            const input = document.getElementById(inputId);
            if (!input) return;
            const icon = btnElement.querySelector('i');
            if (input.type === 'password') {
                input.type = 'text';
                if (icon) {
                    icon.classList.remove('bi-eye');
                    icon.classList.add('bi-eye-slash');
                }
            } else {
                input.type = 'password';
                if (icon) {
                    icon.classList.remove('bi-eye-slash');
                    icon.classList.add('bi-eye');
                }
            }
        }
    </script>

    <!-- General JS Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js"
        integrity="sha384-UO2eT0CpHqdSJQ6hJty5KVphtPhzWj9WO1clHTMGa3JDZwrnQq4sF86dIHNDz0W1" crossorigin="anonymous">
    </script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js"
        integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM" crossorigin="anonymous">
    </script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.nicescroll/3.7.6/jquery.nicescroll.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.24.0/moment.min.js"></script>
    <script src="{{asset('assets/js/stisla.js')}}"></script>

    <!-- Template JS Files -->
    <script src="{{asset('assets/js/scripts.js')}}"></script>
    <script src="{{asset('assets/js/custom.js')}}"></script>

    <!-- PWA Service Worker (Silent Registration Only, No UI) -->
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function() {
                navigator.serviceWorker.register('/sw.js').catch(function(){});
            });
        }
    </script>
</body>

</html>