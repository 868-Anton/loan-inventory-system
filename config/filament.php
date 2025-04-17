<?php

return [
  /*
    |--------------------------------------------------------------------------
    | Broadcasting
    |--------------------------------------------------------------------------
    |
    | By uncommenting the Laravel Echo configuration, you may connect Filament
    | to any Pusher-compatible websockets server.
    |
    | This will allow your client-side to receive real-time notifications.
    |
    */

  'broadcasting' => [
    // 'echo' => [
    //     'broadcaster' => 'pusher',
    //     'key' => env('VITE_PUSHER_APP_KEY'),
    //     'cluster' => env('VITE_PUSHER_APP_CLUSTER'),
    //     'wsHost' => env('VITE_PUSHER_HOST'),
    //     'wsPort' => env('VITE_PUSHER_PORT'),
    //     'wssPort' => env('VITE_PUSHER_PORT'),
    //     'authEndpoint' => '/api/broadcasting/auth',
    //     'forceTLS' => true,
    //     'disableStats' => true,
    // ],
  ],

  /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | This is the storage disk Filament will use to store files.
    |
    */

  'default_filesystem_disk' => env('FILAMENT_FILESYSTEM_DISK', 'public'),

  /*
    |--------------------------------------------------------------------------
    | Assets Path
    |--------------------------------------------------------------------------
    |
    | This is the directory where Filament's assets will be published to. It
    | is relative to the `public` directory of your Laravel application.
    |
    */

  'assets_path' => '',

  /*
    |--------------------------------------------------------------------------
    | Cache Path
    |--------------------------------------------------------------------------
    |
    | This is the directory where Filament's cache files will be stored. It
    | is relative to the storage directory of your Laravel application.
    |
    */

  'cache_path' => 'filament/cache',

  /*
    |--------------------------------------------------------------------------
    | Model Wizard
    |--------------------------------------------------------------------------
    |
    | Specify default model wizard configuration
    |
    */

  'model_wizard' => [
    'close_previous_steps' => true,
  ],
];
