<?php

// Build Pusher options conditionally so hosted Pusher is used by default.
$pusherOptions = [
    'cluster' => env('PUSHER_APP_CLUSTER', 'mt1'),
    'useTLS' => (bool) env('PUSHER_USETLS', true),
];

// Only set host/port/scheme if explicitly provided. Leaving them unset
// allows the SDK to use the hosted Pusher endpoints derived from cluster.
if (env('PUSHER_HOST')) {
    $pusherOptions['host'] = env('PUSHER_HOST');
}
if (env('PUSHER_PORT')) {
    $pusherOptions['port'] = (int) env('PUSHER_PORT');
}
if (env('PUSHER_SCHEME')) {
    $pusherOptions['scheme'] = env('PUSHER_SCHEME');
}

return [
    'default' => env('BROADCAST_CONNECTION', 'log'),

    'connections' => [
        'pusher' => [
            'driver' => 'pusher',
            'key' => env('PUSHER_APP_KEY'),
            'secret' => env('PUSHER_APP_SECRET'),
            'app_id' => env('PUSHER_APP_ID'),
            'options' => $pusherOptions,
        ],

        'log' => [
            'driver' => 'log',
        ],

        'null' => [
            'driver' => 'null',
        ],
    ],
];