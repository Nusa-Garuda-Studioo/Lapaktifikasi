<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class CheckIdCustomer
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user() ?? Auth::user();

        if ($user && method_exists($user, 'isSeller') && $user->isSeller()) {
            return redirect('/seller/profil');
        }

        if ($user && method_exists($user, 'isAdmin') && $user->isAdmin()) {
            return redirect()->route('admin.setting_website');
        }

        $profileId = (int) $request->route('id');

        if (!$user || $user->id !== $profileId || (int) $user->role_id === 1) {
            abort(403, 'Forbidden access.');
        }
        return $next($request);
    }
}
