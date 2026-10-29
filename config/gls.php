<?php

declare(strict_types=1);

return [

    /*
    | RO | HU | HR | CZ | SI | SK | RS (aceeași platformă MyGLS).
    */
    'country' => env('GLS_COUNTRY', 'RO'),

    /*
    | true = api.test.mygls.<țară>, cu credențiale de test de la GLS.
    */
    'sandbox' => (bool) env('GLS_SANDBOX', true),

    /*
    | Email-ul MyGLS și parola în clar (pachetul trimite hash-ul SHA-512 ca array de octeți).
    */
    'username' => env('GLS_USERNAME'),
    'password' => env('GLS_PASSWORD'),
    'client_number' => (int) env('GLS_CLIENT_NUMBER'),

    /*
    | Tipul implicit de imprimantă MyGLS: A4_2x2 | A4_4x1 | Connect | Thermo | ThermoZPL.
    */
    'printer_type' => env('GLS_PRINTER_TYPE', 'Thermo'),

    'pickup_address' => [
        'name' => env('GLS_PICKUP_NAME'),
        'street' => env('GLS_PICKUP_STREET'),
        'house_number' => env('GLS_PICKUP_NUMBER'),
        'city' => env('GLS_PICKUP_CITY'),
        'zip' => env('GLS_PICKUP_ZIP'),
        'country' => env('GLS_COUNTRY', 'RO'),
        'contact_phone' => env('GLS_PICKUP_PHONE'),
        'contact_email' => env('GLS_PICKUP_EMAIL'),
    ],

    /*
    | Notificări pentru destinatar: SM2 (SMS) și FDS (email), trimise când există telefon / email.
    */
    'notifications' => [
        'sms' => (bool) env('GLS_SMS_NOTIFICATION', true),
        'email' => (bool) env('GLS_EMAIL_NOTIFICATION', true),
    ],

    'map_widget' => 'https://map.gls-romania.ro/widget/gls-dpm.js',

    'timeout' => (int) env('GLS_TIMEOUT', 20),

];
