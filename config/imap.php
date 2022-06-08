<?php

return [
    'host'  => env('IMAP_HOST', 'localhost'),
    'port'  => env('IMAP_PORT', 993),
    'protocol'  => env('IMAP_PROTOCOL', 'imap'), //might also use imap, [pop3 or nntp (untested)]
    'encryption'    => env('IMAP_ENCRYPTION', 'ssl'), // Supported: false, 'ssl', 'tls', 'notls', 'starttls'
    'validate_cert' => env('IMAP_VALIDATE_CERT', true),
    'username' => env('IMAP_USERNAME', 'root@example.com'),
    'password' => env('IMAP_PASSWORD', ''),
];
