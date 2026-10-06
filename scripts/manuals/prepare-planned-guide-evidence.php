<?php

declare(strict_types=1);

use App\Models\Group;
use App\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\Eloquent\Collection;

require __DIR__ . '/../../vendor/autoload.php';
$app = require __DIR__ . '/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

if (config('app.env') !== 'local' || parse_url((string) config('app.url'), PHP_URL_HOST) !== 'dev.inbit') {
    throw new RuntimeException('Manual fixtures are restricted to the local dev.inbit environment.');
}

$name = 'Catalogus lezen';
$marker = 'Manuals USR-05 v2 evidence: read-only model catalogue; no user assignments.';
$phase = $argv[1] ?? 'inspect';
$group = Group::where('name', $name)->first();

if ($group && $group->notes !== $marker) {
    throw new RuntimeException('An unrelated group uses the evidence name; nothing was changed.');
}
if ($group && $group->users()->exists()) {
    throw new RuntimeException('The evidence group has members; nothing was changed.');
}

if ($phase === 'create' && !$group) {
    $permissions = [];
    foreach ((array) config('permissions') as $area) {
        foreach ((array) $area as $permission) {
            if (is_array($permission) && isset($permission['permission'])) {
                $permissions[$permission['permission']] = 0;
            }
        }
    }
    $permissions['models.view'] = 1;
    $group = new Group(['name' => $name, 'notes' => $marker, 'permissions' => json_encode($permissions)]);
    $group->saveOrFail();
} elseif ($phase === 'remove' && $group) {
    echo json_encode(['environment' => config('app.env'), 'database' => config('database.connections.' . config('database.default') . '.database'), 'remove_group_id' => $group->id, 'members' => 0]) . PHP_EOL;
    $group->delete();
    $group = null;
} elseif (!in_array($phase, ['inspect', 'create', 'remove'], true)) {
    throw new RuntimeException('Use inspect, create, or remove.');
}

$checks = [];
if ($group) {
    $user = new User(['permissions' => []]);
    $user->setRelation('groups', new Collection([$group]));
    $checks['view_granted'] = $user->hasAccess('models.view');
    $checks['create_not_granted'] = !$user->hasAccess('models.create');
    $checks['admin_not_granted'] = !$user->isAdmin();
    $other = new Group(['permissions' => json_encode(['models.create' => 1])]);
    $user->setRelation('groups', new Collection([$group, $other]));
    $checks['other_group_can_grant'] = $user->hasAccess('models.create');
    $user->permissions = ['models.create' => -1];
    $checks['direct_denial_wins'] = !$user->hasAccess('models.create');
    if (in_array(false, $checks, true)) {
        throw new RuntimeException('The documented permission example does not match the resolver.');
    }
}

echo json_encode(['phase' => $phase, 'group_id' => $group?->id, 'members' => 0, 'resolver_checks' => $checks], JSON_PRETTY_PRINT) . PHP_EOL;
