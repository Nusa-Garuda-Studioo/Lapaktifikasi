<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckBannedUser
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check()) {
            $user = auth()->user();

            if ($user->is_banned) {
                $reason = $user->banned_reason ?? 'Akun Anda telah dinonaktifkan/dibanned.';

                if ($request->expectsJson() || $request->is('api/*')) {
                    if (method_exists($user, 'currentAccessToken') && $user->currentAccessToken()) {
                        $user->currentAccessToken()->delete();
                    }
                    return response()->json([
                        'success' => false,
                        'message' => 'Akun Anda telah dinonaktifkan/dibanned. Alasan: ' . $reason,
                        'data' => [
                            'is_banned' => true,
                            'banned_reason' => $reason
                        ]
                    ], 403);
                }

                auth()->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('banned.page')->with('banned_reason', $reason);
            }
        }

        return $next($request);
    }
}
