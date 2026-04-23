<?php

namespace App\Services\Components;

use App\Models\Asset;
use App\Models\AssetExpectedComponentState;
use App\Models\ComponentInstance;
use App\Models\ComponentStorageLocation;
use App\Models\ModelNumberComponentTemplate;
use App\Models\User;
use App\Services\ComponentLifecycleService;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class AssetExpectedComponentService
{
    public function __construct(
        private readonly ComponentLifecycleService $lifecycle,
    ) {
    }

    public function materializeToTray(Asset $asset, ModelNumberComponentTemplate $template, User|int $performedBy, array $context = []): ComponentInstance
    {
        return DB::transaction(function () use ($asset, $template, $performedBy, $context): ComponentInstance {
            $instance = $this->materializeExpectedBaseline($asset, $template, $performedBy, $context);

            return $this->lifecycle->removeToTray($instance, $performedBy, [
                'note' => $context['note'] ?? null,
                'payload_json' => array_filter([
                    'materialized_expected_template_id' => $template->id,
                ]),
            ]);
        });
    }

    public function materializeToStock(Asset $asset, ModelNumberComponentTemplate $template, ?ComponentStorageLocation $location, User|int $performedBy, array $context = []): ComponentInstance
    {
        return DB::transaction(function () use ($asset, $template, $location, $performedBy, $context): ComponentInstance {
            $instance = $this->materializeExpectedBaseline($asset, $template, $performedBy, $context);

            return $this->lifecycle->moveToStock($instance, $location, [
                'performed_by' => $performedBy,
                'needs_verification' => !empty($context['needs_verification']),
                'storage_location' => $context['verification_location'] ?? $location,
                'note' => $context['note'] ?? null,
            ]);
        });
    }

    public function materializeToAsset(Asset $sourceAsset, ModelNumberComponentTemplate $template, Asset $destinationAsset, User|int $performedBy, array $context = []): ComponentInstance
    {
        return DB::transaction(function () use ($sourceAsset, $template, $destinationAsset, $performedBy, $context): ComponentInstance {
            $instance = $this->materializeExpectedBaseline($sourceAsset, $template, $performedBy, $context);

            return $this->lifecycle->installIntoAsset($instance, $destinationAsset, [
                'performed_by' => $performedBy,
                'installed_as' => $context['installed_as'] ?? null,
                'note' => $context['note'] ?? null,
            ]);
        });
    }

    public function materializeExpectedBaseline(Asset $asset, ModelNumberComponentTemplate $template, User|int|null $performedBy = null, array $context = []): ComponentInstance
    {
        $template->loadMissing('componentDefinition');

        if (!$template->component_definition_id) {
            throw new InvalidArgumentException('Expected components must have a catalog definition before they can be moved.');
        }

        return DB::transaction(function () use ($asset, $template, $performedBy, $context): ComponentInstance {
            $state = AssetExpectedComponentState::query()->firstOrCreate(
                [
                    'asset_id' => $asset->id,
                    'model_number_component_template_id' => $template->id,
                ],
                [
                    'removed_qty' => 0,
                ]
            );

            $expectedQty = max(1, (int) $template->expected_qty);
            if ($state->removed_qty >= $expectedQty) {
                throw new InvalidArgumentException('All expected units for this component have already been materialized or removed.');
            }

            $state->forceFill([
                'removed_qty' => $state->removed_qty + 1,
            ])->save();

            return $this->lifecycle->createInstance([
                'component_definition_id' => $template->component_definition_id,
                'company_id' => $asset->company_id,
                'source_type' => ComponentInstance::SOURCE_EXPECTED_BASELINE,
                'source_asset_id' => $asset->id,
                'current_asset_id' => $asset->id,
                'status' => ComponentInstance::STATUS_INSTALLED,
                'condition_code' => ComponentInstance::CONDITION_UNKNOWN,
                'display_name' => $template->componentDefinition?->name ?: $template->expected_name ?: 'Expected component',
                'serial' => null,
                'installed_as' => $context['installed_as'] ?? null,
                'notes' => $context['note'] ?? null,
                'metadata_json' => array_filter([
                    'expected_baseline' => true,
                    'model_number_component_template_id' => $template->id,
                    'materialized_from_asset_id' => $asset->id,
                ]),
            ], $performedBy);
        });
    }
}
