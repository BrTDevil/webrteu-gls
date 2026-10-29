<?php

declare(strict_types=1);

namespace WeBRTeu\Gls\Tests;

use Illuminate\Database\Migrations\Migration;
use Orchestra\Testbench\TestCase as Orchestra;
use WeBRTeu\Gls\GlsServiceProvider;
use WeBRTeu\Shipping\ShippingServiceProvider;

abstract class TestCase extends Orchestra
{
    /**
     * @return list<class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [ShippingServiceProvider::class, GlsServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']);
        $app['config']->set('gls.username', env('GLS_SANDBOX_USERNAME', 'magazin@example.com'));
        $app['config']->set('gls.password', env('GLS_SANDBOX_PASSWORD', 'secret-pass'));
        $app['config']->set('gls.client_number', (int) env('GLS_SANDBOX_CLIENT_NUMBER', 100000001));
        $app['config']->set('gls.pickup_address', [
            'name' => 'Magazin SRL', 'street' => 'Str. Fabricii', 'house_number' => '5', 'city' => 'Brașov',
            'zip' => '500001', 'country' => 'RO', 'contact_phone' => '0720000000', 'contact_email' => 'depozit@example.com',
        ]);
    }

    protected function defineDatabaseMigrations(): void
    {
        /** @var Migration $migration */
        $migration = require __DIR__.'/../vendor/webrteu/shipping/database/migrations/create_shipments_tables.php.stub';
        $migration->up();
    }
}
