<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Servicios de Procesamiento
    |--------------------------------------------------------------------------
    */

    'transcription' => [
        'url' => env('TRANSCRIPTION_SERVICE_URL', 'http://transcription:5000'),
    ],

    'ner' => [
        'url' => env('NER_SERVICE_URL', 'http://ner:5001'),
    ],

    'processing_timeout' => env('PROCESSING_TIMEOUT', 600),

];
