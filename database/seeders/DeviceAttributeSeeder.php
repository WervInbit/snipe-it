<?php

namespace Database\Seeders;

use App\Models\AttributeDefinition;
use App\Models\Category;
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

        $categories = $this->resolveCatalogCategories($admin);

        DB::transaction(function () use ($categories) {
            $this->seedDefinitions($categories);
            $this->markRemovedDefinitions();
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

    private function resolveCategory(string $name, callable $fallback, ?string $categoryType = null): Category
    {
        $existing = Category::query()
            ->where('name', $name)
            ->when($categoryType, fn ($query) => $query->where('category_type', $categoryType))
            ->first();

        if ($existing) {
            return $existing;
        }

        return $fallback();
    }

    /**
     * @return array<string,Category>
     */
    private function resolveCatalogCategories(User $admin): array
    {
        $componentCategory = fn (string $name) => \Database\Factories\CategoryFactory::new()
            ->forComponents()
            ->create([
                'created_by' => $admin->id,
                'name' => $name,
            ]);

        return [
            'Laptops' => $this->resolveCategory('Laptops', fn () => \Database\Factories\CategoryFactory::new()->assetLaptopCategory()->create(['created_by' => $admin->id]), 'asset'),
            'Mobile Phones' => $this->resolveCategory('Mobile Phones', fn () => \Database\Factories\CategoryFactory::new()->assetMobileCategory()->create(['created_by' => $admin->id]), 'asset'),
            'Memory' => $this->resolveCategory('Memory', fn () => $componentCategory('Memory'), 'component'),
            'Storage' => $this->resolveCategory('Storage', fn () => $componentCategory('Storage'), 'component'),
            'Display' => $this->resolveCategory('Display', fn () => $componentCategory('Display'), 'component'),
            'Battery' => $this->resolveCategory('Battery', fn () => $componentCategory('Battery'), 'component'),
            'Logic Board' => $this->resolveCategory('Logic Board', fn () => $componentCategory('Logic Board'), 'component'),
            'Ports' => $this->resolveCategory('Ports', fn () => $componentCategory('Ports'), 'component'),
            'Camera' => $this->resolveCategory('Camera', fn () => $componentCategory('Camera'), 'component'),
            'Audio' => $this->resolveCategory('Audio', fn () => $componentCategory('Audio'), 'component'),
            'Input' => $this->resolveCategory('Input', fn () => $componentCategory('Input'), 'component'),
            'Network' => $this->resolveCategory('Network', fn () => $componentCategory('Network'), 'component'),
            'Power' => $this->resolveCategory('Power', fn () => $componentCategory('Power'), 'component'),
        ];
    }

    private function markRemovedDefinitions(): void
    {
        $definitionIds = AttributeDefinition::query()
            ->whereIn('key', $this->removedAttributeKeys())
            ->pluck('id');

        if ($definitionIds->isEmpty()) {
            return;
        }

        AttributeDefinition::query()
            ->whereIn('id', $definitionIds)
            ->update([
                'deprecated_at' => now(),
                'hidden_at' => now(),
            ]);

        DB::table('attribute_options')
            ->whereIn('attribute_definition_id', $definitionIds->all())
            ->update(['active' => false]);
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
