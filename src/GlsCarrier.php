<?php

declare(strict_types=1);

namespace WeBRTeu\Gls;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use WeBRTeu\Gls\Enums\PrinterType;
use WeBRTeu\Gls\Exceptions\GlsException;
use WeBRTeu\Gls\Requests\ParcelPayload;
use WeBRTeu\Gls\Support\DotNetDate;
use WeBRTeu\Shipping\Contracts\Carrier;
use WeBRTeu\Shipping\Data\AwbData;
use WeBRTeu\Shipping\Data\QuoteData;
use WeBRTeu\Shipping\Data\ServiceData;
use WeBRTeu\Shipping\Data\ShipmentData;
use WeBRTeu\Shipping\Data\TrackingData;
use WeBRTeu\Shipping\Data\TrackingEventData;
use WeBRTeu\Shipping\Enums\LabelFormat;
use WeBRTeu\Shipping\Exceptions\ShipmentStateUnknownException;
use WeBRTeu\Shipping\Exceptions\ShipmentValidationException;
use WeBRTeu\Shipping\Exceptions\UnsupportedOperationException;
use WeBRTeu\Shipping\Models\Shipment;

/**
 * AWB-ul = numărul coletului GLS (cel de tracking). `ParcelId` (intern MyGLS, cerut la etichetă și ștergere)
 * se păstrează în AwbData::raw și se citește din tabela `shipments`.
 */
class GlsCarrier implements Carrier
{
    public function __construct(private readonly GlsClient $client) {}

    public function name(): string
    {
        return 'gls';
    }

    public function services(): Collection
    {
        return collect([
            new ServiceData('standard', 'GLS Business Parcel'),
            new ServiceData('PSD', 'GLS Parcel Shop / Locker', pickupPoint: true),
        ]);
    }

    /**
     * MyGLS nu are calcul de tarif.
     */
    public function quote(ShipmentData $shipment): QuoteData
    {
        throw UnsupportedOperationException::for($this->name(), 'quote (MyGLS nu are calcul de tarif)');
    }

    public function createShipment(ShipmentData $shipment): AwbData
    {
        $parcel = $this->payload()->make($shipment);

        try {
            $json = $this->client->call('PrintLabels', [
                'ParcelList' => [$parcel],
                'TypeOfPrinter' => $this->printerType()->value,
                'PrintPosition' => 1,
                'ShowPrintDialog' => false,
            ]);
        } catch (ConnectionException $exception) {
            throw new ShipmentStateUnknownException($this->name(), $shipment->reference, $exception);
        }

        $errors = GlsClient::errors($json);
        $infos = array_values(array_filter(is_array($json['PrintLabelsInfoList'] ?? null) ? $json['PrintLabelsInfoList'] : [], is_array(...)));

        if ($errors !== [] || $infos === []) {
            throw ShipmentValidationException::withErrors($this->name(), $this->errorMessages($errors));
        }

        $numbers = array_map(fn (array $info): string => $this->string($info, 'ParcelNumber'), $infos);

        return new AwbData(
            awb: $numbers[0],
            carrier: $this->name(),
            parcelNumbers: $numbers,
            raw: [
                'ParcelId' => (int) $this->string($infos[0], 'ParcelId'),
                'ParcelIdList' => array_map(fn (array $info): int => (int) $this->string($info, 'ParcelId'), $infos),
                'Labels' => base64_encode($this->labels($json)),
            ],
        );
    }

    public function label(string $awb, LabelFormat $format = LabelFormat::A6Pdf): string
    {
        $json = $this->client->call('GetPrintedLabels', [
            'ParcelIdList' => $this->parcelIds($awb),
            'TypeOfPrinter' => PrinterType::forFormat($format)->value,
            'PrintPosition' => 1,
            'ShowPrintDialog' => false,
        ]);

        if (($errors = GlsClient::errors($json)) !== []) {
            throw new GlsException('MyGLS GetPrintedLabels: '.implode('; ', $this->errorMessages($errors)['general']));
        }

        return $this->labels($json);
    }

