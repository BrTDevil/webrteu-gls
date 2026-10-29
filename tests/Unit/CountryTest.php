<?php

declare(strict_types=1);

use WeBRTeu\Gls\Enums\Country;
use WeBRTeu\Gls\Enums\PrinterType;
use WeBRTeu\Shipping\Enums\LabelFormat;

it('builds the host for every country and environment', function (Country $country, string $tld): void {
    expect($country->host(true))->toBe("https://api.test.mygls.{$tld}")
        ->and($country->host(false))->toBe("https://api.mygls.{$tld}");
})->with([
    [Country::Romania, 'ro'], [Country::Hungary, 'hu'], [Country::Croatia, 'hr'], [Country::Czechia, 'cz'],
    [Country::Slovenia, 'si'], [Country::Slovakia, 'sk'], [Country::Serbia, 'rs'],
]);

it('maps label formats to printer types', function (): void {
    expect(PrinterType::forFormat(LabelFormat::A4Pdf))->toBe(PrinterType::A4x2x2)
        ->and(PrinterType::forFormat(LabelFormat::A6Pdf))->toBe(PrinterType::Thermo)
        ->and(PrinterType::forFormat(LabelFormat::Zpl))->toBe(PrinterType::ThermoZpl);
});
