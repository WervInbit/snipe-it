<?php

declare(strict_types=1);

use App\Models\Group;
use App\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

require '/var/www/html/vendor/autoload.php';
$app = require '/var/www/html/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
if (config('app.env') !== 'local' || parse_url((string) config('app.url'), PHP_URL_HOST) !== 'dev.inbit') {
    throw new RuntimeException('Controlled local dev.inbit evidence only.');
}
$password = getenv('GUIDE_CAPTURE_PASSWORD');
if (!$password) {
    throw new RuntimeException('Pass the controlled capture password at the command boundary.');
}
$marker = 'Manuals remaining guides 2026-10-20: synthetic capture account; no real person.';
$result = DB::transaction(function () use ($password, $marker): array {
    $actors = [];
    foreach (['admin' => 'Admin', 'superadmin' => 'Admin', 'senior' => 'Senior Refurbisher', 'user' => 'Refurbisher'] as $suffix => $groupName) {
        $username = 'guide_20261020_' . $suffix;
        $user = User::where('username', $username)->first();
        if ($user && $user->notes !== $marker) {
            throw new RuntimeException('Capture identity belongs to unrelated data.');
        }
        if (!$user) {
            $user = new User();
            $user->first_name = 'Handleiding';
            $user->last_name = ucfirst($suffix);
            $user->username = $username;
            $user->email = $username . '@example.test';
            $user->password = bcrypt($password);
            $user->activated = true;
            $user->ldap_import = false;
            $user->locale = 'nl-NL';
            $user->notes = $marker;
            $user->permissions = $suffix === 'superadmin' ? json_encode(['superuser' => '1']) : null;
            $user->saveOrFail();
            $user->groups()->sync([Group::where('name', $groupName)->firstOrFail()->id]);
        }
        $actors[$suffix] = ['id' => $user->id, 'username' => $username, 'locale' => $user->locale,
            'groups' => $user->groups->pluck('name'), 'superuser' => $user->isSuperUser()];
    }
    $name = 'Catalogus lezen - handleiding 20261020';
    $group = Group::where('name', $name)->first();
    if ($group && $group->notes !== $marker) {
        throw new RuntimeException('Group belongs to unrelated data.');
    }
    if (!$group) {
        $group = new Group();
        $group->name = $name;
        $group->notes = $marker;
        $group->permissions = json_encode(['models.view' => 1, 'models.create' => 0, 'models.edit' => 0,
            'models.delete' => 0, 'models.manage_specification_cleanup' => 0, 'admin' => 0, 'superuser' => 0]);
        $group->saveOrFail();
    }
    return ['environment' => config('app.env'), 'globalLocale' => App\Models\Setting::getSettings()->locale,
        'marker' => $marker, 'actors' => $actors, 'group' => ['id' => $group->id, 'name' => $group->name,
        'members' => $group->users()->count()]];
});
echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
