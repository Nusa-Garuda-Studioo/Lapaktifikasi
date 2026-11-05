<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\TelegramController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PengaturanController;
use App\Http\Controllers\PembayaranController;
use App\Http\Controllers\PremiumAdminController;
use App\Http\Controllers\PremiumCustomerController;
use App\Http\Controllers\LaporanController;
use App\Http\Controllers\KelolaSellerController;
use App\Http\Controllers\SettingKomisiController;
use App\Http\Controllers\SaldoTokoController;
use App\Http\Controllers\SellerTokoController;
use App\Http\Controllers\AdminVoucherController;
use App\Http\Controllers\SellerVoucherController;
use App\Http\Controllers\ProductDigitalController;
use App\Http\Controllers\DigitalAdminController;
use App\Http\Controllers\SitemapController;
use App\Http\Middleware\OnlyCustomer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;


/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

use App\Models\MitraIndustri;
use App\Models\Testimoni;

Route::get('/', function () {
    try {
        $mitras = MitraIndustri::where('is_active', true)->orderBy('id', 'desc')->get();
    } catch (\Throwable $e) {
        $mitras = collect();
    }

    try {
        $testimonis = Testimoni::where('is_active', true)->orderBy('id', 'desc')->get();
    } catch (\Throwable $e) {
        $testimonis = collect();
    }

    return view('welcome', compact('mitras', 'testimonis'));
});

Route::get('/banned', function () {
    return view('auth.banned');
})->name('banned.page');

Route::get('/daftar-jadi-seller', function () {
    $mitras = MitraIndustri::where('is_active', true)->orderBy('id', 'desc')->get();
    $testimonis = Testimoni::where('is_active', true)->orderBy('id', 'desc')->get();
    return view('daftar_seller', compact('mitras', 'testimonis'));
})->name('daftar.seller');

Route::get('/join-partner', function () {
    $mitras = MitraIndustri::where('is_active', true)->orderBy('id', 'desc')->get();
    $testimonis = Testimoni::where('is_active', true)->orderBy('id', 'desc')->get();
    return view('join_partner', compact('mitras', 'testimonis'));
})->name('join.partner');

Route::get('/kebijakan-privasi', function () {
    return view('kebijakan_privasi');
})->name('kebijakan.privasi');

Route::get('/syarat-ketentuan', function () {
    return view('syarat_ketentuan');
})->name('syarat.ketentuan');

Route::get('/offline', function () {
    return view('offline');
})->name('offline');

// Dynamic XML Sitemap for SEO Crawlers
Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');

// Web Push Notification Endpoints
Route::prefix('webpush')->middleware('throttle:15,1')->group(function () {
    Route::get('/key', [\App\Http\Controllers\WebPushController::class, 'getPublicKey'])->name('webpush.key');
    Route::post('/subscribe', [\App\Http\Controllers\WebPushController::class, 'subscribe'])->name('webpush.subscribe');
    Route::post('/unsubscribe', [\App\Http\Controllers\WebPushController::class, 'unsubscribe'])->name('webpush.unsubscribe');
    Route::post('/test', [\App\Http\Controllers\WebPushController::class, 'sendTestNotification'])->name('webpush.test');
});

// Katalog & Produk Publik (SEO Friendly / Shopee Style)
Route::get('/katalog', [PremiumCustomerController::class, 'katalog'])->name('katalog');
Route::get('/premium/katalog', [PremiumCustomerController::class, 'katalog'])->name('premium.katalog');
Route::get('/produk/{product_slug}', [PremiumCustomerController::class, 'showWithoutStore'])->name('produk.show');
Route::get('/toko/{store_slug}/produk/{product_slug}', [PremiumCustomerController::class, 'show'])->name('toko.produk.detail');
Route::get('/premium/produk/{id}', [PremiumCustomerController::class, 'detail'])->name('premium.produk.detail');
Route::get('/daftar_toko', [ProductController::class, 'daftar_toko'])->name('daftar_toko');
Route::get('/toko/{store_slug}/produk', [ProductController::class, 'katalog_toko'])->name('toko.produk');
Route::get('/toko/{store_slug}', [ProductController::class, 'katalog_toko'])->name('toko.show');

