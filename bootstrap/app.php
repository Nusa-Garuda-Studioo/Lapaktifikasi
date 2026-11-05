<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->trustProxies(at: '*');

        $middleware->validateCsrfTokens(except: [
            'callback',
            'callback/*',
            'api/callback/*',
            'payment/*/callback',
            'api/telegram/webhook',
            'telegram/webhook',
        ]);

        $middleware->redirectGuestsTo(fn () => route('login'));

        $middleware->web(append: [
            \App\Http\Middleware\RedirectIfMustChangePassword::class,
            \App\Http\Middleware\CheckMaintenanceMode::class,
            \App\Http\Middleware\CheckBannedUser::class,
        ]);

        $middleware->alias([
            'check.id.customer'  => \App\Http\Middleware\CheckIdCustomer::class,
            'prevent.customer'   => \App\Http\Middleware\PreventCustomer::class,
            'admin.only'         => \App\Http\Middleware\AdminOnly::class,
            'reset.headers'      => \App\Http\Middleware\ResetHeaders::class,
            'only.customer'      => \App\Http\Middleware\OnlyCustomer::class,
            'only.seller'        => \App\Http\Middleware\OnlySeller::class,
            'check.maintenance'  => \App\Http\Middleware\CheckMaintenanceMode::class,
            'check.banned'       => \App\Http\Middleware\CheckBannedUser::class,
            'telegram.webhook'   => \App\Http\Middleware\VerifyTelegramWebhook::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
