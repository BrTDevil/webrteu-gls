<?php

declare(strict_types=1);

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use WeBRTeu\Gls\Exceptions\GlsException;
use WeBRTeu\Gls\GlsClient;
use WeBRTeu\Gls\Support\PasswordHash;
use WeBRTeu\Shipping\Exceptions\ShipmentStateUnknownException;
use WeBRTeu\Shipping\Exceptions\ShipmentValidationException;
use WeBRTeu\Shipping\Exceptions\UnsupportedOperationException;
use WeBRTeu\Shipping\Facades\Shipping;

it('creates the parcels and returns ParcelId, parcel numbers and the PDF', function (): void {
    Http::fake([GLS.'PrintLabels' => Http::response(fixtureFile('print-labels-ok.json'))]);

    $awb = Shipping::carrier('gls')->createShipment(shipmentData());

    expect($awb->awb)->toBe('51234567')
        ->and($awb->parcelNumbers)->toBe(['51234567', '51234568'])
        ->and($awb->raw['ParcelId'])->toBe(9001)
        ->and($awb->raw['ParcelIdList'])->toBe([9001, 9002])
        ->and(base64_decode($awb->raw['Labels']))->toStartWith('%PDF');

    Http::assertSent(fn (Request $request): bool => $request->url() === GLS.'PrintLabels'
        && $request['Username'] === 'magazin@example.com'
        && $request['Password'] === PasswordHash::bytes('secret-pass')
        && $request['TypeOfPrinter'] === 'Thermo'
        && $request['ParcelList'][0]['CODAmount'] === 149.7);
});

it('throws for an error list despite HTTP 200', function (): void {
    Http::fake([GLS.'PrintLabels' => Http::response(fixtureFile('print-labels-error-list.json'))]);

    expect(fn () => Shipping::carrier('gls')->createShipment(shipmentData()))
        ->toThrow(fn (ShipmentValidationException $e) => expect($e->errors)->toBe(['general' => ['14 Invalid zip code']]));
});

it('throws when no parcel comes back', function (): void {
    Http::fake([GLS.'PrintLabels' => Http::response(['PrintLabelsInfoList' => []])]);

    expect(fn () => Shipping::carrier('gls')->createShipment(shipmentData()))
        ->toThrow(ShipmentValidationException::class, 'niciun colet');
});

it('throws ShipmentStateUnknownException on timeout, without retry', function (): void {
    $attempts = 0;
    Http::fake(function () use (&$attempts): never {
        $attempts++;

        throw new ConnectionException('timeout');
    });

    expect(fn () => Shipping::carrier('gls')->createShipment(shipmentData()))->toThrow(ShipmentStateUnknownException::class);
    expect($attempts)->toBe(1);
});

it('reports HTTP errors without logging the password', function (): void {
    Log::spy();
    Http::fake([GLS.'*' => Http::response(['Message' => 'Unauthorized'], 401)]);

    expect(fn () => Shipping::carrier('gls')->createShipment(shipmentData()))->toThrow(GlsException::class, 'HTTP 401');
    Log::shouldHaveReceived('warning')->withArgs(fn (string $m, array $context): bool => ! str_contains((string) json_encode($context), 'secret-pass'));
});

it('uses the production host of the configured country and falls back to Romania', function (): void {
    Config::set('gls.sandbox', false);
    Config::set('gls.country', 'hu');
    Config::set('gls.printer_type', 'bogus');
    Http::fake(['https://api.mygls.hu/*' => Http::response(fixtureFile('print-labels-ok.json'))]);

    Shipping::carrier('gls')->createShipment(shipmentData());
    Http::assertSent(fn (Request $request): bool => $request['TypeOfPrinter'] === 'Thermo');

    Config::set('gls.country', 'XX');
    expect(app(GlsClient::class)->baseUrl())->toBe('https://api.mygls.ro');
});

it('does not quote', function (): void {
    Shipping::carrier('gls')->quote(shipmentData());
})->throws(UnsupportedOperationException::class);

it('lists its services', function (): void {
    expect(Shipping::carrier('gls')->services()->pluck('code')->all())->toBe(['standard', 'PSD']);
});
