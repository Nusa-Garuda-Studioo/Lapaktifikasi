<!-- Scoped Vanilla CSS for PWA (Immune to Tailwind/Bootstrap differences) -->
<style>
    /* Default hidden states with !important to prevent any framework leaks */
    #lp-pwa-install-banner,
    #lp-pwa-notif-bar,
    #lp-pwa-ios-modal,
    #lp-pwa-welcome-modal,
    #lp-pwa-toast {
        display: none !important;
        box-sizing: border-box;
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
    }

    #lp-pwa-install-banner *,
    #lp-pwa-notif-bar *,
    #lp-pwa-ios-modal *,
    #lp-pwa-welcome-modal * {
        box-sizing: border-box;
    }

    /* Keyframes */
    @keyframes lpSlideUp {
        from { transform: translateY(100px); opacity: 0; }
        to { transform: translateY(0); opacity: 1; }
    }
    @keyframes lpPulseDot {
        0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.7); }
        70% { transform: scale(1); box-shadow: 0 0 0 7px rgba(239, 68, 68, 0); }
        100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); }
    }
    @keyframes lpBounce {
        0% { transform: translateY(0); }
        100% { transform: translateY(-6px); }
    }

    /* --- 1. Notification Permission Prompt Bar (Floating Bottom) --- */
    #lp-pwa-notif-bar.lp-show {
        display: flex !important;
        position: fixed;
        bottom: 20px;
        left: 16px;
        right: 16px;
        max-width: 480px;
        margin: 0 auto;
        background: #0f172a;
        color: #ffffff;
        border: 1px solid rgba(99, 102, 241, 0.4);
        border-radius: 16px;
        padding: 12px 14px;
        box-shadow: 0 20px 35px -10px rgba(0, 0, 0, 0.7), 0 0 20px rgba(79, 70, 229, 0.25);
        z-index: 99998;
        align-items: center;
        gap: 12px;
        animation: lpSlideUp 0.35s cubic-bezier(0.16, 1, 0.3, 1) forwards;
    }

    .lp-notif-pulse-wrap {
        position: relative;
        width: 40px;
        height: 40px;
        background: rgba(79, 70, 229, 0.18);
        border: 1px solid rgba(99, 102, 241, 0.3);
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .lp-notif-bell-icon {
        font-size: 20px;
    }

    .lp-pulse-dot {
        position: absolute;
        top: -3px;
        right: -3px;
        width: 10px;
        height: 10px;
        background: #ef4444;
        border-radius: 50%;
        animation: lpPulseDot 1.6s infinite;
    }

    .lp-notif-content {
        flex: 1;
        min-width: 0;
    }

    .lp-notif-title {
        font-size: 13px;
        font-weight: 700;
        color: #ffffff;
        margin: 0;
        line-height: 1.25;
    }

    .lp-notif-desc {
        font-size: 11px;
        color: #94a3b8;
        margin: 2px 0 0 0;
        line-height: 1.35;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .lp-notif-actions {
        display: flex;
        align-items: center;
        gap: 6px;
        flex-shrink: 0;
    }

    .lp-btn-notif-grant {
        background: #4f46e5;
        color: #ffffff !important;
        border: none;
        border-radius: 10px;
        padding: 7px 14px;
        font-size: 12px;
        font-weight: 600;
        cursor: pointer;
        transition: background 0.2s;
        white-space: nowrap;
    }
    .lp-btn-notif-grant:hover {
        background: #4338ca;
    }

    .lp-btn-close {
        background: transparent;
        color: #94a3b8;
        border: none;
        border-radius: 8px;
        width: 28px;
        height: 28px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 16px;
        cursor: pointer;
        transition: color 0.2s, background 0.2s;
    }
    .lp-btn-close:hover {
        color: #ffffff;
        background: rgba(255, 255, 255, 0.1);
    }

    /* --- 2. Install Banner Floating Bottom --- */
    #lp-pwa-install-banner.lp-show {
        display: flex !important;
        position: fixed;
        bottom: 20px;
        left: 16px;
        right: 16px;
        max-width: 440px;
        margin: 0 auto;
        background: #0f172a;
        color: #ffffff;
        border: 1px solid rgba(255, 255, 255, 0.15);
        border-radius: 16px;
        padding: 12px 14px;
        box-shadow: 0 20px 35px -10px rgba(0, 0, 0, 0.6), 0 0 15px rgba(79, 70, 229, 0.3);
        z-index: 99999;
        align-items: center;
        gap: 12px;
        animation: lpSlideUp 0.35s cubic-bezier(0.16, 1, 0.3, 1) forwards;
    }

    .lp-banner-icon {
        width: 42px;
        height: 42px;
        border-radius: 10px;
        flex-shrink: 0;
        object-fit: cover;
    }

    .lp-btn-install {
        background: #4f46e5;
        color: #ffffff !important;
        border: none;
        border-radius: 10px;
        padding: 7px 14px;
        font-size: 12px;
        font-weight: 600;
        cursor: pointer;
        transition: background 0.2s;
    }
    .lp-btn-install:hover {
        background: #4338ca;
    }

    /* --- 3. iOS Safari Instructions Modal --- */
    #lp-pwa-ios-modal.lp-show {
        display: flex !important;
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.7);
        backdrop-filter: blur(6px);
        -webkit-backdrop-filter: blur(6px);
        z-index: 100000;
        align-items: flex-end;
        justify-content: center;
        padding: 16px;
    }
    @media (min-width: 640px) {
        #lp-pwa-ios-modal.lp-show {
            align-items: center;
        }
    }

    .lp-ios-card {
        background: #0f172a;
        color: #ffffff;
        border: 1px solid rgba(255, 255, 255, 0.15);
        border-radius: 24px;
        width: 100%;
        max-width: 360px;
        padding: 24px 20px;
        text-align: center;
        position: relative;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.8);
    }
    .lp-ios-card h3 {
        font-size: 17px;
        font-weight: 700;
        margin: 12px 0 6px 0;
        color: #ffffff;
    }
    .lp-ios-card p {
        font-size: 12px;
        color: #94a3b8;
        line-height: 1.4;
        margin: 0 0 16px 0;
    }
    .lp-ios-steps {
        background: #1e293b;
        border: 1px solid #334155;
        border-radius: 16px;
        padding: 14px;
        text-align: left;
        margin-bottom: 16px;
    }
    .lp-ios-step {
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 12px;
        color: #cbd5e1;
        margin-bottom: 10px;
    }
    .lp-ios-step:last-child {
        margin-bottom: 0;
    }
    .lp-ios-badge {
        width: 20px;
        height: 20px;
        border-radius: 50%;
        background: rgba(79, 70, 229, 0.3);
        color: #818cf8;
        font-weight: 700;
        font-size: 11px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    .lp-ios-close-x {
        position: absolute;
        top: 14px;
        right: 14px;
        background: transparent;
        border: none;
        color: #94a3b8;
        font-size: 16px;
        cursor: pointer;
        padding: 4px;
    }
    .lp-ios-btn-done {
        width: 100%;
        background: #4f46e5;
        color: #ffffff;
        border: none;
        border-radius: 12px;
        padding: 10px;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
    }

    /* --- 4. Welcome Modal on First PWA Launch --- */
    #lp-pwa-welcome-modal.lp-show {
        display: flex !important;
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.75);
        backdrop-filter: blur(8px);
        -webkit-backdrop-filter: blur(8px);
        z-index: 100002;
        align-items: center;
        justify-content: center;
        padding: 16px;
    }
    .lp-welcome-card {
        background: #0f172a;
        color: #ffffff;
        border: 1px solid rgba(255, 255, 255, 0.2);
        border-radius: 24px;
        width: 100%;
        max-width: 370px;
        padding: 28px 22px;
        text-align: center;
        position: relative;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.9), 0 0 25px rgba(79, 70, 229, 0.3);
    }

    /* Minimal Toast */
    #lp-pwa-toast.lp-show {
        display: flex !important;
        position: fixed;
        top: 20px;
        left: 50%;
        transform: translateX(-50%);
        background: #0f172a;
        color: #ffffff;
        border: 1px solid #334155;
        border-radius: 9999px;
        padding: 10px 20px;
        font-size: 13px;
        font-weight: 600;
        box-shadow: 0 12px 30px rgba(0,0,0,0.6), 0 0 15px rgba(79, 70, 229, 0.4);
        z-index: 100003;
        align-items: center;
        gap: 8px;
    }
