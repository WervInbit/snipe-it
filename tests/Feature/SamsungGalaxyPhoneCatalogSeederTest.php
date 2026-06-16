<?php

namespace Tests\Feature;

use App\Models\AttributeDefinition;
use App\Models\ComponentDefinition;
use App\Models\ModelNumber;
use App\Models\ModelNumberComponentTemplate;
use App\Models\TestType;
use Database\Seeders\AttributeTestSeeder;
use Database\Seeders\SamsungGalaxyPhoneCatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SamsungGalaxyPhoneCatalogSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_samsung_galaxy_phone_catalog_seeder_is_idempotent(): void
    {
        $this->seed(SamsungGalaxyPhoneCatalogSeeder::class);
        $counts = $this->catalogCounts();

        $this->seed(SamsungGalaxyPhoneCatalogSeeder::class);

        $this->assertSame($counts, $this->catalogCounts());
    }

    public function test_samsung_galaxy_phone_catalog_seeds_target_variants(): void
    {
        $this->seed(AttributeTestSeeder::class);
        $this->seed(SamsungGalaxyPhoneCatalogSeeder::class);

        $storageType = AttributeDefinition::query()->where('key', 'storage_type')->firstOrFail();
        $storageCapacity = AttributeDefinition::query()->where('key', 'storage_capacity_gb')->firstOrFail();
        $nfc = AttributeDefinition::query()->where('key', 'nfc')->firstOrFail();
        $bluetoothVersion = AttributeDefinition::query()->where('key', 'bluetooth_version')->firstOrFail();
        $color = AttributeDefinition::query()->where('key', 'color')->firstOrFail();

        $this->assertDatabaseHas('attribute_options', [
            'attribute_definition_id' => $storageType->id,
            'value' => 'emmc',
            'label' => 'eMMC-opslag',
            'active' => true,
        ]);

        $a32 = ModelNumber::query()->where('code', 'SM-A325F/DS-6GB-128GB')->firstOrFail();
        $a50 = ModelNumber::query()->where('code', 'SM-A505FN/DS-4GB-128GB')->firstOrFail();
        $a51 = ModelNumber::query()->where('code', 'SM-A515F/DSN-4GB-128GB')->firstOrFail();

        $this->assertSame('Samsung Galaxy A32 - SM-A325F/DS - 6GB - 128GB - Black', $a32->label);
        $this->assertSame('Samsung Galaxy A50 - SM-A505FN/DS - 4GB - 128GB - Black', $a50->label);
        $this->assertSame('Samsung Galaxy A51 - SM-A515F/DSN - 4GB - 128GB - Black', $a51->label);

        $this->assertPhoneHasExpectedComponents($a32, [
            'Logic Board - Samsung Galaxy A32 SM-A325F/DS',
            'Display 6.4 1080x2400 Super AMOLED 90Hz',
            'Battery 5000 mAh',
            'Camera - Selfie - 20MP',
            'Camera - Main - 64MP',
            'Camera - Ultrawide - 8MP',
            'Camera - Macro - 5MP',
            'Camera - Depth - 5MP',
        ]);
        $this->assertPhoneHasExpectedComponents($a50, [
            'Logic Board - Samsung Galaxy A50 SM-A505FN/DS',
            'Display 6.4 2340x1080 Super AMOLED 60Hz',
            'Battery 4000 mAh',
            'Camera - Selfie - 25MP',
            'Camera - Main - 25MP',
            'Camera - Ultrawide - 8MP',
            'Camera - Depth - 5MP',
        ]);
        $this->assertPhoneHasExpectedComponents($a51, [
            'Logic Board - Samsung Galaxy A51 SM-A515F/DSN',
            'Display 6.5 1080x2400 Super AMOLED 60Hz',
            'Battery 4000 mAh',
            'Camera - Selfie - 32MP',
            'Camera - Main - 48MP',
            'Camera - Ultrawide - 12MP',
            'Camera - Macro - 5MP',
            'Camera - Depth - 5MP',
        ]);

        $a32LogicBoard = ComponentDefinition::query()
            ->where('name', 'Logic Board - Samsung Galaxy A32 SM-A325F/DS')
            ->firstOrFail();
        $a32Storage = ComponentDefinition::query()->where('name', 'Storage 128GB eMMC')->firstOrFail();
        $a50Selfie = ComponentDefinition::query()->where('name', 'Camera - Selfie - 25MP')->firstOrFail();
        $a50Main = ComponentDefinition::query()->where('name', 'Camera - Main - 25MP')->firstOrFail();

        $this->assertDatabaseHas('component_definition_subcomponent_templates', [
            'parent_component_definition_id' => $a32LogicBoard->id,
            'child_component_definition_id' => $a32Storage->id,
            'expected_qty' => 1,
        ]);
        $this->assertDatabaseHas('component_definition_attributes', [
            'component_definition_id' => $a32Storage->id,
            'attribute_definition_id' => $storageType->id,
            'value' => 'emmc',
            'resolves_to_spec' => true,
        ]);
        $this->assertDatabaseMissing('model_number_attributes', [
            'model_number_id' => $a32->id,
            'attribute_definition_id' => $storageCapacity->id,
        ]);

        $this->assertTrue(
            TestType::query()
                ->where('slug', 'front_camera')
                ->firstOrFail()
                ->componentDefinitions()
                ->whereKey($a50Selfie->id)
                ->exists()
        );
        $this->assertTrue(
            TestType::query()
                ->where('slug', 'rear_camera')
                ->firstOrFail()
                ->componentDefinitions()
                ->whereKey($a50Main->id)
                ->exists()
        );

        foreach ([$a32, $a50, $a51] as $modelNumber) {
            $this->assertDatabaseHas('model_number_attributes', [
                'model_number_id' => $modelNumber->id,
                'attribute_definition_id' => $nfc->id,
                'value' => '1',
            ]);
            $this->assertDatabaseHas('model_number_attributes', [
                'model_number_id' => $modelNumber->id,
                'attribute_definition_id' => $bluetoothVersion->id,
                'value' => '5.0',
            ]);
        }

        $this->assertDatabaseHas('model_number_attributes', [
            'model_number_id' => $a51->id,
            'attribute_definition_id' => $color->id,
            'value' => 'Prism Crush Black',
        ]);
    }

    public function test_samsung_galaxy_phone_catalog_preserves_manual_expected_components(): void
    {
        $this->seed(SamsungGalaxyPhoneCatalogSeeder::class);

        $a50 = ModelNumber::query()->where('code', 'SM-A505FN/DS-4GB-128GB')->firstOrFail();
        $manualDefinition = ComponentDefinition::factory()->create([
            'name' => 'Manual A50 Accessory Check',
        ]);

        $manualTemplate = ModelNumberComponentTemplate::query()->create([
            'model_number_id' => $a50->id,
            'component_definition_id' => $manualDefinition->id,
            'expected_name' => $manualDefinition->name,
            'expected_qty' => 1,
            'is_required' => false,
            'sort_order' => 99,
            'metadata_json' => ['manual' => true],
        ]);

        $this->seed(SamsungGalaxyPhoneCatalogSeeder::class);

        $this->assertDatabaseHas('model_number_component_templates', [
            'id' => $manualTemplate->id,
            'model_number_id' => $a50->id,
            'component_definition_id' => $manualDefinition->id,
        ]);
    }

    /**
     * @param array<int,string> $componentNames
     */
    private function assertPhoneHasExpectedComponents(ModelNumber $modelNumber, array $componentNames): void
    {
        $definitions = ComponentDefinition::query()
            ->whereIn('name', $componentNames)
            ->get()
            ->keyBy('name');

        foreach ($componentNames as $componentName) {
            $definition = $definitions->get($componentName);

            $this->assertNotNull($definition, "Missing component definition: {$componentName}");
            $this->assertDatabaseHas('model_number_component_templates', [
                'model_number_id' => $modelNumber->id,
                'component_definition_id' => $definition->id,
            ]);
        }
    }

    /**
     * @return array<string,int>
     */
    private function catalogCounts(): array
    {
        return [
            'attribute_definitions' => AttributeDefinition::query()->count(),
            'attribute_options' => DB::table('attribute_options')->count(),
            'models' => DB::table('models')->count(),
            'model_numbers' => ModelNumber::query()->count(),
            'model_number_attributes' => DB::table('model_number_attributes')->count(),
            'component_definitions' => ComponentDefinition::query()->count(),
            'component_definition_attributes' => DB::table('component_definition_attributes')->count(),
            'component_definition_subcomponent_templates' => DB::table('component_definition_subcomponent_templates')->count(),
            'model_number_component_templates' => ModelNumberComponentTemplate::query()->count(),
        ];
    }
}
