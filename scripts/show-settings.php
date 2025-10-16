<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use App\Models\Setting;
$settings = Setting::first(['qr_code','qr_formats']);
echo json_encode($settings ? $settings->toArray() : []);
