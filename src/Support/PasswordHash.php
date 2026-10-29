<?php

declare(strict_types=1);

namespace WeBRTeu\Gls\Support;

/**
 * MyGLS cere parola ca SHA-512 binar, trimis ca array de octeți (0–255).
 */
final class PasswordHash
{
    /**
     * @return list<int>
     */
    public static function bytes(string $password): array
    {
        /** @var array<int, int> $bytes */
        $bytes = (array) unpack('C*', hash('sha512', $password, true));

        return array_values($bytes);
    }
}
