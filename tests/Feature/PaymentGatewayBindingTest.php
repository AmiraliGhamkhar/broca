<?php

namespace Tests\Feature;

use App\Contracts\PaymentGateway;
use App\Providers\AppServiceProvider;
use App\Services\ZibalGateway;
use Tests\TestCase;

class PaymentGatewayBindingTest extends TestCase
{
    public function test_zibal_configuration_resolves_the_zibal_gateway(): void
    {
        config()->set('payment.default', 'zibal');

        (new AppServiceProvider($this->app))->register();

        $this->assertInstanceOf(
            ZibalGateway::class,
            $this->app->make(PaymentGateway::class),
        );
    }
}
