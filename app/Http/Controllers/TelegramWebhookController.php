<?php

namespace App\Http\Controllers;

use App\Services\Telegram\TelegramBotService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TelegramWebhookController extends Controller
{
    public function __invoke(Request $request, TelegramBotService $bot): JsonResponse
    {
        abort_unless(config('services.telegram.enabled'), 404);

        $secret = (string) config('services.telegram.webhook_secret');
        if ($secret !== '') {
            abort_unless(hash_equals($secret, (string) $request->header('X-Telegram-Bot-Api-Secret-Token')), 403);
        }

        $bot->handle($request->all());

        return response()->json(['ok' => true]);
    }
}
