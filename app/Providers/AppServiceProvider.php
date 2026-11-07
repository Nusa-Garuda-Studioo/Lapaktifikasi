<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;

use App\Models\BeliProdukModel;
use App\Models\Pembelian;
use App\Models\Review;
use App\Models\News;
use App\Models\SettingWebsite;
use App\Observers\BeliProdukObserver;
use App\Observers\PembelianObserver;
use App\Observers\ReviewObserver;
use App\Policies\PembelianPolicy;
use App\Policies\NewsPolicy;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $webPushAutoload = app_path('Packages/WebPush/autoload.php');
        if (file_exists($webPushAutoload)) {
            require_once $webPushAutoload;
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (config('app.env') === 'production') {
            URL::forceScheme('https');
        }

        // Model Observers
        if (class_exists(BeliProdukModel::class) && class_exists(BeliProdukObserver::class)) {
            BeliProdukModel::observe(BeliProdukObserver::class);
        }
        if (class_exists(Pembelian::class) && class_exists(PembelianObserver::class)) {
            Pembelian::observe(PembelianObserver::class);
        }
        if (class_exists(Review::class) && class_exists(ReviewObserver::class)) {
            Review::observe(ReviewObserver::class);
        }

        // Model Policies
        if (class_exists(Pembelian::class) && class_exists(PembelianPolicy::class)) {
            Gate::policy(Pembelian::class, PembelianPolicy::class);
        }
        if (class_exists(News::class) && class_exists(NewsPolicy::class)) {
            Gate::policy(News::class, NewsPolicy::class);
        }

        // Event Listeners
        Event::listen(Registered::class, SendEmailVerificationNotification::class);

        // Rate Limiters
        $this->configureRateLimiting();

        // Global Site Settings (Cached via Redis / Cache Store to eliminate per-request DB queries)
        try {
            if (!app()->runningInConsole()) {
                $websiteSettings = SettingWebsite::getCached();
                if ($websiteSettings) {
                    View::share('websiteSettings', $websiteSettings);
                }
            }
        } catch (\Throwable $e) {
            // Safe fallback during migration, testing or console commands
        }
    }

    /**
     * Configure the rate limiters for the application.
     */
    protected function configureRateLimiting(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('auth_rate_limit', function (Request $request) {
            return Limit::perMinute(6)->by($request->ip())->response(function () {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Terlalu banyak percobaan autentikasi. Silakan tunggu 1 menit.',
                    'errors' => []
                ], 429);
            });
        });

        RateLimiter::for('checkout_rate_limit', function (Request $request) {
            return Limit::perMinute(10)->by($request->user()?->id ?: $request->ip())->response(function () {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Terlalu banyak permintaan transaksi dalam waktu singkat. Silakan tunggu.',
                    'errors' => []
                ], 429);
            });
        });

        RateLimiter::for('webhook_rate_limit', function (Request $request) {
            return Limit::perMinute(120)->by($request->ip());
        });

        RateLimiter::for('limit_login', function (Request $request) {
            return Limit::perMinute(6)->by($request->ip());
        });
    }
}
