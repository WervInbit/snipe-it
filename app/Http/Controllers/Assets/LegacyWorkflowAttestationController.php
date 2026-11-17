<?php

namespace App\Http\Controllers\Assets;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\AssetModel;
use App\Models\ModelNumber;
use App\Models\WorkflowLegacyAttestation;
use App\Models\WorkflowProfile;
use App\Services\DraftAssetWorkflowContextFactory;
use App\Services\LegacyWorkflowAttestationService;
use App\Services\WorkflowRunDefinitionService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class LegacyWorkflowAttestationController extends Controller
{
    public function options(
        Request $request,
        DraftAssetWorkflowContextFactory $draftAssets,
        WorkflowRunDefinitionService $definitions
    ): View {
        Gate::authorize('create', Asset::class);
        Gate::authorize('tests.manage_legacy_evidence');

        $validated = $request->validate([
            'model_id' => ['required', 'integer', 'exists:models,id'],
            'model_number_id' => [
                'required',
                'integer',
                Rule::exists('model_numbers', 'id')->where(fn ($query) => $query
                    ->where('model_id', (int) $request->input('model_id'))
                    ->whereNull('deprecated_at')),
            ],
        ]);

        $model = AssetModel::query()->findOrFail($validated['model_id']);
        $modelNumber = ModelNumber::query()
            ->active()
            ->where('model_id', $model->id)
            ->findOrFail($validated['model_number_id']);
        $asset = $draftAssets->make($model, $modelNumber);

        $profileDefinitions = WorkflowProfile::query()
            ->active()
            ->forAsset($asset)
            ->whereHas('items')
            ->ordered()
            ->get()
            ->map(fn (WorkflowProfile $profile): array => [
                'profile' => $profile,
                'items' => $definitions->forProfile($asset, $profile)['profile_items'],
            ])
            ->filter(fn (array $row): bool => $row['items']->isNotEmpty())
            ->values();

        return view('hardware.partials.legacy-workflow-items', compact('profileDefinitions'));
    }

    public function revoke(
        Request $request,
        Asset $asset,
        WorkflowLegacyAttestation $attestation,
        LegacyWorkflowAttestationService $attestations
    ): RedirectResponse {
        $this->authorize('view', $asset);
        Gate::authorize('tests.manage_legacy_evidence');
        abort_unless((int) $attestation->asset_id === (int) $asset->id, 404);

        $validated = $request->validate([
            'revocation_reason' => ['required', 'string', 'max:2000'],
        ]);
        $attestations->revoke($asset, $attestation, $request->user(), $validated['revocation_reason']);

        return redirect()->back()->with('success', trans('tests.legacy_revoked_success'));
    }
}
