<?php

declare(strict_types=1);

use WeBRTeu\Shipping\Facades\Shipping;
use WeBRTeu\Shipping\Models\Shipment;

it('prints and deletes a parcel on api.test.mygls.ro', function (): void {
    $data = shipmentData(['reference' => 'SMOKE-'.time()]);
    $awb = Shipping::carrier('gls')->createShipment($data);
    Shipment::recordFrom($awb, $data);

    expect(Shipping::carrier('gls')->cancel($awb->awb))->toBeTrue();
})->group('sandbox')->skip(! env('GLS_SANDBOX_USERNAME'), 'Setează GLS_SANDBOX_USERNAME / _PASSWORD / _CLIENT_NUMBER.');
