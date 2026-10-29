<?php

declare(strict_types=1);

namespace WeBRTeu\Gls;

use WeBRTeu\Shipping\Enums\ShipmentStatus;
use WeBRTeu\Shipping\Support\StatusResolver;

/**
 * `StatusCode` din GetParcelStatuses → ShipmentStatus.
 * ⚠️ Doar codurile de bază din documentația publică GLS; restul se completează din trasee reale.
 */
final class GlsStatusMap
{
    /**
     * @var array<array-key, ShipmentStatus>
     */
    public const MAP = [
        '1' => ShipmentStatus::PickedUp,
        '2' => ShipmentStatus::InTransit,
        '3' => ShipmentStatus::InTransit,
        '4' => ShipmentStatus::OutForDelivery,
        '5' => ShipmentStatus::Delivered,
        '54' => ShipmentStatus::AtPickupPoint,
        '58' => ShipmentStatus::Delivered,
    ];

    public static function resolve(string $code, string $label): ShipmentStatus
    {
        return StatusResolver::resolve('gls', ltrim($code, '0') !== '' ? ltrim($code, '0') : $code, $label, self::MAP);
    }
}
