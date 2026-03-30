<?php

return [
    'countries' => [
        'FR' => 'France',
        'BE' => 'Belgique',
        'ES' => 'Espagne',
        'IT' => 'Italie',
        'AT' => 'Autriche',
        'HR' => 'Croatie',
        'DK' => 'Danemark',
        'FI' => 'Finlande',
        'GR' => 'Grèce',
        'IE' => 'Irlande',
        'LU' => 'Luxembourg',
        'NL' => 'Pays-Bas',
        'PT' => 'Portugal',
        'SE' => 'Suède',
        'GB' => 'Royaume-Uni',
        'CH' => 'Suisse',
        'GP' => 'Guadeloupe',
        'MQ' => 'Martinique',
        'GF' => 'Guyane',
        'RE' => 'La Réunion',
        'YT' => 'Mayotte',
        'PF' => 'Polynésie française',
    ],

    'zones' => [
        'FR' => [
            'countries' => ['FR'],
            'methods' => ['colissimo', 'boxtal', 'express'],
            'free_shipping_threshold' => null,
        ],
        'BE_ES_IT' => [
            'countries' => ['BE', 'ES', 'IT'],
            'methods' => ['boxtal_intl', 'flat_rate_eu'],
        ],
        'EU' => [
            'countries' => ['AT', 'HR', 'DK', 'FI', 'GR', 'IE', 'LU', 'NL', 'PT', 'SE'],
            'methods' => ['flat_rate_eu'],
        ],
        'UK_CH' => [
            'countries' => ['GB', 'CH'],
            'methods' => ['colissimo_intl'],
        ],
        'DOM_TOM' => [
            'countries' => ['GP', 'MQ', 'GF', 'RE', 'YT', 'PF'],
            'methods' => ['flat_rate_domtom'],
        ],
    ],

    'methods' => [
        'colissimo' => [
            'label' => 'Livraison à domicile (Colissimo)',
            'price' => 7.90,
        ],
        'boxtal' => [
            'label' => 'Livraison en point relais',
            'price' => 5.00,
            'free_above_threshold' => true,
        ],
        'express' => [
            'label' => 'Express (expédition sous 1 semaine)',
            'price' => 9.90,
        ],
        'boxtal_intl' => [
            'label' => 'Point relais international',
            'price' => 6.90,
        ],
        'flat_rate_eu' => [
            'label' => 'Livraison Union Européenne',
            'price' => 12.90,
        ],
        'colissimo_intl' => [
            'label' => 'Colissimo international',
            'price' => 14.90,
        ],
        'flat_rate_domtom' => [
            'label' => 'Livraison DOM-TOM',
            'price' => 15.90,
        ],
    ],

    'boxtal' => [
        'access_key' => env('BOXTAL_ACCESS_KEY'),
        'secret_key' => env('BOXTAL_SECRET_KEY'),
        'connect_access_key' => env('BOXTAL_CONNECT_ACCESS_KEY'),
        'token_url' => 'https://api.boxtal.com/v2/token/maps',
        'bootstrap_url' => 'https://maps.boxtal.com/styles/boxtal/style.json?access_token=${access_token}',
        'networks' => ['CHRP_NETWORK'],

        // API v3
        'v3_access_key' => env('BOXTAL_V3_ACCESS_KEY'),
        'v3_secret_key' => env('BOXTAL_V3_SECRET_KEY'),
        'v3_base_url' => env('BOXTAL_V3_BASE_URL', 'https://api.boxtal.com'),
        'v3_webhook_secret' => env('BOXTAL_V3_WEBHOOK_SECRET'),

        // Adresse expéditeur
        'from_address' => [
            'company' => env('BOXTAL_FROM_COMPANY', "Atelier d'Aubin"),
            'firstName' => env('BOXTAL_FROM_FIRSTNAME', 'Arnaud'),
            'lastName' => env('BOXTAL_FROM_LASTNAME', 'THOMAS'),
            'email' => env('BOXTAL_FROM_EMAIL', 'contact@atelier-aubin.fr'),
            'phone' => env('BOXTAL_FROM_PHONE', ''),
            'street' => env('BOXTAL_FROM_STREET', ''),
            'city' => env('BOXTAL_FROM_CITY', ''),
            'postalCode' => env('BOXTAL_FROM_POSTAL_CODE', ''),
            'country' => 'FR',
        ],

        'default_package' => [
            'weight' => 0.3,
            'length' => 30,
            'width' => 20,
            'height' => 5,
        ],

        'content_category_id' => env('BOXTAL_CONTENT_CATEGORY', 'content:v1:30100'),

        'shipping_offer_codes' => [
            'MONR_NETWORK' => env('BOXTAL_OFFER_MONR', 'MONR-CpourToi'),
            'CHRP_NETWORK' => env('BOXTAL_OFFER_CHRP', 'CHRP-Chrono2ShopDirect'),
            'colissimo' => env('BOXTAL_OFFER_COLISSIMO', 'POFR-ColissimoAccess'),
        ],
    ],
];
