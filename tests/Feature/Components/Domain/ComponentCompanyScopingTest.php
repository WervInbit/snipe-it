<?php

namespace Tests\Feature\Components\Domain;

use App\Models\Asset;
use App\Models\Company;
use App\Models\ComponentDefinition;
use App\Models\ComponentInstance;
use App\Models\ComponentStorageLocation;
use App\Models\User;
use App\Services\ComponentLifecycleService;
use InvalidArgumentException;
use Tests\TestCase;

class ComponentCompanyScopingTest extends TestCase
{
    public function testCreateInstanceInheritsActorCompanyWhenFmcsIsEnabled(): void
    {
        $company = Company::factory()->create();
        $actor = User::factory()->for($company)->superuser()->create();
        $definition = ComponentDefinition::factory()->create(['company_id' => null]);
        $location = ComponentStorageLocation::factory()->stock()->create();

        $this->settings->enableMultipleFullCompanySupport();

        $instance = app(ComponentLifecycleService::class)->createInstance([
            'component_definition_id' => $definition->id,
            'display_name' => 'Replacement Fan',
            'status' => ComponentInstance::STATUS_IN_STOCK,
            'storage_location_id' => $location->id,
        ], $actor);

        $this->assertSame($company->id, $instance->company_id);
    }

    public function testInstallIntoAssetAlignsInstanceCompanyWithDestinationAsset(): void
    {
        [$companyA, $companyB] = Company::factory()->count(2)->create();
        $actor = User::factory()->superuser()->create();
        $asset = Asset::factory()->create(['company_id' => $companyB->id]);
        $component = ComponentInstance::factory()->inTray($actor)->create([
            'company_id' => $companyA->id,
        ]);

        $this->settings->enableMultipleFullCompanySupport();

        app(ComponentLifecycleService::class)->installIntoAsset($component, $asset, [
            'performed_by' => $actor,
        ]);

        $this->assertSame($companyB->id, $component->fresh()->company_id);
    }

    public function testCreateInstanceRequiresCompanyScopeWhenFmcsIsEnabledAndNoFallbackExists(): void
    {
        $actor = User::factory()->superuser()->create(['company_id' => null]);
        $definition = ComponentDefinition::factory()->create(['company_id' => null]);
        $location = ComponentStorageLocation::factory()->stock()->create();

        $this->settings->enableMultipleFullCompanySupport();

        $this->expectException(InvalidArgumentException::class);

        app(ComponentLifecycleService::class)->createInstance([
            'component_definition_id' => $definition->id,
            'display_name' => 'Unscoped Component',
            'status' => ComponentInstance::STATUS_IN_STOCK,
            'storage_location_id' => $location->id,
        ], $actor);
    }
}
