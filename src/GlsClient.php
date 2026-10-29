<?php

declare(strict_types=1);

namespace WeBRTeu\Gls;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use WeBRTeu\Gls\Enums\Country;
use WeBRTeu\Gls\Exceptions\GlsException;
use WeBRTeu\Gls\Support\PasswordHash;
use WeBRTeu\Shipping\Support\Redactor;

/**
 * HTTP brut spre MyGLS: fără token, Username + Password (SHA-512 ca octeți) în fiecare corp. Fără retry.
 */
class GlsClient
{
    public function baseUrl(): string
    {
        $country = Country::tryFrom(strtoupper(Config::string('gls.country', 'RO'))) ?? Country::Romania;

        return $country->host(Config::boolean('gls.sandbox', true));
    }

    public function clientNumber(): int
    {
        return Config::integer('gls.client_number', 0);
    }

    /**
     * @param  array<string, mixed>  $body
     * @return array<array-key, mixed>
     */
    public function call(string $method, array $body): array
    {
        $response = Http::acceptJson()
            ->asJson()
            ->timeout(Config::integer('gls.timeout', 20))
            ->post($this->baseUrl().'/ParcelService.svc/json/'.$method, [
                'Username' => Config::string('gls.username', ''),
                'Password' => PasswordHash::bytes(Config::string('gls.password', '')),
            ] + $body);

        $json = $response->json();
        $json = is_array($json) ? $json : [];

        if ($response->failed()) {
            Log::warning('gls.request_failed', ['method' => $method, 'status' => $response->status(), 'request' => $this->redact($body), 'response' => $this->redact($json)]);

            throw new GlsException(sprintf('MyGLS %s a eșuat (HTTP %d).', $method, $response->status()));
        }

        return $json;
    }

    /**
     * Răspunsurile MyGLS pot avea HTTP 200 cu erori în `*ErrorList` / `*Errors`.
     *
     * @param  array<array-key, mixed>  $json
     * @return list<array<array-key, mixed>>
     */
    public static function errors(array $json): array
    {
        $errors = [];

        foreach ($json as $key => $value) {
            if (is_string($key) && (str_ends_with($key, 'ErrorList') || str_ends_with($key, 'Errors')) && is_array($value)) {
                foreach ($value as $error) {
                    if (is_array($error)) {
                        $errors[] = $error;
                    }
                }
            }
        }

        return $errors;
    }

    /**
     * @param  array<array-key, mixed>  $payload
     * @return array<array-key, mixed>
     */
    public function redact(array $payload): array
    {
        return Redactor::redact($payload);
    }
}
