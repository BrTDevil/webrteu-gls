<?php

declare(strict_types=1);

use WeBRTeu\Gls\Support\PasswordHash;

it('turns the password into the 64 bytes of its SHA-512 hash', function (): void {
    $bytes = PasswordHash::bytes('parola');

    expect($bytes)->toHaveCount(64)
        ->and(implode('', array_map(chr(...), $bytes)))->toBe(hash('sha512', 'parola', true))
        ->and(array_slice(PasswordHash::bytes('abc'), 0, 4))->toBe([0xDD, 0xAF, 0x35, 0xA1]);
});
