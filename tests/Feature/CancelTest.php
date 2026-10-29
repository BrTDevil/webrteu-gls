<?php

declare(strict_types=1);

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use WeBRTeu\Gls\Exceptions\GlsException;
use WeBRTeu\Shipping\Enums\LabelFormat;
use WeBRTeu\Shipping\Enums\ShipmentStatus;
use WeBRTeu\Shipping\Facades\Shipping;
use WeBRTeu\Shipping\Models\Shipment;

beforeEach(function (): void {
    Shipment::query()->create(['carrier' => 'gls', 'awb' => '51234567', 'status' => ShipmentStatus::Created, 'raw' => ['ParcelId' => 9001, 'ParcelIdList' => [9001, 9002]]]);
});

it('deletes the labels by ParcelId', function (): void {
    Http::fake([GLS.'DeleteLabels' => Http::sequence()
        ->push(fixtureFile('delete-labels.json'))
        ->push(['SuccessfullyDeletedList' => [], 'DeleteLabelsErrorList' => [['ErrorCode' => 3]]])]);

    expect(Shipping::carrier('gls')->cancel('51234567'))->toBeTrue()
        ->and(Shipping::carrier('gls')->cancel('51234567'))->toBeFalse();
    Http::assertSent(fn (Request $request): bool => $request['ParcelIdList'] === [9001, 9002]);
});

it('prints labels again for the stored parcels', function (LabelFormat $format, string $printer): void {
    Http::fake([GLS.'GetPrintedLabels' => Http::response(fixtureFile('get-printed-labels.json'))]);

    expect(Shipping::carrier('gls')->label('51234567', $format))->toStartWith('%PDF');
    Http::assertSent(fn (Request $request): bool => $request['TypeOfPrinter'] === $printer);
})->with([[LabelFormat::A6Pdf, 'Thermo'], [LabelFormat::A4Pdf, 'A4_2x2']]);

it('handles label errors and empty labels', function (): void {
    Http::fake([GLS.'GetPrintedLabels' => Http::sequence()
        ->push(['GetPrintedLabelsErrorList' => [['ErrorCode' => 7, 'ErrorDescription' => 'Deleted']]])
        ->push(['Labels' => null])]);

    expect(fn () => Shipping::carrier('gls')->label('51234567'))->toThrow(GlsException::class, 'Deleted')
        ->and(Shipping::carrier('gls')->label('51234567'))->toBe('');
});

it('needs the ParcelId stored with the shipment', function (): void {
    Shipping::carrier('gls')->cancel('999');
})->throws(GlsException::class, 'Shipment::recordFrom');