// Berita / News Publik & Customer
Route::controller(\App\Http\Controllers\CustomerNewsController::class)->group(function () {
    Route::get('/news', 'index')->name('news.index');
    Route::get('/news/{slug}', 'show')->name('news.show');
});

Route::middleware('auth')->group(function () {

    Route::controller(DashboardController::class)->group(function () {
        Route::get('/dashboard', 'index')->name('dashboard')->middleware('prevent.customer');
    });

    Route::controller(CustomerController::class)->group(function () {
        Route::get('/profile_customer/{id}', 'index')->middleware('check.id.customer');
        Route::post('/update_profile', 'update_profile');
    });

    Route::controller(TelegramController::class)->prefix('telegram')->name('telegram.')->group(function () {
        Route::post('/link-token', 'createLinkToken')->name('link_token');
        Route::post('/unlink', 'unlink')->name('unlink');
        Route::post('/channel-preference', 'updateChannelPreference')->name('channel_preference');
    });

    Route::controller(PengaturanController::class)->group(function () {
        Route::get('/ganti_password', 'index');
        Route::post('/proses_ganti_password', 'proses_ganti_password');
    });

    Route::controller(ProductController::class)->group(function () {
        Route::get('/produk_terjual', 'produk_terjual')->middleware('prevent.customer');
        Route::post('/proses_checkout_premium', 'proses_checkout_premium')->name('proses_checkout_premium')->middleware('throttle:checkout_rate_limit');
        Route::get('/varian/{id_varian}/stok', 'get_stok_varian');
    });

    Route::resource('menu_produk', ProductController::class, ['only' => ['index']]);
    Route::resource('menu_produk_digital', ProductDigitalController::class, ['only' => ['index']]);

    Route::controller(PembayaranController::class)->group(function () {
        Route::middleware([OnlyCustomer::class])->group(function () {
            Route::get('/download_bukti_pembayaran/{order_id}', 'download_bukti_pembayaran')->name('download_bukti_pembayaran');
            Route::get('/bukti_pembayaran', 'index');
            Route::get('/metode_pembayaran/{order_id}', 'metode_pembayaran')->name('metode_pembayaran');
            Route::post('/metode_pembayaran/{order_id}/generate', 'generate_transaksi')->name('metode_pembayaran.generate');
            Route::get('/bukti_pembayaran/status/{order_id}', 'status')->name('bukti_pembayaran.status');
            Route::get('/api/status/{order_id}', 'statusApi')
                 ->name('bukti_pembayaran.status_api')
                 ->middleware('throttle:30,1'); // Max 30 requests per minute
            Route::get('/checkout/status/{invoice_number}', 'checkout_status')->name('checkout.status');
            Route::get('/api/checkout/{invoice_number}/status', 'checkoutStatusApi')
                 ->name('checkout.status_api')
                 ->middleware('throttle:60,1');
        });
    });

    // ──────────────────────────────────────────────────────────
    // Premium Layanan Routes  →  Admin & Seller (prevent.customer)
    // Seller melihat & mengelola data yang di-scope ke tokonya sendiri
    // via PremiumAdminController (scoping ada di dalam controller)
    // ──────────────────────────────────────────────────────────
    Route::middleware('prevent.customer')->group(function () {
        // Halaman Inventaris (Gabungan Tipe, Varian, Stok)
        Route::get('/premium/inventaris', [PremiumAdminController::class, 'inventaris_index'])->name('premium.inventaris.index');

        // CRUD Tipe
        Route::post('/premium/tipe', [PremiumAdminController::class, 'tipe_store'])->name('premium.tipe.store');
        Route::put('/premium/tipe/{id}', [PremiumAdminController::class, 'tipe_update'])->name('premium.tipe.update');
        Route::delete('/premium/tipe/{id}', [PremiumAdminController::class, 'tipe_destroy'])->name('premium.tipe.destroy');

        // CRUD Varian
        Route::post('/premium/varian', [PremiumAdminController::class, 'varian_store'])->name('premium.varian.store');
        Route::put('/premium/varian/{id}', [PremiumAdminController::class, 'varian_update'])->name('premium.varian.update');
        Route::delete('/premium/varian/{id}', [PremiumAdminController::class, 'varian_destroy'])->name('premium.varian.destroy');

        // CRUD Stok
        Route::post('/premium/stok', [PremiumAdminController::class, 'stok_store'])->name('premium.stok.store');
        Route::post('/premium/stok/bulk', [PremiumAdminController::class, 'stok_bulk_store'])->name('premium.stok.bulk_store');
        Route::get('/premium/stok/detail/{id}', [PremiumAdminController::class, 'stok_decrypt'])->name('premium.stok.decrypt');
        Route::delete('/premium/stok/{id}', [PremiumAdminController::class, 'stok_destroy'])->name('premium.stok.destroy');

        // Histori Penjualan
        Route::get('/premium/histori', [PremiumAdminController::class, 'histori_index'])->name('premium.histori.index');

        // ──────────────────────────────────────────────────────────
        // Digital Produk Routes
        // ──────────────────────────────────────────────────────────
        Route::get('/digital/inventaris', [DigitalAdminController::class, 'inventaris_index'])->name('digital.inventaris.index');
        Route::post('/digital/tipe', [DigitalAdminController::class, 'tipe_store'])->name('digital.tipe.store');
        Route::put('/digital/tipe/{id}', [DigitalAdminController::class, 'tipe_update'])->name('digital.tipe.update');
        Route::delete('/digital/tipe/{id}', [DigitalAdminController::class, 'tipe_destroy'])->name('digital.tipe.destroy');
        Route::post('/digital/varian', [DigitalAdminController::class, 'varian_store'])->name('digital.varian.store');
        Route::put('/digital/varian/{id}', [DigitalAdminController::class, 'varian_update'])->name('digital.varian.update');
        Route::delete('/digital/varian/{id}', [DigitalAdminController::class, 'varian_destroy'])->name('digital.varian.destroy');
    });

    // ──────────────────────────────────────────────────────────
    // Admin-Exclusive Routes  →  Admin ONLY (admin.only)
    // Seller TIDAK bisa akses halaman ini
    // ──────────────────────────────────────────────────────────
    Route::middleware('admin.only')->group(function () {
        Route::get('/premium/laporan-admin', [LaporanController::class, 'admin_index'])->name('admin.laporan');
        Route::post('/premium/laporan-admin/{id}/status', [LaporanController::class, 'update_status'])->name('admin.laporan.status');

        // Kelola Seller & Badges
        Route::get('/kelola_seller', [KelolaSellerController::class, 'index'])->name('admin.kelola_seller');
        Route::post('/kelola_seller/store', [KelolaSellerController::class, 'store'])->name('admin.kelola_seller.store');
        Route::post('/kelola_seller/update/{id_toko}', [KelolaSellerController::class, 'update'])->name('admin.kelola_seller.update');
        Route::post('/kelola_seller/toggle_status/{id_toko}', [KelolaSellerController::class, 'toggleStatus'])->name('admin.kelola_seller.toggle_status');
        Route::post('/kelola_seller/ban/{id_toko}', [KelolaSellerController::class, 'banSeller'])->name('admin.kelola_seller.ban');
        Route::post('/kelola_seller/unban/{id_toko}', [KelolaSellerController::class, 'unbanSeller'])->name('admin.kelola_seller.unban');
        Route::post('/kelola_seller/badge/attach/{id_toko}', [KelolaSellerController::class, 'attachBadge'])->name('admin.kelola_seller.badge.attach');
        Route::post('/kelola_seller/badge/detach/{id_toko}/{id_badge}', [KelolaSellerController::class, 'detachBadge'])->name('admin.kelola_seller.badge.detach');
        Route::post('/kelola_seller/badge/custom/{id_toko}', [KelolaSellerController::class, 'createCustomBadge'])->name('admin.kelola_seller.badge.custom');

        // Kelola Customer
        Route::get('/kelola_customer', [App\Http\Controllers\KelolaCustomerController::class, 'index'])->name('admin.kelola_customer');
        Route::post('/kelola_customer/ban/{id}', [App\Http\Controllers\KelolaCustomerController::class, 'banCustomer'])->name('admin.kelola_customer.ban');
        Route::post('/kelola_customer/unban/{id}', [App\Http\Controllers\KelolaCustomerController::class, 'unbanCustomer'])->name('admin.kelola_customer.unban');

        // Setting Komisi & Maintenance
        Route::get('/setting_komisi', [SettingKomisiController::class, 'index'])->name('admin.setting_komisi');
        Route::post('/setting_komisi/update', [SettingKomisiController::class, 'update'])->name('admin.setting_komisi.update');
        Route::post('/setting_komisi/toggle_maintenance', [SettingKomisiController::class, 'toggleMaintenance'])->name('admin.setting_komisi.toggle_maintenance');

        // Kelola Saldo Toko
        Route::get('/saldo_toko', [SaldoTokoController::class, 'index'])->name('admin.saldo_toko');
        Route::get('/saldo_toko/detail/{id_toko}', [SaldoTokoController::class, 'detail'])->name('admin.saldo_toko.detail');
        Route::post('/saldo_toko/withdraw/{id_toko}', [SaldoTokoController::class, 'withdraw'])->name('admin.saldo_toko.withdraw');
        Route::post('/premium/pembayaran/{id_pembayaran}/retry-wa', [PremiumAdminController::class, 'retryWa'])->name('admin.pembayaran.retry_wa');
        Route::post('/premium/order/{order_id}/check-status', [PremiumAdminController::class, 'checkPaymentStatus'])->name('admin.order.check_status');

        // Kelola Voucher Admin
        Route::get('/admin/voucher', [AdminVoucherController::class, 'index'])->name('admin.voucher.index');
        Route::get('/admin/voucher/create', [AdminVoucherController::class, 'create'])->name('admin.voucher.create');
        Route::post('/admin/voucher', [AdminVoucherController::class, 'store'])->name('admin.voucher.store');
        Route::get('/admin/voucher/{id}/edit', [AdminVoucherController::class, 'edit'])->name('admin.voucher.edit');
        Route::put('/admin/voucher/{id}', [AdminVoucherController::class, 'update'])->name('admin.voucher.update');
        Route::post('/admin/voucher/{id}/toggle-status', [AdminVoucherController::class, 'toggleStatus'])->name('admin.voucher.toggle_status');

        // Mitra Industri Admin
        Route::get('/mitra_industri', [\App\Http\Controllers\MitraIndustriController::class, 'index'])->name('admin.mitra_industri');
        Route::post('/mitra_industri', [\App\Http\Controllers\MitraIndustriController::class, 'store'])->name('admin.mitra_industri.store');
        Route::put('/mitra_industri/{id}', [\App\Http\Controllers\MitraIndustriController::class, 'update'])->name('admin.mitra_industri.update');
        Route::delete('/mitra_industri/{id}', [\App\Http\Controllers\MitraIndustriController::class, 'destroy'])->name('admin.mitra_industri.destroy');
        Route::post('/mitra_industri/{id}/toggle', [\App\Http\Controllers\MitraIndustriController::class, 'toggleStatus'])->name('admin.mitra_industri.toggle');

        // Setting Website Admin
        Route::get('/setting_website', [\App\Http\Controllers\SettingWebsiteController::class, 'index'])->name('admin.setting_website');
        Route::post('/setting_website', [\App\Http\Controllers\SettingWebsiteController::class, 'update'])->name('admin.setting_website.update');

        // Kelola Testimoni Admin
        Route::get('/testimoni', [\App\Http\Controllers\TestimoniController::class, 'index'])->name('admin.testimoni');
        Route::post('/testimoni', [\App\Http\Controllers\TestimoniController::class, 'store'])->name('admin.testimoni.store');
        Route::put('/testimoni/{id}', [\App\Http\Controllers\TestimoniController::class, 'update'])->name('admin.testimoni.update');
        Route::delete('/testimoni/{id}', [\App\Http\Controllers\TestimoniController::class, 'destroy'])->name('admin.testimoni.destroy');
        Route::post('/testimoni/{id}/toggle', [\App\Http\Controllers\TestimoniController::class, 'toggleStatus'])->name('admin.testimoni.toggle');

        // Kelola Berita / News Admin
        Route::resource('admin/news', \App\Http\Controllers\AdminNewsController::class)->names('admin.news');

        // Broadcast Web Push Notification Admin
        Route::post('/admin/webpush/broadcast', [\App\Http\Controllers\WebPushController::class, 'broadcast'])->name('admin.webpush.broadcast');
        Route::delete('/admin/webpush/subscription/{id}', [\App\Http\Controllers\WebPushController::class, 'deleteSubscription'])->name('admin.webpush.subscription.delete');
        Route::post('/admin/webpush/device/{id}/test', [\App\Http\Controllers\WebPushController::class, 'sendTestToDevice'])->name('admin.webpush.device.test');

        // Admin Telegram Integration
        Route::post('/admin/telegram/link-token', [TelegramController::class, 'createLinkToken'])->name('admin.telegram.link_token');
        Route::post('/admin/telegram/unlink', [TelegramController::class, 'unlink'])->name('admin.telegram.unlink');
    });

    // Premium Customer Routes
    Route::middleware('only.customer')->group(function () {
        Route::get('/premium/member', [PremiumCustomerController::class, 'member'])->name('premium.member');
        Route::get('/premium/referral', [PremiumCustomerController::class, 'referral'])->name('premium.referral');
        Route::post('/premium/voucher/{id_voucher}/klaim', [PremiumCustomerController::class, 'klaimVoucher'])->name('premium.voucher.klaim');
        Route::get('/premium/riwayat', [PremiumCustomerController::class, 'riwayat'])->name('premium.riwayat');
        Route::get('/premium/kredensial/{order_id}', [PremiumCustomerController::class, 'kredensial'])->name('premium.kredensial');
        Route::get('/premium/download-digital/{order_id}', [PremiumCustomerController::class, 'downloadDigital'])->name('premium.digital.download');
        Route::get('/premium/invoice/{order_id}/download', [PremiumCustomerController::class, 'downloadInvoice'])->name('premium.invoice.download');
        Route::get('/premium/review/{order_id}', [PremiumCustomerController::class, 'reviewShow'])->name('premium.review.show');
        Route::post('/premium/review/{order_id}', [PremiumCustomerController::class, 'reviewStore'])->name('premium.review.store');
        Route::get('/premium/laporan', [LaporanController::class, 'index'])->name('customer.laporan');
        Route::post('/premium/laporan', [LaporanController::class, 'store'])->name('customer.laporan.store');

        // Customer Telegram Integration
        Route::post('/customer/telegram/link-token', [TelegramController::class, 'createLinkToken'])->name('customer.telegram.link_token');
        Route::post('/customer/telegram/unlink', [TelegramController::class, 'unlink'])->name('customer.telegram.unlink');
        Route::post('/customer/telegram/channel-preference', [TelegramController::class, 'updateChannelPreference'])->name('customer.telegram.channel_preference');
    });

    // Seller Routes
    Route::middleware('only.seller')->group(function () {
        Route::get('/seller/dashboard', [DashboardController::class, 'seller_index'])->name('seller.dashboard');
        Route::get('/seller/mutasi', [DashboardController::class, 'seller_mutasi']);

        // Seller Toko Profil & Badges
        Route::get('/seller/profil', [SellerTokoController::class, 'index'])->name('seller.profil');
        Route::post('/seller/profil/update', [SellerTokoController::class, 'update']);
        Route::get('/seller/badges', [SellerTokoController::class, 'badges'])->name('seller.badges');

        // Seller Telegram Integration
        Route::post('/seller/telegram/link-token', [TelegramController::class, 'createLinkToken'])->name('seller.telegram.link_token');
        Route::post('/seller/telegram/unlink', [TelegramController::class, 'unlink'])->name('seller.telegram.unlink');

        // Seller Voucher CRUD
        Route::get('/seller/voucher', [SellerVoucherController::class, 'index'])->name('seller.voucher.index');
        Route::get('/seller/voucher/create', [SellerVoucherController::class, 'create'])->name('seller.voucher.create');
        Route::post('/seller/voucher', [SellerVoucherController::class, 'store'])->name('seller.voucher.store');
        Route::get('/seller/voucher/{id}/edit', [SellerVoucherController::class, 'edit'])->name('seller.voucher.edit');
        Route::put('/seller/voucher/{id}', [SellerVoucherController::class, 'update'])->name('seller.voucher.update');
        Route::post('/seller/voucher/{id}/toggle-status', [SellerVoucherController::class, 'toggleStatus'])->name('seller.voucher.toggle_status');

        // Seller Product CRUD (premium only)
        Route::resource('menu_produk', ProductController::class, ['except' => ['index', 'show']]);

        // Seller Product CRUD (digital only)
        Route::resource('menu_produk_digital', ProductDigitalController::class, ['except' => ['index', 'show']]);
    });

    // Thread Percakapan Tiket Laporan (Dapat diakses oleh Customer, Seller, dan Admin yang berwenang)
    Route::get('/premium/laporan/{id}', [LaporanController::class, 'show'])->name('laporan.show');
    Route::post('/premium/laporan/{id}/pesan', [LaporanController::class, 'store_pesan'])->name('laporan.pesan.store');

    Route::get('/logout', [AuthController::class, 'logout']);
});

