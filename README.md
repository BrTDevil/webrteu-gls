# webrteu/gls

[![PHP](https://img.shields.io/badge/PHP-%5E8.2-777bb4)](https://www.php.net/)
[![Laravel](https://img.shields.io/badge/Laravel-12%E2%80%9313-ff2d20)](https://laravel.com/)
[![Licență MIT](https://img.shields.io/badge/licen%C8%9B%C4%83-MIT-green)](LICENSE.md)
[![Packagist](https://img.shields.io/packagist/v/webrteu/gls)](https://packagist.org/packages/webrteu/gls)

Integrare Laravel pentru GLS prin **MyGLS API** (JSON, aceeași platformă în RO, HU, HR, CZ, SI, SK, RS): colete cu
etichetă într-un pas, tracking, ștergere, livrare la Parcel Shop / Locker (PSD). Implementează contractul `Carrier` din
`webrteu/shipping`, cu client propriu (nu depinde de `webapix/mygls-sdk`).

Nu este un pachet oficial GLS.

## Cerințe

- PHP `^8.2`, Laravel 12–13
- contract GLS + utilizator MyGLS (`https://mygls.ro`); credențialele de test se cer reprezentantului GLS
- `webrteu/shipping`

## Instalare

```bash
composer require webrteu/gls
php artisan vendor:publish --tag=gls-config
```

## Configurare

```dotenv
GLS_COUNTRY=RO
GLS_SANDBOX=true             # api.test.mygls.ro, credențiale de test de la GLS
GLS_USERNAME=                # email MyGLS
GLS_PASSWORD=                # parola în clar; pachetul o transformă în hash SHA-512
GLS_CLIENT_NUMBER=
GLS_PRINTER_TYPE=Thermo      # A4_2x2 | A4_4x1 | Connect | Thermo | ThermoZPL
GLS_PICKUP_NAME=
GLS_PICKUP_STREET=
GLS_PICKUP_NUMBER=
GLS_PICKUP_CITY=
GLS_PICKUP_ZIP=
GLS_PICKUP_PHONE=
GLS_PICKUP_EMAIL=
GLS_SMS_NOTIFICATION=true    # serviciul SM2
GLS_EMAIL_NOTIFICATION=true  # serviciul FDS
```

## Utilizare

```php
$awb = Shipping::carrier('gls')->createShipment($data);   // PrintLabels
Shipment::recordFrom($awb, $data, $order);                 // obligatoriu: păstrează ParcelId pentru etichetă / ștergere

$pdf = base64_decode($awb->raw['Labels']);                 // eticheta vine direct din PrintLabels
```

| Metodă | MyGLS |
|---|---|
| `createShipment($data)` | `PrintLabels`; AWB = numărul coletului; `raw` = `ParcelId`, `ParcelIdList`, `Labels` (PDF base64) |
| `label($awb, $format)` | `GetPrintedLabels` (A4 → `A4_2x2`, A6 → `Thermo`, ZPL → `ThermoZPL`) |
| `track($awb)` | `GetParcelStatuses` |
| `cancel($awb)` | `DeleteLabels` |
| `quote()` | `UnsupportedOperationException` (MyGLS nu are calcul de tarif) |

Atenții: codul poștal e obligatoriu; erorile vin cu HTTP 200 în `PrintLabelsErrorList` → `ShipmentValidationException`;
datele .NET (`/Date(1700000000000+0200)/`) sunt convertite automat. ⚠️ De verificat: dacă MyGLS cere serviciul `COD`
pe lângă `CODAmount` (pachetul le trimite pe amândouă), valorile `TypeOfPrinter` și dacă primești un `ParcelId` per colet.

## Parcel Shop / Locker

MyGLS API nu are listă de parcel shop-uri: punctul se alege din widget-ul oficial **GLS Map**:

```blade
<x-gls::parcel-shop-picker name="gls_psd_id" country="RO" />
```

Id-ul ales se trimite în `ShipmentData::$pickupPointId` → serviciul `PSD`. ⚠️ URL-ul widget-ului și evenimentul `change`
se confirmă cu documentația GLS.

## Statusuri

⚠️ `GlsStatusMap` are codurile de bază (1 preluat, 2–3 tranzit, 4 în livrare, 5 livrat, 54 parcel shop). Restul se deduce
din descriere. Fără webhook → `shipping:sync` (câte un apel per colet).

## Ce nu face pachetul

- nu calculează tarife
- nu listează parcel shop-urile (doar prin widget)
- nu face retry pe `PrintLabels`

## Testare

```bash
composer test
GLS_SANDBOX_USERNAME=... GLS_SANDBOX_PASSWORD=... GLS_SANDBOX_CLIENT_NUMBER=... vendor/bin/pest --group=sandbox
```

Fixture-urile sunt după documentația MyGLS. **De înlocuit cu răspunsuri reale**.

## Changelog

Vezi [CHANGELOG.md](CHANGELOG.md).

## Autor

[WeBRTeu](https://webrteu.eu)

## Licență

MIT. Vezi [LICENSE.md](LICENSE.md).
