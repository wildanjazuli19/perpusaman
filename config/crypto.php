<?php

return [
    'node_binary' => env('NODE_BINARY', 'node'),
    'key_path' => env('MLKEM_KEY_PATH', storage_path('app/private/keys/ml-kem-768.json')),
];
