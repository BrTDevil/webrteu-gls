<?php

declare(strict_types=1);

use WeBRTeu\Gls\GlsStatusMap;
use WeBRTeu\Shipping\Enums\ShipmentStatus;

it('maps GLS status codes, with or without leading zeros', function (string $code, ShipmentStatus $expected): void {
    expect(GlsStatusMap::resolve($code, ''))->toBe($expected);
})->with([
    ['01', ShipmentStatus::PickedUp],
    ['5', ShipmentStatus::Delivered],
    ['05', ShipmentStatus::Delivered],
    ['54', ShipmentStatus::AtPickupPoint],
]);

it('falls back to the label for unmapped codes', function (): void {
    expect(GlsStatusMap::resolve('00', 'Coletul a fost livrat'))->toBe(ShipmentStatus::Delivered)
        ->and(GlsStatusMap::resolve('99', 'Retur'))->toBe(ShipmentStatus::Returning);
});
