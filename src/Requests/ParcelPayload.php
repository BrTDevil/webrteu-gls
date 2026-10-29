<?php

declare(strict_types=1);

namespace WeBRTeu\Gls\Requests;

use Carbon\CarbonImmutable;
use WeBRTeu\Gls\Support\DotNetDate;
use WeBRTeu\Shipping\Data\AddressData;
use WeBRTeu\Shipping\Data\ShipmentData;
use WeBRTeu\Shipping\Exceptions\ShipmentValidationException;
use WeBRTeu\Shipping\Support\Money;
use WeBRTeu\Shipping\Support\Normalizer;

/**
 * ShipmentData → un `Parcel` MyGLS (ServiceList: COD, PSD, SM2, FDS).
 */
final readonly class ParcelPayload
{
    /**
     * @param  array{name?: string|null, street?: string|null, house_number?: string|null, city?: string|null, zip?: string|null, country?: string|null, contact_phone?: string|null, contact_email?: string|null}  $pickupAddress
     */
    public function __construct(
        private int $clientNumber,
        private array $pickupAddress,
        private bool $smsNotification = true,
        private bool $emailNotification = true,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function make(ShipmentData $shipment, ?CarbonImmutable $pickupDate = null): array
    {
        $recipient = $shipment->recipient;

        if ($recipient->postalCode === null || $recipient->postalCode === '') {
            throw new ShipmentValidationException('GLS cere codul poștal al destinatarului.', ['DeliveryAddress.ZipCode' => ['Codul poștal este obligatoriu.']]);
        }

        return [
            'ClientNumber' => $this->clientNumber,
            'ClientReference' => $shipment->reference,
            'Count' => max(1, $shipment->parcelCount() + $shipment->envelopes),
            'CODAmount' => Money::toFloat($shipment->codAmount),
            'CODReference' => $shipment->hasCashOnDelivery() ? $shipment->reference : null,
            'Content' => $shipment->contents !== '' ? $shipment->contents : (string) $shipment->observations,
            'PickupDate' => DotNetDate::format($pickupDate ?? CarbonImmutable::now()),
            'PickupAddress' => $shipment->sender instanceof AddressData ? $this->address($shipment->sender) : $this->configuredPickupAddress(),
            'DeliveryAddress' => $this->address($recipient),
            'ServiceList' => $this->services($shipment),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function services(ShipmentData $shipment): array
    {
        $recipient = $shipment->recipient;
        $services = [];

        if ($shipment->hasCashOnDelivery()) {
            // ⚠️ de confirmat dacă MyGLS cere explicit codul COD sau doar CODAmount.
            $services[] = ['Code' => 'COD'];
        }

        if ($shipment->isPickupPointDelivery()) {
            $services[] = ['Code' => 'PSD', 'PSDParameter' => ['StringValue' => $shipment->pickupPointId]];
        }

        if ($this->smsNotification && $recipient->phone !== '') {
            $services[] = ['Code' => 'SM2', 'SM2Parameter' => ['Value' => Normalizer::phone($recipient->phone)]];
        }

        if ($this->emailNotification && $recipient->email !== null && $recipient->email !== '') {
            $services[] = ['Code' => 'FDS', 'FDSParameter' => ['Value' => $recipient->email]];
        }

        return $services;
    }

    /**
     * @return array<string, string>
     */
    private function address(AddressData $address): array
    {
        return [
            'Name' => $address->company ?? $address->name,
            'Street' => $address->street,
            'HouseNumber' => $address->number,
            'HouseNumberInfo' => implode(' ', array_filter([
                $address->block !== null ? 'bl. '.$address->block : null,
                $address->staircase !== null ? 'sc. '.$address->staircase : null,
                $address->floor !== null ? 'et. '.$address->floor : null,
                $address->apartment !== null ? 'ap. '.$address->apartment : null,
            ])),
            'City' => $address->city,
            'ZipCode' => (string) $address->postalCode,
            'CountryIsoCode' => $address->countryCode,
            'ContactName' => $address->contactPerson ?? $address->name,
            'ContactPhone' => Normalizer::phone($address->phone),
            'ContactEmail' => (string) $address->email,
        ];
    }

    /**
     * @return array<string, string>
     */
    private function configuredPickupAddress(): array
    {
        return [
            'Name' => (string) ($this->pickupAddress['name'] ?? ''),
            'Street' => (string) ($this->pickupAddress['street'] ?? ''),
            'HouseNumber' => (string) ($this->pickupAddress['house_number'] ?? ''),
            'City' => (string) ($this->pickupAddress['city'] ?? ''),
            'ZipCode' => (string) ($this->pickupAddress['zip'] ?? ''),
            'CountryIsoCode' => (string) ($this->pickupAddress['country'] ?? 'RO'),
            'ContactName' => (string) ($this->pickupAddress['name'] ?? ''),
            'ContactPhone' => (string) ($this->pickupAddress['contact_phone'] ?? ''),
            'ContactEmail' => (string) ($this->pickupAddress['contact_email'] ?? ''),
        ];
    }
}
