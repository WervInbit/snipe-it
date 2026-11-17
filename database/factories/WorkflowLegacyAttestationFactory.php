<?php

namespace Database\Factories;

use App\Models\Asset;
use App\Models\User;
use App\Models\WorkflowLegacyAttestation;
use App\Models\WorkflowProfileItem;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class WorkflowLegacyAttestationFactory extends Factory
{
    protected $model = WorkflowLegacyAttestation::class;

    public function configure(): static
    {
        return $this->afterMaking(function (WorkflowLegacyAttestation $attestation): void {
            $profileItem = $attestation->profileItem;
            $asset = $attestation->asset;
            $attestation->workflow_profile_id ??= $profileItem?->workflow_profile_id;
            $attestation->workflow_item_id ??= $profileItem?->workflow_item_id;
            $attestation->model_number_id ??= $asset?->model_number_id;
        });
    }

    public function definition(): array
    {
        return [
            'evidence_set_uuid' => (string) Str::uuid(),
            'asset_id' => Asset::factory(),
            'workflow_profile_item_id' => WorkflowProfileItem::factory(),
            'profile_name_snapshot' => 'Standard Diagnostics',
            'profile_slug_snapshot' => 'standard-diagnostics',
            'item_name_snapshot' => 'Keyboard',
            'item_slug_snapshot' => 'keyboard',
            'requirement_context_hash' => str_repeat('a', 64),
            'source_note' => 'Completed before Snipe-IT adoption.',
            'accepted_by' => User::factory(),
            'accepted_by_name_snapshot' => 'Legacy reviewer',
            'accepted_at' => now(),
            'active_slot' => 1,
        ];
    }
}
