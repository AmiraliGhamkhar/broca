<?php

namespace App\Console\Commands;

use App\Services\Telegram\TelegramApiClient;
use Illuminate\Console\Command;

class RegisterTelegramWebhook extends Command
{
    protected $signature = 'broca:telegram-set-webhook {url? : Full webhook URL} {--drop-pending-updates : Ask Telegram to drop pending updates}';

    protected $description = 'Register the Telegram bot webhook for the Broca admin bot';

    public function handle(TelegramApiClient $telegram): int
    {
        $url = (string) ($this->argument('url') ?: rtrim((string) config('app.url'), '/').route('telegram.webhook', absolute: false));

        if (! filter_var($url, FILTER_VALIDATE_URL)) {
            $this->error('Webhook URL is invalid. Set APP_URL or pass a full URL.');

            return self::FAILURE;
        }

        // The webhook route fails closed without a secret (403 for every
        // unsigned update), so registering without one would brick the bot.
        // Refuse here with the fix in the message instead.
        $secret = (string) config('services.telegram.webhook_secret');
        if ($secret === '') {
            $this->error('TELEGRAM_WEBHOOK_SECRET is empty. Set a random secret in .env first — the webhook route rejects unsigned updates.');

            return self::FAILURE;
        }

        $response = $telegram->setWebhook(
            $url,
            $secret,
            (bool) $this->option('drop-pending-updates'),
        );

        $this->info('Telegram webhook registered.');
        $this->line(json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));

        return self::SUCCESS;
    }
}
