<?php

namespace App\Console\Commands;

use App\Models\AssetAttributeOverride;
use App\Models\AttributeDefinition;
use App\Models\AttributeOption;
use App\Models\ModelNumberAttribute;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PromoteAttributeCustomValues extends Command
{
    protected $signature = 'attribute:promote-custom {key : Attribute definition key} {--apply : Create options for discovered values}';

    protected $description = 'List or promote custom attribute values to formal options.';

    public function handle(): int
    {
        $key = Str::snake($this->argument('key'));
        $definition = AttributeDefinition::where('key', $key)->first();

        if (!$definition) {
            $this->error("Attribute definition with key '{$key}' not found.");
            return self::FAILURE;
        }

        if ($definition->datatype !== AttributeDefinition::DATATYPE_ENUM) {
            $this->error('This command only supports enum attributes.');
            return self::FAILURE;
        }

        if (!$definition->allow_custom_values) {
            $this->warn('Custom values are disabled for this attribute; nothing to promote.');
            return self::SUCCESS;
        }

        $values = $this->collectValues($definition);

        if ($values->isEmpty()) {
            $this->info('No custom values found.');
            return self::SUCCESS;
        }

        $this->table(['Value', 'Occurrences'], $values->map(fn ($count, $value) => [$value, $count]));

        if (!$this->option('apply')) {
            $this->info('Run with --apply to create options and link existing records.');
            return self::SUCCESS;
        }

        DB::transaction(function () use ($definition, $values) {
            foreach ($values as $value => $count) {
                /** @var AttributeOption $option */
                $option = AttributeOption::withTrashed()
                    ->firstOrCreate([
                        'attribute_definition_id' => $definition->id,
                        'value' => $value,
                    ], [
                        'label' => Str::of($value)->title(),
                        'active' => true,
                    ]);

                if ($option->trashed()) {
                    $option->restore();
                }

                $option->fill([
                    'label' => $option->label ?? Str::of($value)->title(),
                    'active' => true,
                ])->save();

                ModelNumberAttribute::query()
                    ->where('attribute_definition_id', $definition->id)
                    ->where('value', $value)
                    ->update(['attribute_option_id' => $option->id]);

                AssetAttributeOverride::query()
                    ->where('attribute_definition_id', $definition->id)
                    ->where('value', $value)
                    ->update(['attribute_option_id' => $option->id]);
            }
        });

        $this->info('Custom values promoted successfully.');

        return self::SUCCESS;
    }

    private function collectValues(AttributeDefinition $definition): Collection
    {
        $modelValues = ModelNumberAttribute::query()
            ->where('attribute_definition_id', $definition->id)
            ->whereNull('attribute_option_id')
            ->whereNotNull('value')
            ->pluck('value');

        $overrideValues = AssetAttributeOverride::query()
            ->where('attribute_definition_id', $definition->id)
            ->whereNull('attribute_option_id')
            ->whereNotNull('value')
            ->pluck('value');

        return $modelValues
            ->merge($overrideValues)
            ->map(fn ($value) => trim((string) $value))
            ->filter(fn ($value) => $value !== '')
            ->countBy()
            ->sortDesc();
    }
}
