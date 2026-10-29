<?php

declare(strict_types=1);

namespace WeBRTeu\Gls\Facades;

use Illuminate\Support\Facades\Facade;
use WeBRTeu\Gls\GlsCarrier;

/**
 * @method static \WeBRTeu\Shipping\Data\AwbData createShipment(\WeBRTeu\Shipping\Data\ShipmentData $shipment)
 * @method static \WeBRTeu\Shipping\Data\TrackingData track(string $awb)
 * @method static string label(string $awb, \WeBRTeu\Shipping\Enums\LabelFormat $format = \WeBRTeu\Shipping\Enums\LabelFormat::A6Pdf)
 *
 * @see GlsCarrier
 */
class Gls extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return GlsCarrier::class;
    }
}
