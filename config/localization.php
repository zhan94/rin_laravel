<?php

declare(strict_types=1);

return [
    'supported_locales' => ['en', 'de', 'es'],
    'cookie_name' => env('APP_LOCALE_COOKIE', 'app_locale'),
    'query_parameter' => 'lang',
    'manual_files' => [
        'en' => 'admin-manual-en.html',
        'de' => 'admin-manual-de.html',
        'es' => 'admin-manual-es.html',
    ],
];
