<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyTelegramWebhook
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $expectedSecret = (string) config('services.telegram.webhook_secret');
        $providedSecret = (string) $request->header('X-Telegram-Bot-Api-Secret-Token', '');

        if ($expectedSecret === '' || $providedSecret === '' || !hash_equals($expectedSecret, $providedSecret)) {
            return response()->json([
                'message' => 'Unauthorized: Invalid or missing Telegram webhook secret token.',
            ], 403);
        }

        return $next($request);
    }
}