</style>

<!-- 1. Notification Permission Prompt Bar (Web & PWA Trigger) -->
<div id="lp-pwa-notif-bar">
    <div class="lp-notif-pulse-wrap">
        <div class="lp-notif-bell-icon">🔔</div>
        <span class="lp-pulse-dot"></span>
    </div>
    <div class="lp-notif-content">
        <div class="lp-notif-title">Aktifkan Notifikasi Promo & Pesanan?</div>
        <div class="lp-notif-desc">Dapatkan status transaksi & flash sale langsung di HP Anda.</div>
    </div>
    <div class="lp-notif-actions">
        <button id="lp-notif-grant-btn" class="lp-btn-notif-grant">Aktifkan</button>
        <button id="lp-notif-dismiss-btn" class="lp-btn-close" aria-label="Nanti">&times;</button>
    </div>
</div>

<!-- 2. Install Banner (Only pops up when installable and NOT dismissed) -->
<div id="lp-pwa-install-banner">
    <img src="{{ asset('assets/img/pwa/icon-96x96.png') }}" class="lp-banner-icon" alt="Lapaktifikasi">
    <div class="lp-banner-content">
        <div class="lp-banner-title">Pasang Lapaktifikasi</div>
        <div class="lp-banner-desc">Akses cepat & hemat kuota di beranda HP</div>
    </div>
    <div class="lp-banner-actions">
        <button id="lp-install-btn" class="lp-btn-install">Instal</button>
        <button id="lp-close-btn" class="lp-btn-close" aria-label="Tutup">&times;</button>
    </div>
