<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Disposable data seeders
    |--------------------------------------------------------------------------
    |
    | Demo accounts and the disposable asset/scenario seeders can replace
    | matching credentials or runtime data. They remain blocked unless the app
    | is local/testing and the operator explicitly opts in for the process.
    |
    */
    'allow_disposable_data_seeding' => env('SNIPEIT_ALLOW_DISPOSABLE_DATA_SEEDING', false),
];
