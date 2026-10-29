<?php

declare(strict_types=1);

namespace WeBRTeu\Gls\Enums;

use WeBRTeu\Shipping\Enums\LabelFormat;

/**
 * Valorile `TypeOfPrinter` din MyGLS. ⚠️ lista completă se confirmă în documentația MyGLS.
 */
enum PrinterType: string
{
    case A4x2x2 = 'A4_2x2';
    case A4x4x1 = 'A4_4x1';
    case Connect = 'Connect';
    case Thermo = 'Thermo';
    case ThermoZpl = 'ThermoZPL';

    public static function forFormat(LabelFormat $format): self
    {
        return match ($format) {
            LabelFormat::A4Pdf => self::A4x2x2,
            LabelFormat::A6Pdf => self::Thermo,
            LabelFormat::Zpl => self::ThermoZpl,
        };
    }
}