</div>

<!-- 3. iOS Safari Manual Modal (Add to Home Screen Guide) -->
<div id="lp-pwa-ios-modal">
    <div class="lp-ios-card">
        <button id="lp-ios-close" class="lp-ios-close-x">&times;</button>
        <img src="{{ asset('assets/img/pwa/icon-96x96.png') }}" style="width: 56px; height: 56px; border-radius: 14px; margin: 0 auto;" alt="Logo">
        <h3>Pasang di iPhone / iPad</h3>
        <p id="lp-ios-modal-desc">Akses Lapaktifikasi langsung dari layar beranda Apple Anda dan aktifkan notifikasi.</p>
        <div class="lp-ios-steps">
            <div class="lp-ios-step">
                <span class="lp-ios-badge">1</span>
                <span>Ketuk tombol <strong>Bagikan (Share)</strong> <span style="font-size: 14px;">⎙</span> di bilah bawah Safari.</span>
            </div>
            <div class="lp-ios-step">
                <span class="lp-ios-badge">2</span>
                <span>Gulir dan pilih <strong>Tambahkan ke Layar Utama</strong> (Add to Home Screen).</span>
            </div>
            <div class="lp-ios-step">
                <span class="lp-ios-badge">3</span>
                <span>Ketuk <strong>Tambah</strong> di sudut kanan atas. Buka dari layar utama untuk aktifkan notifikasi.</span>
            </div>
        </div>
        <button id="lp-ios-done" class="lp-ios-btn-done">Mengerti</button>
    </div>
</div>

