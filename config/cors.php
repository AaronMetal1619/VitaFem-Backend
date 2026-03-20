<?php

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie', 'webhook/*'],
    'allowed_methods' => ['*'],
    'allowed_origins' => ['*'
        /*'https://vetpetfront.onrender.com', // Producción
        'http://localhost:5173',
        'https://4169f60d.us1a.app.preset.io',*/           // Desarrollo
    ],
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => true,
];
