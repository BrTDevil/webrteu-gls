<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use WeBRTeu\Gls\Support\DotNetDate;

it('parses .NET dates with and without a time zone', function (): void {
    $utc = DotNetDate::parse('/Date(1700000000000)/');
    $local = DotNetDate::parse('/Date(1700000000000+0200)/');

    expect($utc->toIso8601String())->toBe('2023-11-14T22:13:20+00:00')
        ->and($local->toIso8601String())->toBe('2023-11-15T00:13:20+02:00')
        ->and($local->equalTo($utc))->toBeTrue()
        ->and(DotNetDate::parse('/Date(-1000)/')->getTimestamp())->toBe(-1);
});

it('serializes back to the .NET format', function (): void {
    expect(DotNetDate::format(CarbonImmutable::createFromTimestampMs(1700000000123)))->toBe('/Date(1700000000123)/');
});

it('rejects other formats and tries ISO dates leniently', function (): void {
    expect(fn () => DotNetDate::parse('2026-01-01'))->toThrow(InvalidArgumentException::class)
        ->and(DotNetDate::tryParse('2026-01-02T08:00:00+02:00')?->toIso8601String())->toBe('2026-01-02T08:00:00+02:00')
        ->and(DotNetDate::tryParse(null))->toBeNull()
        ->and(DotNetDate::tryParse(''))->toBeNull();
});
