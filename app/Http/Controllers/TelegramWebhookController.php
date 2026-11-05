<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessTelegramUpdate;
use App\Models\TelegramUpdate;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TelegramWebhookController extends Controller
{
    /**
     * Handle incoming Telegram webhook updates.
     */
    public function handle(Request $request): JsonResponse
    {
        $updateId = $request->input('update_id');

        if (!$updateId) {
            return response()->json(['message' => 'Missing update_id'], 400);
        }

        // Idempotency check: if update_id was already received, return 200 without dispatching
        if (TelegramUpdate::where('update_id', $updateId)->exists()) {
            return response()->json(['status' => 'already_processed'], 200);
        }

        try {
            $record = TelegramUpdate::create([
                'update_id' => $updateId,
                'received_at' => now(),
            ]);
        } catch (UniqueConstraintViolationException | QueryException $e) {
            return response()->json(['status' => 'already_processed'], 200);
        }

        ProcessTelegramUpdate::dispatch($request->all(), $record->id);

        return response()->json(['status' => 'ok'], 200);
    }
}

