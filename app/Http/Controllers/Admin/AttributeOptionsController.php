<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AttributeOptionRequest;
use App\Models\AttributeDefinition;
use App\Models\AttributeOption;
use App\Models\AssetAttributeOverride;
use App\Models\ComponentDefinitionAttribute;
use App\Models\ModelNumberAttribute;
use Illuminate\Http\RedirectResponse;

class AttributeOptionsController extends Controller
{
    public function store(AttributeOptionRequest $request, AttributeDefinition $attribute): RedirectResponse
    {
        $this->authorize('update', $attribute);
        $this->guardEnumDatatype($attribute);

        $data = $request->validated();
        $option = AttributeOption::withTrashed()
            ->where('attribute_definition_id', $attribute->id)
            ->where('value', $data['value'])
            ->first();

        if ($option) {
            $option->fill([
                'label' => $data['label'],
                'active' => $request->boolean('active', true),
                'sort_order' => $data['sort_order'] ?? 0,
            ]);
            $option->restore();
            $option->save();
        } else {
            $attribute->options()->create([
                'value' => $data['value'],
                'label' => $data['label'],
                'active' => $request->boolean('active', true),
                'sort_order' => $data['sort_order'] ?? 0,
            ]);
        }

        return redirect()
            ->route('attributes.edit', $attribute)
            ->with('success', __('Option saved.'));
    }

    public function update(AttributeOptionRequest $request, AttributeDefinition $attribute, AttributeOption $option): RedirectResponse
    {
        $this->authorize('update', $attribute);
        $this->guardEnumDatatype($attribute);
        $this->ensureOptionBelongsToAttribute($attribute, $option);

        $data = $request->validated();
        $valueChanged = $data['value'] !== $option->value;

        $option->fill([
            'value' => $data['value'],
            'label' => $data['label'],
            'active' => $request->boolean('active', true),
            'sort_order' => $data['sort_order'] ?? 0,
        ])->save();

        if ($valueChanged) {
            $this->syncCurrentOptionValue($option);
        }

        return redirect()
            ->route('attributes.edit', $attribute)
            ->with('success', __('Option updated.'));
    }

    public function destroy(AttributeDefinition $attribute, AttributeOption $option): RedirectResponse
    {
        $this->authorize('update', $attribute);
        $this->guardEnumDatatype($attribute);
        $this->ensureOptionBelongsToAttribute($attribute, $option);

        $option->delete();

        return redirect()
            ->route('attributes.edit', $attribute)
            ->with('success', __('Option removed.'));
    }

    private function guardEnumDatatype(AttributeDefinition $attribute): void
    {
        if (!$attribute->isEnum()) {
            abort(403, __('Options can only be managed for enum attributes.'));
        }
    }

    private function syncCurrentOptionValue(AttributeOption $option): void
    {
        ModelNumberAttribute::query()
            ->where('attribute_option_id', $option->id)
            ->update([
                'value' => $option->value,
                'raw_value' => $option->value,
            ]);

        AssetAttributeOverride::query()
            ->where('attribute_option_id', $option->id)
            ->update([
                'value' => $option->value,
                'raw_value' => $option->value,
            ]);

        ComponentDefinitionAttribute::query()
            ->where('attribute_option_id', $option->id)
            ->update([
                'value' => $option->value,
                'raw_value' => $option->value,
            ]);
    }
}
