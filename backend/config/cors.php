<?php

// return [
//     'paths' => ['api/*', 'sanctum/csrf-cookie', 'login', 'logout', 'register', 'utilisateurs',],
//     'allowed_methods' => ['*'],
//     'allowed_origins' => ['http://localhost:4200'],
//     'allowed_origins' => ['*'],
//     'allowed_origins_patterns' => [],
//     'allowed_headers' => ['*'],
//     'exposed_headers' => [],
//     'max_age' => 0,
//     'supports_credentials' => true,
    
// ];

return [
    'paths' => [
        'api/*', 
        'export/*',
        'sanctum/csrf-cookie', 
        'login', 
        'logout', 
        'register', 
        'utilisateurs/*',
        'modifier-identifiants'
    ],
    
    'allowed_methods' => ['*'],
    
    // Pour le développement, vous pouvez utiliser '*'
    // En production, spécifiez les origines exactes
    'allowed_origins' => ['http://localhost:4200'],
    
    'allowed_origins_patterns' => [],
    
    'allowed_headers' => [
        'Content-Type',
        'X-Auth-Token',
        'Authorization',
        'X-Requested-With',
        'Accept',
        'X-Custom-Header'   

    ],
    
    'exposed_headers' => [
        'Authorization',
        'X-Custom-Header'
    ],
    
    'max_age' => 86400, // 24 heures
    
    'supports_credentials' => true,
];

