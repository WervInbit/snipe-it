<?php
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\\Contracts\\Console\\Kernel::class);
$kernel->bootstrap();
$user = App\\Models\\User::where('username','admin')->first();
$token = $user->createToken('dev-token')->accessToken;
echo $token, \n;
