<?php

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\QrCodeService;

$svc = new QrCodeService();
$tpl = 'dymo-s0929120-25x25';
$caption = [
    'top' => [],
    'bottom' => [
        'ASSET-0001',
        'SN: 5CD048PBP4',
    ],
];

$pdf = $svc->pdf('ASSET-0001', null, null, $tpl, $caption);
$png = $svc->png('ASSET-0001', null, null, $tpl);

file_put_contents(__DIR__.'/../sample-asset-serial-25x25.pdf', $pdf);
file_put_contents(__DIR__.'/../sample-asset-serial-25x25.png', $png);

echo "Generated sample-asset-serial-25x25.[pdf|png]\n";
