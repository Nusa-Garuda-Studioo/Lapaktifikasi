<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group.
|
*/

// Webhook / Callbacks
Route::middleware('throttle:webhook_rate_limit')->group(function () {
    Route::post('/callback', [\App\Http\Controllers\MidtransController::class, 'callback']);
    Route::post('/callback/pakasir', [\App\Http\Controllers\PakasirController::class, 'callback']);
    Route::post('/callback/tripay', [\App\Http\Controllers\TriPayController::class, 'callback']);
});

// API V1
Route::prefix('v1')->namespace('App\Http\Controllers\Api')->group(function () {
    
    // Public Landing, Info & News
    Route::get('/home', 'PublicController@getHome');
    Route::get('/payment-channels', 'PublicController@getPaymentChannels');
    Route::get('/news', 'PublicController@getNews');
    Route::get('/news/{slug}', 'PublicController@getNewsDetail');

    // Public Catalog & Stores
    Route::get('/toko', 'CatalogController@getShops');
    Route::get('/toko/{id}', 'CatalogController@getShopDetail');
    Route::get('/toko/{id}/produk', 'CatalogController@getCatalog');
    Route::get('/katalog', 'CatalogController@getCatalog');
    Route::get('/produk/{id}', 'CatalogController@getProductDetail');
    Route::get('/produk/varian/{id}/stok', 'CatalogController@checkStock');

    // Auth (Protected with Auth Rate Limiter)
    Route::middleware('throttle:auth_rate_limit')->group(function () {
        Route::post('/auth/login', 'AuthController@login');
        Route::post('/auth/register', 'AuthController@register');
        Route::post('/auth/forgot-password', 'AuthController@forgotPassword');
        Route::post('/auth/reset-password', 'AuthController@resetPassword');
    });

    // Protected Routes (auth:sanctum & check.banned)
    Route::middleware(['auth:sanctum', 'check.banned'])->group(function () {
        
        // Auth Logout & Profile
        Route::post('/auth/logout', 'AuthController@logout');
        Route::get('/profile', 'ProfileController@getProfile');
        Route::post('/profile/update', 'ProfileController@updateProfile');

        // Customer Only Features
        Route::middleware('only.customer')->group(function () {
            // Checkout & Pembayaran (Protected with Checkout Rate Limiter)
            Route::middleware('throttle:checkout_rate_limit')->group(function () {
                Route::post('/checkout', 'CheckoutController@checkout');
                Route::post('/pembayaran/generate/{order_id}', 'CheckoutController@generateTransaction');
                Route::get('/pembayaran/status/{order_id}', 'CheckoutController@status');
            });
            
            // Premium Customer Data, Orders & Downloads
            Route::get('/customer/member', 'CustomerPremiumController@getMemberData');
            Route::get('/customer/referral', 'CustomerPremiumController@getReferralData');
            Route::get('/customer/riwayat', 'CustomerPremiumController@getRiwayat');
            Route::get('/customer/kredensial/{order_id}', 'CustomerPremiumController@getKredensial');
            Route::get('/customer/download-digital/{order_id}', 'CustomerPremiumController@downloadDigital');
            Route::get('/customer/invoice/{order_id}', 'CustomerPremiumController@downloadInvoice');
            Route::post('/customer/voucher/{id}/klaim', 'CustomerPremiumController@klaimVoucher');
            Route::post('/customer/review/{order_id}', 'CustomerPremiumController@storeReview');
            Route::get('/customer/laporan', 'CustomerPremiumController@getLaporan');
            Route::post('/customer/laporan', 'CustomerPremiumController@storeLaporan');
        });

        // Seller Only Features
        Route::middleware('only.seller')->group(function () {
            // Dashboard, Mutasi & Toko Profil
            Route::get('/seller/dashboard', 'SellerController@getDashboard');
            Route::get('/seller/mutasi', 'SellerController@getMutasi');
            Route::get('/seller/profil', 'SellerController@getProfil');
            Route::post('/seller/profil', 'SellerController@updateProfil');
            Route::get('/seller/badges', 'SellerController@getBadges');

            // CRUD Produk
            Route::get('/seller/produk', 'SellerProductController@index');
            Route::post('/seller/produk', 'SellerProductController@store');
            Route::get('/seller/produk/{id}', 'SellerProductController@show');
            Route::post('/seller/produk/{id}', 'SellerProductController@update');
            Route::delete('/seller/produk/{id}', 'SellerProductController@destroy');

            // Inventaris: Tipe Layanan
            Route::get('/seller/tipe', 'SellerInventoryController@indexTipe');
            Route::post('/seller/tipe', 'SellerInventoryController@storeTipe');
            Route::put('/seller/tipe/{id}', 'SellerInventoryController@updateTipe');
            Route::delete('/seller/tipe/{id}', 'SellerInventoryController@destroyTipe');

            // Inventaris: Varian Layanan
            Route::get('/seller/varian', 'SellerInventoryController@indexVarian');
            Route::post('/seller/varian', 'SellerInventoryController@storeVarian');
            Route::post('/seller/varian/{id}', 'SellerInventoryController@updateVarian');
            Route::put('/seller/varian/{id}', 'SellerInventoryController@updateVarian');
            Route::delete('/seller/varian/{id}', 'SellerInventoryController@destroyVarian');

            // Inventaris: Stok Akun
            Route::get('/seller/stok', 'SellerInventoryController@indexStok');
            Route::post('/seller/stok', 'SellerInventoryController@storeStok');
            Route::post('/seller/stok/bulk', 'SellerInventoryController@storeStokBulk');
            Route::get('/seller/stok/{id}/decrypt', 'SellerInventoryController@decryptStok');
            Route::delete('/seller/stok/{id}', 'SellerInventoryController@destroyStok');

            // Histori Penjualan Seller
            Route::get('/seller/histori-penjualan', 'SellerInventoryController@historiPenjualan');

            // CRUD Voucher Seller
            Route::get('/seller/voucher', 'SellerVoucherController@index');
            Route::post('/seller/voucher', 'SellerVoucherController@store');
            Route::put('/seller/voucher/{id}', 'SellerVoucherController@update');
            Route::post('/seller/voucher/{id}/toggle-status', 'SellerVoucherController@toggleStatus');
            Route::delete('/seller/voucher/{id}', 'SellerVoucherController@destroy');
        });

        // Admin Only Features
        Route::middleware('admin.only')->group(function () {
            // Dashboard
            Route::get('/admin/dashboard', 'AdminController@getDashboard');
            
            // Kelola Seller & Badges
            Route::get('/admin/kelola-seller', 'AdminController@getSellers');
            Route::post('/admin/kelola-seller', 'AdminController@storeSeller');
            Route::post('/admin/kelola-seller/{id}', 'AdminController@updateSeller');
            Route::post('/admin/kelola-seller/{id}/toggle-status', 'AdminController@toggleSellerStatus');
            Route::post('/admin/kelola-seller/{id}/ban', 'AdminController@banSeller');
            Route::post('/admin/kelola-seller/{id}/unban', 'AdminController@unbanSeller');
            Route::post('/admin/kelola-seller/{id}/badge/attach', 'AdminController@attachBadge');
            Route::post('/admin/kelola-seller/{id}/badge/detach/{id_badge}', 'AdminController@detachBadge');
            Route::post('/admin/kelola-seller/{id}/badge/custom', 'AdminController@createCustomBadge');

            // Kelola Customer
            Route::get('/admin/kelola-customer', 'AdminController@getCustomers');
            Route::post('/admin/kelola-customer/{id}/ban', 'AdminController@banCustomer');
            Route::post('/admin/kelola-customer/{id}/unban', 'AdminController@unbanCustomer');

            // Kelola Saldo Toko & Withdraw
            Route::get('/admin/saldo-toko', 'AdminController@getSaldoToko');
            Route::get('/admin/saldo-toko/{id}', 'AdminController@detailSaldoToko');
            Route::post('/admin/saldo-toko/{id}/withdraw', 'AdminController@withdrawSaldoToko');

            // Orders & Transaction Operations
            Route::get('/admin/orders', 'AdminController@getOrders');
            Route::post('/admin/pembayaran/{id}/retry-wa', 'AdminController@retryWa');
            Route::post('/admin/order/{order_id}/check-status', 'AdminController@checkPaymentStatus');

            // Setting Komisi & Maintenance
            Route::get('/admin/setting-komisi', 'AdminController@getSettingKomisi');
            Route::post('/admin/setting-komisi', 'AdminController@updateSettingKomisi');
            Route::post('/admin/setting-komisi/toggle-maintenance', 'AdminController@toggleMaintenance');

            // Setting Website
            Route::get('/admin/setting-website', 'AdminController@getSettingWebsite');
            Route::post('/admin/setting-website', 'AdminController@updateSettingWebsite');

            // Kelola Laporan
            Route::get('/admin/laporan', 'AdminController@getLaporan');
            Route::put('/admin/laporan/{id}/status', 'AdminController@updateLaporanStatus');

            // Kelola Voucher Admin (Global)
            Route::get('/admin/voucher', 'AdminController@getVoucherAdmin');
            Route::post('/admin/voucher', 'AdminController@storeVoucherAdmin');
            Route::put('/admin/voucher/{id}', 'AdminController@updateVoucherAdmin');
            Route::post('/admin/voucher/{id}/toggle-status', 'AdminController@toggleVoucherAdminStatus');
            Route::delete('/admin/voucher/{id}', 'AdminController@destroyVoucherAdmin');

            // Kelola Mitra Industri
            Route::get('/admin/mitra-industri', 'AdminController@getMitraIndustri');
            Route::post('/admin/mitra-industri', 'AdminController@storeMitraIndustri');
            Route::post('/admin/mitra-industri/{id}', 'AdminController@updateMitraIndustri');
            Route::post('/admin/mitra-industri/{id}/toggle', 'AdminController@toggleMitraIndustri');
            Route::delete('/admin/mitra-industri/{id}', 'AdminController@destroyMitraIndustri');

            // Kelola Testimoni
            Route::get('/admin/testimoni', 'AdminController@getTestimoni');
            Route::post('/admin/testimoni', 'AdminController@storeTestimoni');
            Route::put('/admin/testimoni/{id}', 'AdminController@updateTestimoni');
            Route::post('/admin/testimoni/{id}/toggle', 'AdminController@toggleTestimoni');
            Route::delete('/admin/testimoni/{id}', 'AdminController@destroyTestimoni');

            // Kelola News / Berita
            Route::get('/admin/news', 'AdminController@getNews');
            Route::post('/admin/news', 'AdminController@storeNews');
            Route::get('/admin/news/{id}', 'AdminController@showNews');
            Route::post('/admin/news/{id}', 'AdminController@updateNews');
            Route::delete('/admin/news/{id}', 'AdminController@destroyNews');
        });

    });

});
