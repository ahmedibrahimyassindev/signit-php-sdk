<?php

return [
    'client_id' => env('SIGN_IT_CLIENT_ID'),
    'client_secret' => env('SIGN_IT_CLIENT_SECRET'),
    'base_url' => env('SIGN_IT_BASE_URL'),
    'client_query_id' => env('SIGN_IT_CLIENT_ID'),
    'timeout' => (int) env('SIGN_IT_TIMEOUT', 30),
];
