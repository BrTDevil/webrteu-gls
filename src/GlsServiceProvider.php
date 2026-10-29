<?php

declare(strict_types=1);

namespace WeBRTeu\Gls;

use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use WeBRTeu\Shipping\ShippingManager;

class GlsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/gls.php', 'gls');

        $this->app->singleton(GlsClient::class);
        $this->app->singleton(GlsCarrier::class);

        $this->callAfterResolving(ShippingManager::class, function (ShippingManager $shipping): void {
            $shipping->extend('gls', fn (Container $app): GlsCarrier => $app->make(GlsCarrier::class));
        });
    }

    public function boot(): void
    {
        Blade::anonymousComponentPath(__DIR__.'/../resources/views/components', 'gls');

        $this->publishes([
            __DIR__.'/../config/gls.php' => $this->app->configPath('gls.php'),
        ], 'gls-config');

        $this->publishes([
            __DIR__.'/../resources/views' => $this->app->resourcePath('views/vendor/gls'),
        ], 'gls-views');
    }
}
