<?php

namespace App\Http\Controllers;

use App\Services\Telegram\TelegramLinkService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class TelegramController extends Controller
{
    protected TelegramLinkService $linkService;

    public function __construct(TelegramLinkService $linkService)
    {
        $this->linkService = $linkService;
    }

    /**
     * Buat token penautan akun Telegram untuk user saat ini.
     */
    public function createLinkToken(Request $request): JsonResponse|RedirectResponse
    {
        $user = Auth::user();

        if (!$user) {
            if ($request->expectsJson()) {
                return response()->json(['status' => 'error', 'message' => 'Unauthenticated.'], 401);
            }
            return redirect()->back()->with('error', 'Silakan login terlebih dahulu.');
        }

        $tokenData = $this->linkService->createLinkToken($user);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'status' => 'success',
                'token' => $tokenData['token'],
                'url' => $tokenData['url'],
                'expires_at' => $tokenData['expires_at']->toIso8601String(),
                'expires_in_minutes' => 15,
            ]);
        }

        return redirect()->back()
            ->with('telegram_link_token', $tokenData['token'])
            ->with('telegram_link_url', $tokenData['url'])
            ->with('success', 'Token penautan Telegram berhasil dibuat. Klik tautan untuk membuka bot.');
    }

    /**
     * Putus penautan akun Telegram untuk user saat ini.
     */
    public function unlink(Request $request): JsonResponse|RedirectResponse
    {
        $user = Auth::user();

        if (!$user) {
            if ($request->expectsJson()) {
                return response()->json(['status' => 'error', 'message' => 'Unauthenticated.'], 401);
            }
            return redirect()->back()->with('error', 'Silakan login terlebih dahulu.');
        }

        $this->linkService->unlink($user);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Tautan akun Telegram berhasil diputuskan.',
            ]);
        }

        return redirect()->back()->with('success', 'Tautan akun Telegram Anda telah berhasil diputuskan.');
    }

    /**
     * Perbarui preferensi channel notifikasi tiket laporan (auto / telegram / whatsapp).
     */
    public function updateChannelPreference(Request $request): JsonResponse|RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'notif_channel_laporan' => 'required|in:auto,telegram,whatsapp',
        ], [
            'notif_channel_laporan.required' => 'Pilih saluran notifikasi laporan.',
            'notif_channel_laporan.in' => 'Saluran notifikasi tidak valid (pilih auto, telegram, atau whatsapp).',
        ]);

        if ($validator->fails()) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'status' => 'error',
                    'errors' => $validator->errors(),
                ], 422);
            }
            return redirect()->back()->withErrors($validator);
        }

        $user = Auth::user();
        $user->update([
            'notif_channel_laporan' => $request->input('notif_channel_laporan'),
        ]);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Preferensi saluran notifikasi berhasil disimpan.',
                'channel' => $user->notif_channel_laporan,
            ]);
        }

        return redirect()->back()->with('success', 'Preferensi saluran notifikasi berhasil disimpan.');
    }
}

