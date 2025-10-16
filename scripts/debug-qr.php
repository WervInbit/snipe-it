<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use App\Models\Asset;
use App\Services\QrLabelService;
$assetId = $argv[1] ?? null;
if (!$assetId) {
    fwrite(STDERR, "Usage: php scripts/debug-qr.php <asset_id>\n");
    exit(1);
}
$asset = Asset::find($assetId);
if (!$asset) {
    fwrite(STDERR, "Asset not found\n");
    exit(1);
}
$service = app(QrLabelService::class);
$pngUrl = $service->url($asset, 'png');
$exists = Storage::disk('public')->exists(str_replace(Storage::disk('public')->url(''), '', $pngUrl));
var_dump(['url' => $pngUrl, 'exists' => $exists]);