<!-- 4. Welcome Modal on First PWA Launch -->
<div id="lp-pwa-welcome-modal">
    <div class="lp-welcome-card">
        <button id="lp-welcome-close" class="lp-ios-close-x">&times;</button>
        <div style="font-size: 42px; margin-bottom: 6px; animation: lpBounce 1s infinite alternate;">🎉</div>
        <h3 style="font-size: 18px; font-weight: 700; color: #ffffff; margin: 0 0 8px 0;">Terima Kasih Telah Menginstall!</h3>
        <p style="font-size: 13px; color: #cbd5e1; line-height: 1.5; margin: 0 0 18px 0;">
            Aplikasi <strong>Lapaktifikasi</strong> siap digunakan langsung dari layar utama HP Anda. Aktifkan notifikasi sekarang agar langsung mendapatkan info transaksi & promo diskon!
        </p>
        <div style="display: flex; flex-direction: column; gap: 8px;">
            <button id="lp-welcome-notif-btn" class="lp-ios-btn-done" style="background: linear-gradient(135deg, #4f46e5, #7c3aed); padding: 12px; font-weight: 700; display: flex; align-items: center; justify-content: center; gap: 8px; box-shadow: 0 4px 14px rgba(79, 70, 229, 0.4);">
                <span>🔔</span> Aktifkan Notifikasi Sekarang
            </button>
            <button id="lp-welcome-btn" style="background: transparent; color: #94a3b8; border: none; padding: 8px; font-size: 12px; cursor: pointer;">
                Mulai Belanja &rarr;
            </button>
        </div>
    </div>
</div>

<!-- Minimal Toast Notification -->
<div id="lp-pwa-toast">
    <span id="lp-toast-msg"></span>
</div>

