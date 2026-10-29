<?php

declare(strict_types=1);

use WeBRTeu\Shipping\Data\AddressData;
use WeBRTeu\Shipping\Data\ParcelData;
use WeBRTeu\Shipping\Data\ShipmentData;

const GLS = 'https://api.test.mygls.ro/ParcelService.svc/json/';

function fixtureFile(string $name): string
{
    return (string) file_get_contents(__DIR__.'/Fixtures/'.$name);
}

/**
 * @param  array<string, mixed>  $overrides
 */
function shipmentData(array $overrides = []): ShipmentData
{
    return new ShipmentData(...$overrides + [
        'recipient' => new AddressData(
            name: 'Ion Popescu',
            phone: '+40 721 000 000',
            county: 'Brașov',
            city: 'Râșnov',
            street: 'Str. Republicii',
            number: '10',
            email: 'ion@example.com',
            block: 'A',
            apartment: '3',
            postalCode: '505400',
        ),
        'parcels' => [new ParcelData(1.2), new ParcelData(0.8)],
        'reference' => 'CMD-1024',
        'codAmount' => 14970,
        'contents' => 'Haine',
    ]);
}
