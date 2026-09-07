<?php

namespace App\Services\Telegram;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class TelegramApiClient
{
    public function sendMessage(int $chatId, string $text, array $options = []): array
    {
        // Plain text is intentional: titles, names and validation errors come
        // from administrators and may contain <, > or &. HTML parse mode would
        // reject such messages or interpret user content as markup.
        return $this->request('sendMessage', array_merge([
            'chat_id' => $chatId,
            'text' => $text,
        ], $options));
    }

    public function sendDocument(int $chatId, string $path, ?string $caption = null): array
    {
        if (! is_file($path)) {
            throw new RuntimeException('Telegram document path not found: '.$path);
        }

        try {
            return $this->http()
                ->attach('document', fopen($path, 'r'), basename($path))
                ->post($this->endpoint('sendDocument'), array_filter([
                    'chat_id' => $chatId,
                    'caption' => $caption,
                ]))
                ->throw()
                ->json();
        } catch (ConnectionException $exception) {
            throw new RuntimeException($this->redactToken((string) $exception->getMessage()), previous: $exception);
        }
    }

    public function getFile(string $fileId): array
    {
        $response = $this->request('getFile', ['file_id' => $fileId]);

        return (array) ($response['result'] ?? []);
    }

    public function downloadTelegramFile(string $telegramPath, string $destination): void
    {
        $response = Http::timeout(config('services.telegram.timeout', 30))
            ->sink($destination)
            ->get($this->fileEndpoint($telegramPath));

        if (! $response->successful()) {
            // A sink would have written Telegram's JSON error body to the
            // destination — remove it so a broken file is never mistaken for
            // valid media, then fail with the actual status.
            @unlink($destination);

            throw new RuntimeException('دریافت فایل از تلگرام ناموفق بود (HTTP '.$response->status().').');
        }

        // Guard against a truncated transfer (connection cut mid-stream):
        // sink reports success as long as the body streamed, so compare the
        // byte count with the declared Content-Length when it is present.
        $contentLength = $response->header('Content-Length');
        if ($contentLength !== null && is_numeric($contentLength) && filesize($destination) !== (int) $contentLength) {
            @unlink($destination);

            throw new RuntimeException('فایل تلگرام ناقص دانلود شد؛ دوباره تلاش کنید.');
        }
    }

    public function setWebhook(string $url, ?string $secretToken = null, bool $dropPendingUpdates = false): array
    {
        return $this->request('setWebhook', array_filter([
            'url' => $url,
            'secret_token' => $secretToken,
            'drop_pending_updates' => $dropPendingUpdates,
        ], fn ($value) => $value !== null));
    }

    public function answerCallbackQuery(string $callbackQueryId, ?string $text = null): array
    {
        return $this->request('answerCallbackQuery', array_filter([
            'callback_query_id' => $callbackQueryId,
            'text' => $text,
        ], fn ($value) => $value !== null && $value !== ''));
    }

    private function request(string $method, array $payload = []): array
    {
        try {
            return $this->http()
                ->post($this->endpoint($method), $payload)
                ->throw()
                ->json();
        } catch (ConnectionException $exception) {
            // The bot token is a URL path segment, so transport-level
            // exception messages contain the full URL — including the token.
            // report() writes those messages to laravel.log, which gets
            // pasted into support chats (Round-6 audit I-3). Redact at this
            // single choke point; keep the original for the exception chain.
            throw new RuntimeException($this->redactToken((string) $exception->getMessage()), previous: $exception);
        }
    }

    /**
     * Replace the bot token in any string with a redacted placeholder.
     */
    private function redactToken(string $message): string
    {
        $token = (string) config('services.telegram.bot_token');

        if ($token === '') {
            return $message;
        }

        return str_replace($token, 'REDACTED_TOKEN', $message);
    }

    private function http(): PendingRequest
    {
        $token = (string) config('services.telegram.bot_token');

        if ($token === '') {
            throw new RuntimeException('Telegram bot token is not configured.');
        }

        return Http::asForm()->timeout(config('services.telegram.timeout', 30));
    }

    private function endpoint(string $method): string
    {
        $base = rtrim((string) config('services.telegram.api_base_url', 'https://api.telegram.org'), '/');
        $token = (string) config('services.telegram.bot_token');

        return sprintf('%s/bot%s/%s', $base, $token, ltrim($method, '/'));
    }

    private function fileEndpoint(string $telegramPath): string
    {
        $base = rtrim((string) config('services.telegram.api_base_url', 'https://api.telegram.org'), '/');
        $token = (string) config('services.telegram.bot_token');

        return sprintf('%s/file/bot%s/%s', $base, $token, ltrim($telegramPath, '/'));
    }
}
