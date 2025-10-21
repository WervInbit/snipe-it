<?php

namespace Database\Seeders;

use App\Models\AttributeDefinition;
use App\Models\TestType;
use App\Models\User;
use Database\Seeders\Concerns\ProvidesDeviceCatalogData;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DeviceAttributeSeeder extends Seeder
{
    use ProvidesDeviceCatalogData;

    public function run(): void
    {
        $admin = User::where('permissions->superuser', '1')->first()
            ?? User::factory()->firstAdmin()->create();

        $categories = [
            'Laptops' => $this->resolveCategory('Laptops', fn () => \Database\Factories\CategoryFactory::new()->assetLaptopCategory()->create(['created_by' => $admin->id])),
            'Mobile Phones' => $this->resolveCategory('Mobile Phones', fn () => \Database\Factories\CategoryFactory::new()->assetMobileCategory()->create(['created_by' => $admin->id])),
        ];

        DB::transaction(function () use ($categories) {
            $this->seedDefinitions($categories);
        });
    }

    /**
     * @param array<string,\App\Models\Category> $categories
     */
    private function seedDefinitions(array $categories): EloquentCollection
    {
        $blueprints = $this->attributeBlueprints();
        $definitions = collect();

        foreach ($blueprints as $key => $config) {
            /** @var AttributeDefinition $definition */
            $definition = AttributeDefinition::withTrashed()->firstOrNew(['key' => $key]);

            $definition->fill([
                'label' => $config['label'],
                'datatype' => $config['datatype'],
                'unit' => $config['unit'] ?? null,
                'required_for_category' => $config['required'] ?? false,
                'needs_test' => $config['needs_test'] ?? false,
                'allow_custom_values' => $config['allow_custom_values'] ?? ($config['datatype'] === AttributeDefinition::DATATYPE_TEXT),
                'allow_asset_override' => $config['allow_asset_override'] ?? false,
                'constraints' => $config['constraints'] ?? [],
            ]);

            if ($definition->trashed()) {
                $definition->restore();
            }

            $definition->save();

            $categoryNames = $config['categories'] ?? [];

            if (!empty($categoryNames)) {
                $categoryIds = collect($categoryNames)
                    ->map(fn ($name) => $categories[$name]->id ?? null)
                    ->filter()
                    ->values();

                if ($categoryIds->isNotEmpty()) {
                    $definition->categories()->syncWithoutDetaching($categoryIds->all());
                }
            }

            $this->syncOptions($definition, $config['options'] ?? []);

            if (!empty($config['needs_test'])) {
                TestType::forAttribute($definition);
            }

            $definitions->put($key, $definition);
        }

        return new EloquentCollection($definitions->values());
    }

    private function resolveCategory(string $name, callable $fallback)
    {
        $existing = \App\Models\Category::where('name', $name)->first();

        if ($existing) {
            return $existing;
        }

        return $fallback();
    }

    private function syncOptions(AttributeDefinition $definition, array $options): void
    {
        if (empty($options)) {
            return;
        }

        $activeValues = [];

        foreach ($options as $index => $optionConfig) {
            $value = $optionConfig['value'];
            $activeValues[] = $value;

            $option = $definition->options()->withTrashed()->firstOrNew(['value' => $value]);
            $option->label = $optionConfig['label'];
            $option->active = $optionConfig['active'] ?? true;
            $option->sort_order = $optionConfig['sort_order'] ?? $index;

            if ($option->trashed()) {
                $option->restore();
            }

            $option->save();
        }

        $definition->options()
            ->whereNotIn('value', $activeValues)
            ->update(['active' => false]);
    }
}