    public function track(string $awb): TrackingData
    {
        $json = $this->client->call('GetParcelStatuses', [
            'ParcelNumber' => (int) $awb,
            'ReturnPOD' => false,
            'LanguageIsoCode' => strtoupper(Config::string('gls.country', 'RO')),
        ]);

        if (($errors = GlsClient::errors($json)) !== []) {
            throw new GlsException('MyGLS GetParcelStatuses: '.implode('; ', $this->errorMessages($errors)['general']));
        }

        $events = array_map(function (array $status): TrackingEventData {
            $code = $this->string($status, 'StatusCode');
            $label = $this->string($status, 'StatusDescription');

            return new TrackingEventData(
                status: GlsStatusMap::resolve($code, $label),
                code: $code,
                label: $label,
                occurredAt: DotNetDate::tryParse($status['StatusDate'] ?? null),
                location: $this->string($status, 'DepotCity') !== '' ? $this->string($status, 'DepotCity') : null,
                raw: $status,
            );
        }, array_values(array_filter(is_array($json['ParcelStatusList'] ?? null) ? $json['ParcelStatusList'] : [], is_array(...))));

        return TrackingData::fromEvents($awb, $events, $json);
    }

    public function cancel(string $awb): bool
    {
        $json = $this->client->call('DeleteLabels', ['ParcelIdList' => $this->parcelIds($awb)]);

        return GlsClient::errors($json) === [] && ! empty($json['SuccessfullyDeletedList']);
    }

    /**
     * @return list<int>
     */
    private function parcelIds(string $awb): array
    {
        $shipment = Shipment::query()->where('carrier', $this->name())->where('awb', $awb)->first();
        $ids = $shipment instanceof Shipment ? ($shipment->raw['ParcelIdList'] ?? null) : null;

        if (! is_array($ids) || $ids === []) {
            throw new GlsException(sprintf('Nu găsesc ParcelId pentru coletul GLS [%s]: salvează expedierea cu Shipment::recordFrom().', $awb));
        }

        return array_values(array_map(intval(...), array_filter($ids, is_numeric(...))));
    }

    private function payload(): ParcelPayload
    {
        /** @var array{name?: string|null, street?: string|null, house_number?: string|null, city?: string|null, zip?: string|null, country?: string|null, contact_phone?: string|null, contact_email?: string|null} $pickup */
        $pickup = Config::array('gls.pickup_address', []);

        return new ParcelPayload(
            $this->client->clientNumber(),
            $pickup,
            Config::boolean('gls.notifications.sms', true),
            Config::boolean('gls.notifications.email', true),
        );
    }

    private function printerType(): PrinterType
    {
        return PrinterType::tryFrom(Config::string('gls.printer_type', 'Thermo')) ?? PrinterType::Thermo;
    }

    /**
     * `Labels` vine ca array de octeți (byte[] .NET) sau ca șir base64.
     *
     * @param  array<array-key, mixed>  $json
     */
    private function labels(array $json): string
    {
        $labels = $json['Labels'] ?? null;

        if (is_array($labels)) {
            return implode('', array_map(fn (mixed $byte): string => chr(is_numeric($byte) ? ((int) $byte) & 0xFF : 0), $labels));
        }

        return is_string($labels) ? (string) base64_decode($labels, true) : '';
    }

    /**
     * @param  list<array<array-key, mixed>>  $errors
     * @return array{general: list<string>}
     */
    private function errorMessages(array $errors): array
    {
        $messages = array_map(fn (array $error): string => trim($this->string($error, 'ErrorCode').' '.$this->string($error, 'ErrorDescription')), $errors);

        return ['general' => $messages !== [] ? $messages : ['MyGLS nu a întors niciun colet.']];
    }

    /**
     * @param  array<array-key, mixed>  $data
     */
    private function string(array $data, string $key): string
    {
        return is_scalar($data[$key] ?? null) ? (string) $data[$key] : '';
    }
}
