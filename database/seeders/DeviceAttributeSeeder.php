<?php

namespace Database\Seeders;

use App\Models\AttributeDefinition;
use App\Models\Category;
use Database\Seeders\Concerns\ProvidesDeviceCatalogData;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DeviceAttributeSeeder extends Seeder
{
    use ProvidesDeviceCatalogData;

    public function run(): void
    {
        $categories = $this->resolveCatalogCategories();

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
                'allow_custom_values' => $config['allow_custom_values'] ?? ($config['datatype'] === AttributeDefinition::DATATYPE_TEXT),
                'allow_asset_override' => $config['allow_asset_override'] ?? false,
                'component_spec_display_mode' => $config['component_spec_display_mode'] ?? AttributeDefinition::COMPONENT_SPEC_DISPLAY_VALUE_LABELS,
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
            $definitions->put($key, $definition);
        }

        return new EloquentCollection($definitions->values());
    }

    private function resolveCategory(string $name, string $categoryType): Category
    {
        /** @var Category $category */
        $category = Category::withTrashed()->firstOrNew([
            'name' => $name,
            'category_type' => $categoryType,
        ]);

        if (! $category->exists) {
            $category->created_by = null;
        }

        if ($category->trashed()) {
            $category->restore();
        }

        $category->save();

        return $category;
    }

    /**
     * @return array<string,Category>
     */
    private function resolveCatalogCategories(): array
    {
        return [
            'Laptops' => $this->resolveCategory('Laptops', 'asset'),
            'Mobile Phones' => $this->resolveCategory('Mobile Phones', 'asset'),
            'Memory' => $this->resolveCategory('Memory', 'component'),
            'Storage' => $this->resolveCategory('Storage', 'component'),
            'Display' => $this->resolveCategory('Display', 'component'),
            'Battery' => $this->resolveCategory('Battery', 'component'),
            'Logic Board' => $this->resolveCategory('Logic Board', 'component'),
            'Ports' => $this->resolveCategory('Ports', 'component'),
            'Camera' => $this->resolveCategory('Camera', 'component'),
            'Audio' => $this->resolveCategory('Audio', 'component'),
            'Input' => $this->resolveCategory('Input', 'component'),
            'Network' => $this->resolveCategory('Network', 'component'),
            'Power' => $this->resolveCategory('Power', 'component'),
        ];
    }

    private function syncOptions(AttributeDefinition $definition, array $options): void
    {
        if (empty($options)) {
            return;
        }

        foreach ($options as $index => $optionConfig) {
            $value = $optionConfig['value'];

            $option = $definition->options()->withTrashed()->firstOrNew(['value' => $value]);
            $option->label = $optionConfig['label'];
            $option->active = $optionConfig['active'] ?? true;
            $option->sort_order = $optionConfig['sort_order'] ?? $index;

            if ($option->trashed()) {
                $option->restore();
            }

            $option->save();
        }
    }
}
