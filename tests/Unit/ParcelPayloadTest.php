<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use WeBRTeu\Gls\Requests\ParcelPayload;
use WeBRTeu\Shipping\Data\AddressData;
use WeBRTeu\Shipping\Exceptions\ShipmentValidationException;

function parcel(array $overrides = [], bool $sms = true, bool $email = true): array
{
    return (new ParcelPayload(100000001, ['name' => 'Magazin SRL', 'zip' => '500001'], $sms, $email))
        ->make(shipmentData($overrides), CarbonImmutable::createFromTimestampMs(1700000000000));
}

it('sends cash on delivery with amount, reference and the COD service', function (): void {
    $parcel = parcel();

    expect($parcel)->toMatchArray([
        'ClientNumber' => 100000001,
        'ClientReference' => 'CMD-1024',
        'Count' => 2,
        'CODAmount' => 149.7,
        'CODReference' => 'CMD-1024',
        'Content' => 'Haine',
        'PickupDate' => '/Date(1700000000000)/',
    ])->and($parcel['ServiceList'])->toBe([
        ['Code' => 'COD'],
        ['Code' => 'SM2', 'SM2Parameter' => ['Value' => '0721000000']],
        ['Code' => 'FDS', 'FDSParameter' => ['Value' => 'ion@example.com']],
    ])->and($parcel['DeliveryAddress'])->toMatchArray([
        'ZipCode' => '505400',
        'HouseNumberInfo' => 'bl. A ap. 3',
        'ContactPhone' => '0721000000',
    ])->and($parcel['PickupAddress'])->toMatchArray(['Name' => 'Magazin SRL', 'ZipCode' => '500001', 'CountryIsoCode' => 'RO']);
});

it('adds PSD with the parcel shop id and skips disabled notifications', function (): void {
    $parcel = parcel(['pickupPointId' => 'RO1234-PSD', 'codAmount' => 0], sms: false, email: false);

    expect($parcel['ServiceList'])->toBe([['Code' => 'PSD', 'PSDParameter' => ['StringValue' => 'RO1234-PSD']]])
        ->and($parcel['CODReference'])->toBeNull();
});

it('uses a custom sender and the observations as content', function (): void {
    $sender = new AddressData('Ana', '0730000000', 'Cluj', 'Cluj-Napoca', 'Str. X', '2', postalCode: '400001', company: 'Alt SRL');
    $parcel = parcel(['sender' => $sender, 'contents' => '', 'observations' => 'Fragil']);

    expect($parcel['PickupAddress'])->toMatchArray(['Name' => 'Alt SRL', 'ContactName' => 'Ana', 'City' => 'Cluj-Napoca'])
        ->and($parcel['Content'])->toBe('Fragil');
});

it('requires the postal code', function (): void {
    $recipient = new AddressData('Ion', '0721000000', 'Brașov', 'Brașov', 'Str. X', '1');

    parcel(['recipient' => $recipient]);
})->throws(ShipmentValidationException::class, 'codul poștal');
