<?php

declare(strict_types=1);

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use WeBRTeu\Gls\Exceptions\GlsException;
use WeBRTeu\Shipping\Enums\ShipmentStatus;
use WeBRTeu\Shipping\Facades\Shipping;

it('tracks a parcel with .NET dates', function (): void {
    Http::fake([GLS.'GetParcelStatuses' => Http::response(fixtureFile('get-parcel-statuses.json'))]);

    $tracking = Shipping::carrier('gls')->track('51234567');

    expect($tracking->status)->toBe(ShipmentStatus::Delivered)
        ->and($tracking->events)->toHaveCount(3)
        ->and($tracking->events[0]->code)->toBe('01')
        ->and($tracking->events[0]->location)->toBe('Cluj')
        ->and($tracking->events[1]->location)->toBeNull()
        ->and($tracking->deliveredAt?->getTimestampMs())->toBe(1767435000000);

    Http::assertSent(fn (Request $request): bool => $request['ParcelNumber'] === 51234567 && $request['LanguageIsoCode'] === 'RO');
});

it('throws on tracking errors', function (): void {
    Http::fake([GLS.'GetParcelStatuses' => Http::response(['GetParcelStatusErrors' => [['ErrorCode' => 1, 'ErrorDescription' => 'Not found']]])]);

    Shipping::carrier('gls')->track('1');
})->throws(GlsException::class, 'Not found');
