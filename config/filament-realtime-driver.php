<?php

return [
    'server' => env(
        'FILAMENT_REALTIME_SERVER',
        'ws-' . env('PUSHER_APP_CLUSTER', 'mt1') . '.pusher.com'
    ),

    'channel' => env(
        'FILAMENT_REALTIME_CHANNEL',
        'filament-realtime-driver'
    ),

    'key' => env('PUSHER_APP_KEY'),

    'secure' => (bool) env(
        'FILAMENT_REALTIME_SECURE',
        true
    ),
];