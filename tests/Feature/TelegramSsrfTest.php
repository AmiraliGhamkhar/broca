<?php

namespace Tests\Feature;

use App\Services\Telegram\TelegramBotService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * SSRF hardening for the bot's admin "import from URL" path
 * (TelegramBotService::storeRemoteDocument). The URL originates from a
 * Telegram message, so every hop is re-validated and the connection is
 * pinned to the validated IP. IP literals are used throughout so the tests
 * never depend on DNS resolution.
 */
class TelegramSsrfTest extends TestCase
{
    private function service(): TelegramBotService
    {
        return app(TelegramBotService::class);
    }

    private function callPrivate(TelegramBotService $service, string $method, array $args): mixed
    {
        return (new \ReflectionMethod($service, $method))->invokeArgs($service, $args);
    }

    public function test_host_validation_rejects_loopback_private_and_metadata_space(): void
    {
        $service = $this->service();

        foreach ([
            '127.0.0.1',          // loopback (cPanel services live here)
            '10.0.0.5',           // RFC1918
            '192.168.1.1',        // RFC1918
            '172.16.0.9',         // RFC1918
            '169.254.169.254',    // cloud metadata endpoint
            '0.0.0.0',
            '',                   // empty host → 'URL معتبر نیست.'
        ] as $host) {
            try {
                $this->callPrivate($service, 'assertPublicHost', [$host]);
                $this->fail("Host '{$host}' must be rejected.");
            } catch (\RuntimeException $exception) {
                $this->assertNotEmpty($exception->getMessage());
            }
        }
    }

    public function test_host_validation_accepts_a_public_ip_and_returns_it(): void
    {
        $ip = $this->callPrivate($this->service(), 'assertPublicHost', ['93.184.216.34']);

        $this->assertSame('93.184.216.34', $ip);
    }

    public function test_scheme_validation_rejects_non_http_schemes(): void
    {
        foreach (['file:///etc/passwd', 'ftp://93.184.216.34/x.pdf', 'gopher://127.0.0.1/'] as $url) {
            try {
                $this->callPrivate($this->service(), 'assertPublicUrl', [$url]);
                $this->fail("Scheme of '{$url}' must be rejected.");
            } catch (\RuntimeException $exception) {
                $this->assertNotEmpty($exception->getMessage());
            }
        }
    }

    public function test_redirect_to_loopback_is_refused_before_the_second_fetch(): void
    {
        Storage::fake('local');
        Http::fake([
            'http://93.184.216.34/*' => Http::response('', 302, ['Location' => 'http://127.0.0.1/internal.pdf']),
        ]);

        try {
            $this->callPrivate($this->service(), 'storeRemoteDocument', ['http://93.184.216.34/asset.pdf', 'notes', 'مطلب تست']);
            $this->fail('A redirect to loopback must be rejected.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('مجاز نیست', $exception->getMessage());
        }

        // Exactly one request: the loopback target was refused BEFORE fetch.
        Http::assertSentCount(1);
    }

    public function test_relative_redirect_is_refused(): void
    {
        Storage::fake('local');
        Http::fake([
            'http://93.184.216.34/*' => Http::response('', 302, ['Location' => '/elsewhere.pdf']),
        ]);

        try {
            $this->callPrivate($this->service(), 'storeRemoteDocument', ['http://93.184.216.34/asset.pdf', 'notes', 'مطلب تست']);
            $this->fail('A relative redirect must be rejected.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('دنبال نمی‌شود', $exception->getMessage());
        }

        Http::assertSentCount(1);
    }

    public function test_public_redirect_chain_is_followed_and_stored(): void
    {
        Storage::fake('local');
        Http::fake([
            'http://93.184.216.34/*' => Http::response('', 302, ['Location' => 'http://8.8.4.4/files/final.pdf']),
            'http://8.8.4.4/*' => Http::response('PDFDATA', 200, ['Content-Type' => 'application/pdf']),
        ]);

        $result = $this->callPrivate($this->service(), 'storeRemoteDocument', ['http://93.184.216.34/asset.pdf', 'notes', 'مطلب تست']);

        $this->assertArrayHasKey('storage_key', $result);
        $this->assertTrue(Storage::disk('local')->exists($result['storage_key']));
        Http::assertSentCount(2);
    }
}
