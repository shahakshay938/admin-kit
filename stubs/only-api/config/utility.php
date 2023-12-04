<?php

return [
    'api' => [
        'token_name' => env('SANCTUM_TOKEN_NAME', function () {
            throw new Exception('Sanctum token name is not specified in .env.');
        }),
        'auth_token_name' => env('API_AUTH_TOKEN_NAME', 'Authorization Token'),
    ],
];
