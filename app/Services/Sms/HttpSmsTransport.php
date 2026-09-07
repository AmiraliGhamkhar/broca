<?php

namespace App\Services\Sms;

use App\Contracts\SmsTransport;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Generic HTTP panel driver.
 *
 * One driver covers every Iranian panel whose API is "POST these fields to
 * this URL": the request is described entirely by config/sms.php (fed from
 * .env), and the field names — `receptor`, `to`, `msisdn`, … — live in the
 * body template rather than in code. Switching vendor is an env change.
 *
 * Security notes:
 *  - The URL is operator-supplied (env), never user-supplied, so this is not
 *    an SSRF sink; the scheme is still validated so a typo cannot make Laravel
 *    read a local file or hit an internal service.
 *  - The panel response is truncated before it is logged: panels echo the
 *    message body and (worse) credentials back in error responses.
 *  - A 200 from a panel is not proof of delivery — most answer HTTP 200 with
 *    a JSON error body — so a `success_contains` substring may be required as
 *    a second gate (SMS_HTTP_SUCCESS_CONTAINS).
 */
class HttpSmsTransport implements SmsTransport
{
    public function send(string $phone, string $message, array $context = []): SmsResult
    {
        $config = (array) config('sms.drivers.http', []);
        $url = trim((string) ($config['url'] ?? ''));

        if ($url === '' || ! $this->isHttpUrl($url)) {
            // Not configured (or configured nonsense): a normal state on a
            // fresh install. Fail soft so signup still completes.
            return SmsResult::failed('http', 'SMS_HTTP_URL is not configured or is not an http(s) URL');
        }

        $reference = (string) ($context['reference'] ?? '');
        $replacements = $this->replacements($phone, $message, $context, $reference);

        $payload = $this->fill((array) ($config['body'] ?? []), $replacements);
        $headers = $this->fill((array) ($config['headers'] ?? []), $replacements);
        $method = strtolower((string) ($config['method'] ?: 'post'));

        if (! in_array($method, ['get', 'post', 'put', 'patch'], true)) {
            return SmsResult::failed('http', 'SMS_HTTP_METHOD must be one of GET/POST/PUT/PATCH');
        }

        $target = strtr($url, $replacements);

        try {
            $request = Http::timeout((int) ($config['timeout'] ?? 15))
                ->withOptions(['verify' => (bool) ($config['verify'] ?? true)])
                ->withHeaders($headers);

            $response = strtolower((string) ($config['encode'] ?? 'json')) === 'form'
                ? $request->asForm()->{$method}($target, $payload)
                : $request->{$method}($target, $payload);
        } catch (ConnectionException $exception) {
            throw new SmsDeliveryException('ارتباط با پنل پیامک برقرار نشد.', previous: $exception);
        } catch (Throwable $exception) {
            throw new SmsDeliveryException('ارسال پیامک با خطا مواجه شد.', previous: $exception);
        }

        $status = $response->status();
        $body = mb_substr($response->body(), 0, 500);
        $expected = array_map('intval', (array) ($config['success_status'] ?? [200, 201, 202]));
        $contains = trim((string) ($config['success_contains'] ?? ''));

        $statusOk = in_array($status, $expected, true);
        $bodyOk = $contains === '' || str_contains($body, $contains);

        if (! $statusOk || ! $bodyOk) {
            throw new SmsDeliveryException(sprintf(
                'پنل پیامک پاسخ ناموفق داد (HTTP %d): %s',
                $status,
                $body
            ));
        }

        return SmsResult::delivered('http', 'HTTP '.$status, $reference === '' ? null : $reference);
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, string>
     */
    private function replacements(string $phone, string $message, array $context, string $reference): array
    {
        return [
            ':to' => $phone,
            ':message' => $message,
            ':from' => (string) (config('sms.from') ?? ''),
            ':code' => (string) ($context['code'] ?? ''),
            ':reference' => $reference,
        ];
    }

    /**
     * Recursively substitute placeholders in every scalar value of a payload.
     *
     * @param  array<mixed>  $payload
     * @return array<mixed>
     */
    private function fill(array $payload, array $replacements): array
    {
        foreach ($payload as $key => $value) {
            if (is_array($value)) {
                $payload[$key] = $this->fill($value, $replacements);

                continue;
            }

            if (is_scalar($value) || $value === null) {
                $payload[$key] = strtr((string) $value, $replacements);
            }
        }

        return $payload;
    }

    private function isHttpUrl(string $url): bool
    {
        return preg_match('#^https?://#i', $url) === 1;
    }
}
