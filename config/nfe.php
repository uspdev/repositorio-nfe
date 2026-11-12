<?php

return [
    'nfeWs' => [
        'server' => env('NFE_WS_SERVER'),
        'apiKey' => env('NFE_WS_API_KEY') ?: env('NFE_WS_KEY') ?: env('NFE_WS_PWD') ?: env('NFE_WS_USER'),
        'debug' => (int) env('NFE_WS_DEBUG', 0),
        'sslVerify' => (bool) env('NFE_WS_SSL_VERIFY', true),
    ],
];
