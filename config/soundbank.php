<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Soundbank
    |--------------------------------------------------------------------------
    |
    | Limites techniques des envois. La commission, les clés Stripe / PayPal et
    | les informations de facturation se règlent dans l'administration (/admin).
    |
    */

    /*
    | Taille maximale d'un fichier audio original (Ko) et formats acceptés.
    */
    'max_sample_kilobytes' => (int) env('SOUNDBANK_MAX_SAMPLE_KB', 204800),

    'audio_formats' => ['wav', 'aif', 'aiff', 'flac', 'mp3', 'ogg'],

    /*
    | L'aperçu (22 kHz mono, 30 s max.) est généré dans le navigateur de
    | l'artiste : l'original haute qualité n'est jamais exposé publiquement.
    */
    'max_preview_kilobytes' => 16384,

    'preview_seconds' => 30,

    /*
    | Tag sonore mixé dans chaque aperçu : au début, puis toutes les N secondes.
    | Sans fichier, un « bip-bip » est synthétisé. Pour une voix de marque,
    | déposez un court audio dans public/ et indiquez son URL (ex. /audio/tag.mp3).
    */
    'preview_tag_url' => env('SOUNDBANK_PREVIEW_TAG_URL'),

    'preview_tag_interval' => 4,

    'max_files_per_upload' => 50,

    /*
    | Bornes de prix imposées aux artistes (centimes).
    */
    'min_price_cents' => 50,

    'max_price_cents' => 50000,

    'types' => [
        'one_shot' => 'One-shot',
        'loop' => 'Loop',
    ],

    'keys' => [
        'C', 'C#', 'D', 'D#', 'E', 'F', 'F#', 'G', 'G#', 'A', 'A#', 'B',
        'Cm', 'C#m', 'Dm', 'D#m', 'Em', 'Fm', 'F#m', 'Gm', 'G#m', 'Am', 'A#m', 'Bm',
    ],

    'genres' => [
        'Hip-Hop', 'Trap', 'Drill', 'R&B', 'Pop', 'Afro', 'Reggaeton', 'House',
        'Techno', 'Drum & Bass', 'Dubstep', 'Lo-Fi', 'Ambient', 'Cinematic', 'Rock', 'Autre',
    ],

    /*
    | Valeurs par défaut, remplacées par les réglages de l'administration.
    */
    'defaults' => [
        'commission_percent' => '30',
        'payout_min_cents' => '2000',
        'vat_rate' => '0',
        'vat_mention' => 'TVA non applicable, art. 293 B du CGI',
        'invoice_series' => 'F',
        'credit_series' => 'AV',
        'currency' => 'EUR',
        'paypal_mode' => 'sandbox',
    ],

];