Route::middleware('guest')->group(function () {

    Route::get('/lupa_password', function () {
        return view('auth.lupa_password');
    })->name('password.request');

    Route::post('/lupa_password', function (Request $request) {

        function statusFunction($status)
        {
            return $status;
        }

        $request->validate(['email' => 'required|email'], [
            'email.required' => 'Email harus diisi.',
            'email.email' => 'Email tidak valid.'
        ]);

        $status = Password::sendResetLink(
            $request->only('email')
        );

        return $status === Password::RESET_LINK_SENT
            ? back()->with(['status' => __(statusFunction('Link reset password telah berhasil dikirim melalui email. Silakan periksa email Anda.'))])
            : back()->withErrors(['email' => __(statusFunction('Email tidak ada.'))]);
    })->name('password.email');

    Route::get('/reset_password/{token}', function (string $token) {
        return view('auth.reset_password', ['token' => $token]);
    })->name('password.reset');

    Route::post('/reset_password', function (Request $request) {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:10|confirmed',
            'password_confirmation' => 'required',
        ], [
            'password.required' => 'Password harus di isi.',
            'password.min' => 'Password setidaknya minimal 10 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
            'password_confirmation.required' => 'Konfirmasi password harus di isi.',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password)
                ])->setRememberToken(Str::random(60));

                $user->save();

                event(new PasswordReset($user));
            }
        );

        return $status === Password::PASSWORD_RESET
            ? redirect()->route('login')->with('status', __($status))
            : back()->withErrors(['email' => [__($status)]]);
    })->name('password.update');

    Route::controller(AuthController::class)->group(function () {
        Route::get('/login', 'index')->name('login');
        Route::get('/pendaftaran', 'pendaftaran');
        Route::get('/redirect', 'redirect');
        Route::get('/auth/google/callback', 'callback');

        Route::post('/proses_login', 'proses_login')->middleware('throttle:limit_login');
        Route::get('/proses_login', function () { return redirect()->route('login'); });

        Route::post('/proses_pendaftaran', 'proses_pendaftaran')->middleware('throttle:limit_login');
        Route::get('/proses_pendaftaran', function () { return redirect('/pendaftaran'); });
        
        Route::post('/proses_lupa_password', 'proses_lupa_password');
        Route::get('/proses_lupa_password', function () { return redirect()->route('password.request'); });
    });
});

