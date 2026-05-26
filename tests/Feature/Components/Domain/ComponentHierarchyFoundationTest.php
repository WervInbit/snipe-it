<?php

namespace Tests\Feature\Components\Domain;

use App\Models\Asset;
use App\Models\ComponentDefinition;
use App\Models\ComponentInstance;
use App\Models\User;
use App\Services\Components\ComponentDefinitionSubcomponentTemplateManager;
use App\Services\ComponentLifecycleService;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Tests\TestCase;

class ComponentHierarchyFoundationTest extends TestCase
{
    public function testComponentInstanceCanBeAttachedBeneathTopLevelComponent(): void
    {
        $asset = Asset::factory()->create();
        $parent = ComponentInstance::factory()->installed($asset->id)->create([
            'display_name' => 'Main board',
        ]);

        $child = ComponentInstance::factory()->asChildOf($parent)->create([
            'display_name' => 'USB-C port board',
        ]);

        $this->assertTrue($child->isSubcomponent());
        $this->assertTrue($parent->isTopLevelComponent());
        $this->assertSame($parent->id, $child->parent_component_instance_id);
        $this->assertSame($asset->id, $child->current_asset_id);
        $this->assertSame($asset->id, $child->root_asset_id);
        $this->assertTrue($child->parentComponent->is($parent));
        $this->assertTrue(
            $parent->fresh('childComponents')->childComponents->contains(fn (ComponentInstance $component) => $component->is($child))
        );
    }

    public function testComponentHierarchyRejectsDepthBeyondSubcomponent(): void
    {
        $asset = Asset::factory()->create();
        $parent = ComponentInstance::factory()->installed($asset->id)->create();
        $child = ComponentInstance::factory()->asChildOf($parent)->create();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Component hierarchy is limited to one subcomponent level.');

        ComponentInstance::factory()->asChildOf($child)->create([
            'display_name' => 'Too deep',
        ]);
    }

    public function testComponentWithAttachedChildrenCannotBecomeSubcomponent(): void
    {
        $asset = Asset::factory()->create();
        $parent = ComponentInstance::factory()->installed($asset->id)->create();
        ComponentInstance::factory()->asChildOf($parent)->create();
        $newParent = ComponentInstance::factory()->installed($asset->id)->create();

        $parent->parent_component_instance_id = $newParent->id;

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('A component with attached child components cannot also become a subcomponent.');

        $parent->save();
    }

    public function testLifecycleServiceMaintainsRootAssetForTopLevelFlows(): void
    {
        $actor = User::factory()->superuser()->create();
        $sourceAsset = Asset::factory()->create();
        $targetAsset = Asset::factory()->create();
        $service = app(ComponentLifecycleService::class);

        $instance = $service->createInstance([
            'display_name' => 'Trackpad',
            'status' => ComponentInstance::STATUS_INSTALLED,
            'current_asset_id' => $sourceAsset->id,
            'created_by' => $actor->id,
            'updated_by' => $actor->id,
        ], $actor);

        $this->assertSame($sourceAsset->id, $instance->root_asset_id);

        $removed = $service->removeToTray($instance, $actor);

        $this->assertNull($removed->current_asset_id);
        $this->assertNull($removed->parent_component_instance_id);
        $this->assertNull($removed->root_asset_id);

        $installed = $service->installIntoAsset($removed, $targetAsset, [
            'performed_by' => $actor,
        ]);

        $this->assertSame($targetAsset->id, $installed->current_asset_id);
        $this->assertSame($targetAsset->id, $installed->root_asset_id);
        $this->assertNull($installed->parent_component_instance_id);
    }

    public function testComponentDefinitionPlacementModeIsConstrained(): void
    {
        $definition = ComponentDefinition::factory()->create([
            'placement_mode' => ComponentDefinition::PLACEMENT_SUBCOMPONENT_ONLY,
        ]);

        $this->assertSame(ComponentDefinition::PLACEMENT_SUBCOMPONENT_ONLY, $definition->placement_mode);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Component definition placement mode is invalid.');

        ComponentDefinition::factory()->create([
            'placement_mode' => 'outside_tree',
        ]);
    }

    public function testSubcomponentOnlyDefinitionCannotBeInstalledDirectlyOnAsset(): void
    {
        $actor = User::factory()->superuser()->create();
        $asset = Asset::factory()->create();
        $definition = ComponentDefinition::factory()->create([
            'placement_mode' => ComponentDefinition::PLACEMENT_SUBCOMPONENT_ONLY,
        ]);
        $component = ComponentInstance::factory()->create([
            'component_definition_id' => $definition->id,
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('This component definition is restricted to subcomponent placement and cannot be installed directly on an asset.');

        app(ComponentLifecycleService::class)->installIntoAsset($component, $asset, [
            'performed_by' => $actor,
        ]);
    }

    public function testAssetOnlyDefinitionCannotBeConfiguredAsExpectedSubcomponent(): void
    {
        $parentDefinition = ComponentDefinition::factory()->create([
            'placement_mode' => ComponentDefinition::PLACEMENT_EITHER,
        ]);
        $childDefinition = ComponentDefinition::factory()->create([
            'placement_mode' => ComponentDefinition::PLACEMENT_ASSET_ONLY,
        ]);

        $this->expectException(ValidationException::class);

        app(ComponentDefinitionSubcomponentTemplateManager::class)->sync($parentDefinition, [[
            'child_component_definition_id' => $childDefinition->id,
            'expected_qty' => 1,
        ]]);
    }
}
