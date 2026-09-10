<?php

namespace Tests\Feature;

use App\Services\Payments\TimeoutHttpClient;
use App\Services\Payments\TimeoutZarinpal;
use App\Services\Payments\TimeoutZarinpalNormal;
use App\Services\Payments\TimeoutZarinpalSandbox;
use App\Services\Payments\TimeoutZibal;
use GuzzleHttp\Client;
use ReflectionClass;
use ReflectionMethod;
use Shetabit\Multipay\Drivers\Zarinpal\Strategies\Normal;
use Shetabit\Multipay\Invoice as MultipayInvoice;
use Shetabit\Multipay\Payment;
use Tests\TestCase;

/**
 * Round-6 audit B-4 (verified + fixed Round 10): shetabit/multipay builds
 * every driver with a bare `new Client()` — Guzzle's default timeout is 0,
 * so a hung gateway holds a PHP worker until max_execution_time.
 *
 * These tests prove the wiring end to end without touching the network: the
 * manager resolves the app subclasses from config, and each subclass carries
 * a bounded client. They also pin the vendor constructor shapes the
 * subclasses track (shetabit/multipay v3.0.4): if a vendor upgrade changes a
 * parent ctor, instantiation fails loudly here instead of silently in prod.
 */
class PaymentTimeoutTest extends TestCase
{
    public function test_payment_manager_resolves_timeout_bound_zarinpal_driver(): void
    {
        // CI's .env sets ZARINPAL_SANDBOX=true; pin the mode explicitly so
        // this test resolves the same strategy in every environment.
        config()->set('payment.drivers.zarinpal.mode', 'normal');

        $driver = $this->freshDriver(new Payment((array) config('payment')), 'zarinpal');

        $this->assertInstanceOf(TimeoutZarinpal::class, $driver);

        $strategy = (new ReflectionClass($driver))->getProperty('strategy');
        $strategy = $strategy->getValue($driver);

        $this->assertInstanceOf(TimeoutZarinpalNormal::class, $strategy);
        $this->assertBoundedClient($strategy);
    }

    public function test_payment_manager_resolves_timeout_bound_zarinpal_sandbox_driver(): void
    {
        config()->set('payment.drivers.zarinpal.mode', 'sandbox');

        $driver = $this->freshDriver(new Payment((array) config('payment')), 'zarinpal');

        $this->assertInstanceOf(TimeoutZarinpal::class, $driver);

        $strategy = (new ReflectionClass($driver))->getProperty('strategy');
        $strategy = $strategy->getValue($driver);

        $this->assertInstanceOf(TimeoutZarinpalSandbox::class, $strategy);
        $this->assertBoundedClient($strategy);
    }

    public function test_payment_manager_resolves_timeout_bound_zibal_driver(): void
    {
        $driver = $this->freshDriver(new Payment((array) config('payment')), 'zibal');

        $this->assertInstanceOf(TimeoutZibal::class, $driver);
        $this->assertBoundedClient($driver);
    }

    public function test_sandbox_strategy_carries_a_bounded_client(): void
    {
        $strategy = new TimeoutZarinpalSandbox(new MultipayInvoice, (object) [
            'mode' => 'sandbox',
            'currency' => 'R',
        ]);

        $this->assertBoundedClient($strategy);
    }

    public function test_vendor_strategies_still_default_to_no_timeout(): void
    {
        // Guard against the test passing for the wrong reason: if a future
        // vendor release adds its own timeout, the subclasses become redundant
        // and this assertion names the cleanup instead of hiding it.
        $vendor = new Normal(new MultipayInvoice, (object) []);

        $client = (new ReflectionClass($vendor))->getProperty('client')->getValue($vendor);

        // Guzzle ships no `timeout` default at all (null = the curl handle
        // waits forever) — that missing default is exactly what B-4 fixes.
        $this->assertNull($client->getConfig('timeout'));
    }

    /**
     * Instantiate the manager's driver without performing any HTTP: the
     * invoice property is seeded by reflection so getFreshDriverInstance()
     * never runs purchase()/verify().
     */
    private function freshDriver(Payment $payment, string $driver): object
    {
        $payment->via($driver);

        $invoice = (new ReflectionClass($payment))->getProperty('invoice');
        $invoice->setValue($payment, new MultipayInvoice);

        $fresh = new ReflectionMethod($payment, 'getFreshDriverInstance');

        return $fresh->invoke($payment);
    }

    private function assertBoundedClient(object $driver): void
    {
        $client = (new ReflectionClass($driver))->getProperty('client')->getValue($driver);

        $this->assertInstanceOf(Client::class, $client);
        $this->assertSame(TimeoutHttpClient::TIMEOUT_SECONDS, $client->getConfig('timeout'));
        $this->assertSame(TimeoutHttpClient::CONNECT_TIMEOUT_SECONDS, $client->getConfig('connect_timeout'));
    }
}
