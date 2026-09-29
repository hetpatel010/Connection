<?php

$isNativeDesktop = filter_var(env('NATIVEPHP_RUNNING', false), FILTER_VALIDATE_BOOL)
    || env('APP_RUNTIME') === 'desktop';

return [

    /*
    |--------------------------------------------------------------------------
    | Application Runtime
    |--------------------------------------------------------------------------
    |
    | Determines whether the application is running as a web/cloud deployment
    | or as a NativePHP desktop application. Business logic must never branch
    | on this value directly — use infrastructure bindings instead.
    |
    */

    'runtime' => $isNativeDesktop ? 'desktop' : env('APP_RUNTIME', 'web'),

    /*
    |--------------------------------------------------------------------------
    | Default Database Connection by Runtime
    |--------------------------------------------------------------------------
    */

    'database_connection' => $isNativeDesktop ? 'sqlite' : env('DB_CONNECTION', 'sqlite'),

];
