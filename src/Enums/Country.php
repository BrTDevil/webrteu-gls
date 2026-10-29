<?php

declare(strict_types=1);

namespace WeBRTeu\Gls\Enums;

/**
 * Țările MyGLS, cu host-urile de test și producție.
 */
enum Country: string
{
    case Romania = 'RO';
    case Hungary = 'HU';
    case Croatia = 'HR';
    case Czechia = 'CZ';
    case Slovenia = 'SI';
    case Slovakia = 'SK';
    case Serbia = 'RS';

    public function host(bool $sandbox): string
    {
        $tld = strtolower($this->value);

        return $sandbox ? "https://api.test.mygls.{$tld}" : "https://api.mygls.{$tld}";
    }
}
