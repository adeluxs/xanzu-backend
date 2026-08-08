<?php

return [
    'base_url' => env('RAYPLUSMONEY_BASE_URL', 'https://app.rayplusmoney.com/pay/v01'),
    'api_key' => env('RAYPLUSMONEY_API_KEY'),
    'api_token' => env('RAYPLUSMONEY_API_TOKEN'),
    'payout_network' => env('RAYPLUSMONEY_PAYOUT_NETWORK'),
    'connect_timeout' => (int) env('RAYPLUSMONEY_CONNECT_TIMEOUT', 8),
    'timeout' => (int) env('RAYPLUSMONEY_TIMEOUT', 20),
];