<script>
    (function () {
        // Helper defer run until browser is genuinely idle
        function runWhenIdle(callback) {
            if ('requestIdleCallback' in window) {
                window.requestIdleCallback(callback, { timeout: 3000 });
            } else {
                setTimeout(callback, 1500);
            }
        }

        // --- 1. Service Worker Silent Registration (Deferred till load + idle) ---
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function () {
                runWhenIdle(function () {
                    navigator.serviceWorker.register('/sw.js')
                        .then(function () {
                            // Auto-sync jika permission sudah granted sebelumnya
                            if ('Notification' in window && Notification.permission === 'granted') {
                                navigator.serviceWorker.ready.then(function (reg) {
                                    reg.pushManager.getSubscription().then(function (sub) {
                                        if (!sub && window.LapaktifikasiPWA) {
                                            window.LapaktifikasiPWA.requestPush(true);
                                        }
                                    });
                                });
                            }
                        })
                        .catch(function (err) {
                            console.warn('[PWA] SW register info:', err);
                        });
                });
            });
        }

        function getCsrfToken() {
            const meta = document.querySelector('meta[name="csrf-token"]');
            return meta ? meta.getAttribute('content') : '';
        }

        function urlBase64ToUint8Array(base64String) {
            const padding = '='.repeat((4 - (base64String.length % 4)) % 4);
            const base64 = (base64String + padding)
                .replace(/\-/g, '+')
                .replace(/_/g, '/');
            const rawData = window.atob(base64);
            const outputArray = new Uint8Array(rawData.length);
            for (let i = 0; i < rawData.length; ++i) {
                outputArray[i] = rawData.charCodeAt(i);
            }
            return outputArray;
        }

        function showLpToast(msg) {
            const toast = document.getElementById('lp-pwa-toast');
            const toastMsg = document.getElementById('lp-toast-msg');
            if (!toast || !toastMsg) return;
            toastMsg.textContent = msg;
            toast.classList.add('lp-show');
            setTimeout(function () {
                toast.classList.remove('lp-show');
            }, 3500);
        }

        const isIos = function () {
            const ua = window.navigator.userAgent.toLowerCase();
            return /iphone|ipad|ipod/.test(ua);
        };

        const isInStandalone = function () {
            return ('standalone' in window.navigator && window.navigator.standalone) ||
                   window.matchMedia('(display-mode: standalone)').matches ||
                   window.location.search.includes('source=pwa') ||
                   document.referrer.includes('android-app://');
        };

        // --- 2. Notification Trigger Bar Logic ---
        const notifBar = document.getElementById('lp-pwa-notif-bar');
        const notifGrantBtn = document.getElementById('lp-notif-grant-btn');
        const notifDismissBtn = document.getElementById('lp-notif-dismiss-btn');
        const NOTIF_DISMISS_KEY = 'lp_pwa_notif_dismissed_v1';

        function checkNotificationBar() {
            if (!('Notification' in window)) return;
            if (Notification.permission === 'granted') return; // Sudah aktif
            if (Notification.permission === 'denied') return; // User tolak, jangan ganggu

            const dismissedAt = localStorage.getItem(NOTIF_DISMISS_KEY);
            if (dismissedAt && (Date.now() - parseInt(dismissedAt, 10)) < (3 * 24 * 60 * 60 * 1000)) {
                return; // Jangan munculkan selama 3 hari jika ditutup
            }

            // Tampilkan bar secara elegan setelah 2.5 detik
            setTimeout(function () {
                if (notifBar) notifBar.classList.add('lp-show');
            }, 2500);
        }

        if (notifGrantBtn) {
            notifGrantBtn.addEventListener('click', async function () {
                if (notifBar) notifBar.classList.remove('lp-show');
                await window.LapaktifikasiPWA.requestPush(false);
            });
        }

        if (notifDismissBtn) {
            notifDismissBtn.addEventListener('click', function () {
                if (notifBar) notifBar.classList.remove('lp-show');
                localStorage.setItem(NOTIF_DISMISS_KEY, Date.now().toString());
            });
        }

        // --- 3. Install Banner (Android / Desktop Chrome) ---
        let deferredPrompt = null;
        const banner = document.getElementById('lp-pwa-install-banner');
        const installBtn = document.getElementById('lp-install-btn');
        const closeBtn = document.getElementById('lp-close-btn');
        const INSTALL_DISMISS_KEY = 'lp_pwa_install_dismissed_v2';

        window.addEventListener('beforeinstallprompt', function (e) {
            e.preventDefault();
            deferredPrompt = e;

            const t = localStorage.getItem(INSTALL_DISMISS_KEY);
            const isDismissed = t && ((Date.now() - parseInt(t, 10)) < (7 * 24 * 60 * 60 * 1000));
            if (banner && !isDismissed) {
                setTimeout(function () {
                    // Hanya tampilkan jika notif bar sedang tidak terbuka
                    if (!notifBar || !notifBar.classList.contains('lp-show')) {
                        banner.classList.add('lp-show');
                    }
                }, 4000);
            }
        });

        if (installBtn) {
            installBtn.addEventListener('click', async function () {
                if (banner) banner.classList.remove('lp-show');
                if (deferredPrompt) {
                    deferredPrompt.prompt();
                    await deferredPrompt.userChoice;
                    deferredPrompt = null;
                }
            });
        }

        if (closeBtn) {
            closeBtn.addEventListener('click', function () {
                if (banner) banner.classList.remove('lp-show');
                localStorage.setItem(INSTALL_DISMISS_KEY, Date.now().toString());
            });
        }

        window.addEventListener('appinstalled', function () {
            if (banner) banner.classList.remove('lp-show');
            deferredPrompt = null;
            showLpToast('Lapaktifikasi berhasil dipasang!');
        });

        // --- 4. iOS Safari Modal ---
        const iosModal = document.getElementById('lp-pwa-ios-modal');
        const iosClose = document.getElementById('lp-ios-close');
        const iosDone = document.getElementById('lp-ios-done');

        function openIosModal(customDesc) {
            if (customDesc) {
                const descEl = document.getElementById('lp-ios-modal-desc');
                if (descEl) descEl.textContent = customDesc;
            }
            if (iosModal) iosModal.classList.add('lp-show');
        }
        function closeIosModal() {
            if (iosModal) iosModal.classList.remove('lp-show');
        }
        if (iosClose) iosClose.addEventListener('click', closeIosModal);
        if (iosDone) iosDone.addEventListener('click', closeIosModal);

        // --- 5. First Time Opening PWA Welcome Greeting ---
        const welcomeModal = document.getElementById('lp-pwa-welcome-modal');
        const welcomeBtn = document.getElementById('lp-welcome-btn');
        const welcomeClose = document.getElementById('lp-welcome-close');
        const welcomeNotifBtn = document.getElementById('lp-welcome-notif-btn');

        function closeWelcomeModal() {
            if (welcomeModal) welcomeModal.classList.remove('lp-show');
        }
        if (welcomeBtn) welcomeBtn.addEventListener('click', closeWelcomeModal);
        if (welcomeClose) welcomeClose.addEventListener('click', closeWelcomeModal);

        if (welcomeNotifBtn) {
            welcomeNotifBtn.addEventListener('click', async function () {
                closeWelcomeModal();
                await window.LapaktifikasiPWA.requestPush(false);
            });
        }

        function checkFirstTimePwaLaunch() {
            const WELCOME_KEY = 'lp_pwa_first_install_greeted_v2';
            if (isInStandalone() && !localStorage.getItem(WELCOME_KEY)) {
                localStorage.setItem(WELCOME_KEY, Date.now().toString());
                setTimeout(function () {
                    if (welcomeModal) {
                        welcomeModal.classList.add('lp-show');
                    }
                }, 800);
            } else {
                // Jika bukan first time launch modal, cek trigger bar notifikasi biasa
                checkNotificationBar();
            }
        }

        // Defer UI prompt check until idle
        window.addEventListener('load', function () {
            runWhenIdle(function () {
                checkFirstTimePwaLaunch();
            });
        });

        // --- 6. Global LapaktifikasiPWA Object ---
        window.LapaktifikasiPWA = {
            install: function () {
                if (deferredPrompt) {
                    deferredPrompt.prompt();
                } else if (isIos() && !isInStandalone()) {
                    openIosModal();
                } else {
                    showLpToast('Aplikasi sudah terpasang di perangkat Anda.');
                }
            },

            requestPush: async function (silent = false) {
                // Validasi kapabilitas browser
                if (!('Notification' in window) || !('PushManager' in window) || !('serviceWorker' in navigator)) {
                    if (isIos()) {
                        if (!isInStandalone()) {
                            if (!silent) {
                                openIosModal('Pada iPhone/iPad (iOS), Apple mewajibkan memasang Lapaktifikasi ke Layar Utama (Add to Home Screen) terlebih dahulu agar notifikasi Web Push bisa aktif.');
                            }
                        } else {
                            if (!silent) alert('Fitur Push Notifikasi di iOS membutuhkan versi iOS 16.4+. Pastikan perangkat Apple Anda sudah diperbarui.');
                        }
                    } else {
                        if (!silent) alert('Browser Anda belum mendukung Web Push Notifications.');
                    }
                    return false;
                }

                let permission = Notification.permission;
                if (permission !== 'granted') {
                    permission = await Notification.requestPermission();
                }

                if (permission !== 'granted') {
                    if (!silent) {
                        alert('Izin notifikasi belum diizinkan. Silakan aktifkan melalui pengaturan browser Anda.');
                    }
                    return false;
                }

                try {
                    const reg = await navigator.serviceWorker.ready;
                    if (!reg || !reg.pushManager) {
                        throw new Error('Service Worker Push Manager tidak tersedia.');
                    }

                    const keyRes = await fetch('/webpush/key');
                    const keyData = await keyRes.json();
                    if (!keyData.publicKey) throw new Error('Kunci VAPID publik tidak ditemukan');

                    const appServerKey = urlBase64ToUint8Array(keyData.publicKey);

                    // Bersihkan subscription lama yang mungkin corrupt/kadaluarsa untuk mencegah 'push service error'
                    const existingSub = await reg.pushManager.getSubscription();
                    if (existingSub) {
                        try {
                            await existingSub.unsubscribe();
                        } catch (unsubErr) {
                            console.warn('[WebPush] Gagal unsubscribe sub lama:', unsubErr);
                        }
                    }

                    const subscription = await reg.pushManager.subscribe({
                        userVisibleOnly: true,
                        applicationServerKey: appServerKey
                    });

                    const subJson = subscription.toJSON();
                    const saveRes = await fetch('/webpush/subscribe', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': getCsrfToken(),
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            endpoint: subJson.endpoint,
                            keys: subJson.keys,
                            contentEncoding: 'aes128gcm'
                        })
                    });

                    const saveResult = await saveRes.json();
                    if (saveResult.status === 'success') {
                        if (notifBar) notifBar.classList.remove('lp-show');
                        showLpToast('🎉 Notifikasi Berhasil Diaktifkan!');

                        if (!silent) {
                            // Tembak uji notifikasi selamat datang
                            try {
                                await reg.showNotification('🎉 Notifikasi Lapaktifikasi Aktif!', {
                                    body: 'Perangkat ini siap menerima info update pesanan & promo flash sale.',
                                    icon: '/assets/img/pwa/icon-192x192.png',
                                    badge: '/assets/img/pwa/icon-96x96.png',
                                    data: { url: '/premium/katalog' }
                                });
                            } catch (e) {
                                // Fallback server push
                                fetch('/webpush/test', {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'X-CSRF-TOKEN': getCsrfToken(),
                                        'Accept': 'application/json'
                                    },
                                    body: JSON.stringify({
                                        endpoint: subJson.endpoint,
                                        keys: subJson.keys
                                    })
                                }).catch(function () {});
                            }
                        }
                        return true;
                    }
                    throw new Error(saveResult.message || 'Gagal menyimpan data perangkat');
                } catch (err) {
                    console.error('[WebPush Error]', err);
                    if (!silent) {
                        const errMsg = (err && err.message) ? err.message : String(err);
                        if (errMsg.includes('Registration failed - push service error') || errMsg.includes('push service error')) {
                            alert('Gagal mengaktifkan notifikasi: Layanan Push diblokir oleh browser atau jaringan.\n\nTips:\n1. Jika Anda memakai browser Brave: Buka brave://settings/privacy lalu AKTIFKAN "Use Google services for push messaging".\n2. Pastikan adblocker atau firewall tidak memblokir fcm.googleapis.com.');
                        } else {
                            alert('Gagal mengaktifkan notifikasi: ' + errMsg);
                        }
                    }
                    return false;
                }
            },

            testPush: async function () {
                if (!('serviceWorker' in navigator)) return;
                const reg = await navigator.serviceWorker.ready;

                if (!reg || !reg.pushManager) {
                    await this.requestPush();
                    return;
                }

                let sub = await reg.pushManager.getSubscription();
                if (!sub) {
                    const ok = await this.requestPush();
                    if (!ok) return;
                    sub = await reg.pushManager.getSubscription();
                }

                if (!sub) return;

                const subJson = sub.toJSON();
                try {
                    const res = await fetch('/webpush/test', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': getCsrfToken(),
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            endpoint: subJson.endpoint,
                            keys: subJson.keys
                        })
                    });

                    const data = await res.json();
                    if (data.status === 'success') {
                        showLpToast('⚡ Notifikasi percobaan berhasil dikirim!');
                    } else {
                        alert(data.message || 'Gagal mengirim push notifikasi');
                    }
                } catch (err) {
                    alert('Terjadi kesalahan: ' + err.message);
                }
            }
        };

        window.installLapaktifikasiPWA = window.LapaktifikasiPWA.install;
    })();
</script>
