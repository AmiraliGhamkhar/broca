<?php

namespace Tests\Unit;

use App\Services\Sms\SmsDeliveryException;
use App\Services\Sms\SmsManager;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

/**
 * The SMS stack is driver-based because no panel had been chosen when this
 * path was built. These tests pin the three properties the rest of the app
 * depends on:
 *
 *  - a fresh install (default `log` driver) delivers nothing but breaks
 *    nothing;
 *  - the generic HTTP driver sends whatever the operator's panel expects, with
 *    the placeholders substituted, so switching vendor is an env change;
 *  - a panel that answers HTTP 200 with an error body is a failure, not a
 *    success — most of them do exactly that.
 */
class SmsManagerTest extends TestCase
{
    public function test_the_default_driver_writes_to_the_log_and_reports_delivery(): void
    {
        config()->set('sms.enabled', true);
        config()->set('sms.default', 'log');

        $result = (new SmsManager)->send('09123456789', 'کد تأیید: ۱۲۳۴۵۶', ['code' => '123456']);

        $this->assertTrue($result->delivered);
        $this->assertSame('log', $result->driver);
    }

    public function test_an_invalid_number_is_refused_before_anything_is_sent(): void
    {
        config()->set('sms.default', 'log');

        $result = (new SmsManager)->send('12345', 'متن');

        $this->assertFalse($result->delivered);
    }

    public function test_disabling_sms_stops_delivery_without_throwing(): void
    {
        config()->set('sms.enabled', false);
        config()->set('sms.default', 'log');

        $result = (new SmsManager)->send('09123456789', 'متن');

        $this->assertFalse($result->delivered);
    }

    public function test_the_http_driver_substitutes_placeholders_into_the_configured_body(): void
    {
        Http::fake(['*' => Http::response(['status' => 'ok'], 200)]);

        config()->set('sms.enabled', true);
        config()->set('sms.default', 'http');
        config()->set('sms.from', '30001234');
        config()->set('sms.drivers.http', [
            'url' => 'https://panel.example/send',
            'method' => 'POST',
            'encode' => 'json',
            'headers' => ['X-API-KEY' => 'secret-key'],
            'body' => ['receptor' => ':to', 'message' => ':message', 'sender' => ':from', 'code' => ':code'],
            'success_status' => [200],
            'success_contains' => null,
            'timeout' => 5,
            'verify' => true,
        ]);

        $result = (new SmsManager)->send('09123456789', 'کد شما', ['code' => '123456']);

        $this->assertTrue($result->delivered);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://panel.example/send'
                && $request->hasHeader('X-API-KEY', 'secret-key')
                && $request->data()['receptor'] === '09123456789'
                && $request->data()['sender'] === '30001234'
                && $request->data()['message'] === 'کد شما'
                && $request->data()['code'] === '123456';
        });
    }

    public function test_a_panel_answering_200_with_an_error_body_counts_as_a_failure(): void
    {
        Http::fake(['*' => Http::response(['error' => 'insufficient credit'], 200)]);

        config()->set('sms.enabled', true);
        config()->set('sms.default', 'http');
        config()->set('sms.drivers.http', [
            'url' => 'https://panel.example/send',
            'method' => 'POST',
            'encode' => 'json',
            'headers' => [],
            'body' => ['receptor' => ':to', 'message' => ':message'],
            'success_status' => [200],
            'success_contains' => 'success',
            'timeout' => 5,
            'verify' => true,
        ]);

        $this->expectException(SmsDeliveryException::class);

        (new SmsManager)->send('09123456789', 'متن');
    }

    public function test_an_unconfigured_http_driver_degrades_instead_of_crashing(): void
    {
        config()->set('sms.enabled', true);
        config()->set('sms.default', 'http');
        config()->set('sms.drivers.http', [
            'url' => null,
            'method' => 'POST',
            'encode' => 'json',
            'headers' => [],
            'body' => [],
            'success_status' => [200],
            'success_contains' => null,
            'timeout' => 5,
            'verify' => true,
        ]);

        // A half-configured .env must mean "no text messages", not "signup is
        // down".
        $result = (new SmsManager)->send('09123456789', 'متن');

        $this->assertFalse($result->delivered);
    }

    public function test_send_quietly_never_throws(): void
    {
        config()->set('sms.enabled', true);
        config()->set('sms.default', 'http');
        config()->set('sms.drivers.http', [
            'url' => 'https://panel.example/send',
            'method' => 'POST',
            'encode' => 'json',
            'headers' => [],
            'body' => ['receptor' => ':to'],
            'success_status' => [200],
            'success_contains' => 'success',
            'timeout' => 5,
            'verify' => true,
        ]);

        Http::fake(['*' => fn () => throw new RuntimeException('boom')]);

        $result = (new SmsManager)->sendQuietly('09123456789', 'متن');

        $this->assertFalse($result->delivered);
    }
}
