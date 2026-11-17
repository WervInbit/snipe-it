<?php

declare(strict_types=1);

use App\Models\Asset;
use App\Models\Statuslabel;
use App\Models\TestResult;
use App\Models\TestRun;
use App\Models\User;
use App\Services\WorkflowRunDefinitionService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

require '/var/www/html/vendor/autoload.php';
$app = require '/var/www/html/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
if (config('app.env') !== 'local' || parse_url((string) config('app.url'), PHP_URL_HOST) !== 'dev.inbit') {
    throw new RuntimeException('Controlled local dev.inbit evidence only.');
}
$tag = 'GUIDE-20261020-01';
$marker = 'Manuals operational guides 2026-10-20: synthetic demonstration; no physical tests claimed.';
$user = User::where('username', 'demo_supervisor')->firstOrFail();
Auth::login($user);
$result = DB::transaction(function () use ($tag, $marker, $user): array {
    $asset = Asset::where('asset_tag', $tag)->first();
    if ($asset && $asset->notes !== $marker) {
        throw new RuntimeException('Tag belongs to unrelated data.');
    }
    $created = false;
    if (!$asset) {
        $example = Asset::findOrFail(1);
        $asset = new Asset();
        $asset->name = 'Handleiding voorbeeld';
        $asset->asset_tag = $tag;
        $asset->serial = 'GUIDE-SN-20261020-01';
        $asset->model_id = $example->model_id;
        $asset->model_number_id = $example->model_number_id;
        $asset->status_id = Statuslabel::where('name', 'Being Processed')->firstOrFail()->id;
        $asset->notes = $marker;
        $asset->created_by = $user->id;
        $asset->withStatusChangeNote('Handleidingdemonstratie; geen fysieke werkzaamheden.');
        if (!$asset->save()) {
            throw new RuntimeException('Evidence validation failed: ' . $asset->getErrors()->first());
        }
        $created = true;
    }
    $finished = getenv('GUIDE_EVIDENCE_FINISH') === '1';
    if ($finished) {
        $definitions = app(WorkflowRunDefinitionService::class);
        foreach ($asset->blockingSaleReadinessProfiles() as $profile) {
            $definition = $definitions->forProfile($asset, $profile);
            $run = TestRun::where('asset_id', $asset->id)->where('workflow_profile_id', $profile->id)->latest('id')->first();
            if (!$run) {
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
                    $attribute = $item->attribute_definition_id ? $definition['resolved_by_definition']->get($item->attribute_definition_id) : null;
                    $run->results()->create([
                        'workflow_item_id' => $item->id,
                        'workflow_profile_item_id' => $profileItem->id,
                        'attribute_definition_id' => $item->attribute_definition_id,
                        'expected_value' => $attribute?->value,
                        'expected_raw_value' => $attribute?->rawValue,
                        'status' => TestResult::STATUS_PASS,
                        'note' => 'Demonstratiewaarde; geen fysieke controle.',
                        'is_required' => $profileItem->is_required,
                        'result_label_mode' => $profileItem->result_label_mode,
                        'sort_order' => $profileItem->sort_order,
                    ]);
                }
            } else {
                foreach ($run->results as $item) {
                    $item->status = TestResult::STATUS_PASS;
                    if (!$item->note) {
                        $item->note = 'Demonstratiewaarde; geen fysieke controle.';
                    }
                    $item->saveOrFail();
                }
            }
            $run->syncFinishedAtFromResults();
        }
    }
    return ['environment' => config('app.env'), 'asset_id' => $asset->id, 'asset_tag' => $tag,
        'serial' => $asset->serial, 'marker' => $marker, 'created' => $created,
        'prepared_demonstration_results' => $finished,
        'status' => $asset->fresh()->assetstatus->name,
        'runs' => TestRun::where('asset_id', $asset->id)->get(['id', 'workflow_profile_id'])->toArray()];
});
echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
