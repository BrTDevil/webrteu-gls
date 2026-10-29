<?php

declare(strict_types=1);

namespace WeBRTeu\Gls\Support;

use Carbon\CarbonImmutable;
use InvalidArgumentException;

/**
 * Datele JSON .NET: „/Date(1700000000000)/” sau „/Date(1700000000000+0200)/”.
 */
final class DotNetDate
{
    public static function parse(string $value): CarbonImmutable
    {
        if (preg_match('#^/Date\((-?\d+)([+-]\d{4})?\)/$#', $value, $matches) !== 1) {
            throw new InvalidArgumentException(sprintf('[%s] nu este o dată .NET JSON.', $value));
        }

        $date = CarbonImmutable::createFromTimestampMs((int) $matches[1], 'UTC');

        if (isset($matches[2])) {
            $offset = substr($matches[2], 0, 3).':'.substr($matches[2], 3);

            return $date->setTimezone($offset);
        }

        return $date;
    }

    public static function tryParse(mixed $value): ?CarbonImmutable
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        try {
            return self::parse($value);
        } catch (InvalidArgumentException) {
            return CarbonImmutable::parse($value);
        }
    }

    public static function format(CarbonImmutable $date): string
    {
        return '/Date('.$date->getTimestampMs().')/';
    }
}
