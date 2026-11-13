<?php

return [
    'nfeWs' => [
        'server' => env('NFE_WS_SERVER'),
        'apiKey' => env('NFE_WS_API_KEY') ?: env('NFE_WS_KEY') ?: env('NFE_WS_PWD') ?: env('NFE_WS_USER'),
        'debug' => (int) env('NFE_WS_DEBUG', 0),
        'sslVerify' => (bool) env('NFE_WS_SSL_VERIFY', true),
    ],

    'imap' => [
        'host' => env('IMAP_HOST', 'localhost'),
        'port' => env('IMAP_PORT', 993),
        'protocol' => env('IMAP_PROTOCOL', 'imap'), // might also use imap, [pop3 or nntp (untested)]
        'encryption' => env('IMAP_ENCRYPTION', 'ssl'), // Supported: false, 'ssl', 'tls', 'notls', 'starttls'
        'validate_cert' => env('IMAP_VALIDATE_CERT', true),
        'username' => env('IMAP_USERNAME', 'root@example.com'),
        'password' => env('IMAP_PASSWORD', ''),
        'retention_days' => (int) env('IMAP_RETENTION_DAYS', 180),
    ],
];
