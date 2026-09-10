<?php

namespace Tests\Feature;

use App\Services\Telegram\TelegramApiClient;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

/**
 * Round-6 audit I-3 (re-audited Round 10): the bot token is a URL path
 * segment, so a transport failure's message contains the full URL — token
 * included — and report() would write it to laravel.log, which gets pasted
 * into support chats. The client redacts at one choke point; the fix shipped
 * in Round 6 but had no regression test locking it.
 */
class TelegramTokenRedactionTest extends TestCase
{
    public function test_transport_failure_redacts_the_bot_token(): void
    {
        config()->set('services.telegram.bot_token', 'SECRET-TOKEN-123');

        Http::fake(fn () => throw new ConnectionException(
            'cURL error 28: Operation timed out for https://api.telegram.org/botSECRET-TOKEN-123/sendMessage'
        ));

        try {
            app(TelegramApiClient::class)->sendMessage(42, 'سلام');

            $this->fail('a transport failure must throw');
        } catch (RuntimeException $exception) {
            $this->assertStringNotContainsString(
                'SECRET-TOKEN-123',
                $exception->getMessage(),
                'the token must never reach the exception message (and hence the log)'
            );
            $this->assertStringContainsString('REDACTED_TOKEN', $exception->getMessage());
            $this->assertInstanceOf(
                ConnectionException::class,
                $exception->getPrevious(),
                'the original stays on the chain for report() context'
            );
        }
    }
}
