<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Contracts\Console\Kernel;

require __DIR__ . '/../../vendor/autoload.php';

$app = require __DIR__ . '/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$state = $argv[1] ?? 'inactive';
$allowedStates = ['inactive', 'deleted', 'restored'];

if (!in_array($state, $allowedStates, true)) {
    fwrite(STDERR, "Usage: php scripts/manuals/prepare-user-account-guide-evidence.php [inactive|deleted|restored]\n");
    exit(2);
}

$username = 'Miladb';
$user = User::withTrashed()->where('username', $username)->first();

if (!$user) {
    $source = User::where('username', 'demo_refurbisher')->firstOrFail();
    $user = $source->replicate();
    $user->first_name = 'Mila';
    $user->last_name = 'de Boer';
    $user->username = $username;
    $user->email = 'mila.deboer@example.test';
    $user->locale = 'nl-NL';
    $user->activated = false;
    $user->ldap_import = false;
    $user->permissions = null;
    $user->remember_token = null;
    $user->created_by = User::where('username', 'admin')->value('id');
    $user->saveOrFail();
    $user->groups()->sync([$source->groups()->firstOrFail()->id]);
}

if ($state === 'deleted') {
    if (!$user->trashed()) {
        $user->delete();
    }
} else {
    if ($user->trashed()) {
        $user->restore();
    }

    $user->activated = false;
    $user->saveOrFail();
}

$user = User::withTrashed()->where('username', $username)->firstOrFail();

echo json_encode([
    'id' => $user->id,
    'name' => $user->first_name . ' ' . $user->last_name,
    'username' => $user->username,
    'activated' => (bool) $user->activated,
    'deleted' => $user->trashed(),
    'state' => $state,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
