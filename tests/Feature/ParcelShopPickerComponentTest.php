<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use WeBRTeu\Gls\Facades\Gls;
use WeBRTeu\Gls\GlsCarrier;
use WeBRTeu\Gls\GlsServiceProvider;
use WeBRTeu\Shipping\Facades\Shipping;

it('renders the GLS Map widget once with the input', function (): void {
    $html = Blade::render(<<<'BLADE'
        <x-gls::parcel-shop-picker wire:model="psd" />
        <x-gls::parcel-shop-picker name="alt" country="HU" class="map" />
        BLADE);

    expect(substr_count($html, 'gls-dpm.js'))->toBe(1)
        ->and($html)->toContain('name="gls_psd_id"')
        ->toContain('name="alt"')
        ->toContain('wire:model="psd"')
        ->toContain('country="hu"')
        ->toContain('class="map"');
});

it('registers the carrier and publishes its files', function (): void {
    expect(Shipping::available())->toContain('gls')
        ->and(Gls::getFacadeRoot())->toBeInstanceOf(GlsCarrier::class)
        ->and(ServiceProvider::pathsToPublish(GlsServiceProvider::class, 'gls-views'))->toHaveCount(1);
});
