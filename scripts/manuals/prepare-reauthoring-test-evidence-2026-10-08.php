<?php

declare(strict_types=1);

use App\Models\Asset;
use App\Models\Statuslabel;
use App\Models\TestResult;
use App\Models\TestRun;
use App\Models\User;
use App\Services\Assets\AssetStatusTransitionGuardService;
use App\Services\WorkflowRunDefinitionService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

require '/var/www/html/vendor/autoload.php';
$app = require '/var/www/html/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

if (config('app.env') !== 'local' || parse_url((string) config('app.url'), PHP_URL_HOST) !== 'dev.inbit') {
    throw new RuntimeException('Evidence preparation requires local dev.inbit.');
}

$tag = 'GUIDE-20261008-01';
$marker = 'Manuals reauthoring test 2026-10-08: synthetic demonstration record; no physical tests claimed.';
$user = User::where('username', 'demo_supervisor')->firstOrFail();
Auth::login($user);

$result = DB::transaction(function () use ($tag, $marker, $user): array {
    $asset = Asset::where('asset_tag', $tag)->first();
    if ($asset && $asset->notes !== $marker) {
        throw new RuntimeException('Evidence tag is owned by unrelated data.');
    }
    $created = false;
    if (!$asset) {
        $modelExample = Asset::findOrFail(1);
        $asset = new Asset();
        $asset->name = 'Handleiding voorbeeld';
        $asset->asset_tag = $tag;
        $asset->serial = 'GUIDE-SN-20261008-01';
        $asset->model_id = $modelExample->model_id;
        $asset->model_number_id = $modelExample->model_number_id;
        $asset->status_id = Statuslabel::where('name', 'QA Hold')->firstOrFail()->id;
        $asset->notes = $marker;
        $asset->created_by = $user->id;
        $asset->withStatusChangeNote('Demonstratie voor de handleiding; geen fysiek apparaat vrijgegeven.');
        if (!$asset->save()) {
            throw new RuntimeException('Evidence asset validation failed: ' . $asset->getErrors()->first());
        }
        $definitions = app(WorkflowRunDefinitionService::class);
        foreach ($asset->blockingSaleReadinessProfiles() as $profile) {
            $definition = $definitions->forProfile($asset, $profile);
            if ($definition['profile_items']->where('is_required', true)->isEmpty()) {
                throw new RuntimeException('No required applicable items in evidence profile.');
            }
            $run = new TestRun([
                'asset_id' => $asset->id,
                'model_number_id' => $asset->model_number_id,
                'workflow_profile_id' => $profile->id,
                'profile_name_snapshot' => $profile->name,
                'profile_slug_snapshot' => $profile->slug,
                'profile_display_order_snapshot' => $profile->display_order,
                'readiness_context_hash' => $definition['readiness_context_hash'],
                'prerequisite_snapshot' => [],
                'user_id' => $user->id,
                'started_at' => now(),
            ]);
            $run->saveOrFail();
            foreach ($definition['profile_items'] as $profileItem) {
                $item = $profileItem->item;
                $attribute = $item->attribute_definition_id
                    ? $definition['resolved_by_definition']->get($item->attribute_definition_id)
                    : null;
                $run->results()->create([
                    'workflow_item_id' => $item->id,
                    'workflow_profile_item_id' => $profileItem->id,
                    'attribute_definition_id' => $item->attribute_definition_id,
                    'expected_value' => $attribute?->value,
                    'expected_raw_value' => $attribute?->rawValue,
                    'status' => TestResult::STATUS_PASS,
                    'note' => 'Demonstratiewaarde voor handleidingscherm; geen fysieke controle.',
                    'is_required' => $profileItem->is_required,
                    'result_label_mode' => $profileItem->result_label_mode,
                    'sort_order' => $profileItem->sort_order,
                ]);
            }
            $run->syncFinishedAtFromResults();
        }
        $created = true;
    }
    $evaluation = app(AssetStatusTransitionGuardService::class)->evaluate(
        $asset->fresh(),
        Statuslabel::where('name', 'Ready for Sale')->firstOrFail()
    );
    if ($evaluation['has_issues']) {
        throw new RuntimeException('Clean release evidence unavailable: ' . json_encode([
            $evaluation['workflow_issues'], $evaluation['component_issues'],
        ]));
    }
    return [
        'environment' => config('app.env'),
        'asset_id' => $asset->id,
        'asset_tag' => $tag,
        'created' => $created,
        'status' => $asset->fresh()->assetstatus->name,
        'clean_release_preview' => true,
        'workflow_run_ids' => TestRun::where('asset_id', $asset->id)->pluck('id')->all(),
        'marker' => $marker,
    ];
});

echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
