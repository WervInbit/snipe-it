<?php

namespace App\Http\Requests\TestType;

use App\Models\TestType;
use App\Models\WorkflowProfileItem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTestTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', TestType::class);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:191'],
            'slug' => ['nullable', 'string', 'max:191', 'regex:/^[A-Za-z0-9-]+$/', 'unique:workflow_items,slug'],
            'manual_slug_override' => ['sometimes', 'boolean'],
            'attribute_definition_id' => ['nullable', 'exists:attribute_definitions,id'],
            'instructions' => ['nullable', 'string'],
            'tooltip' => ['nullable', 'string'],
            'category_ids' => ['nullable', 'array'],
            'category_ids.*' => ['integer', Rule::exists('categories', 'id')->where('category_type', 'asset')],
            'component_category_ids' => ['nullable', 'array'],
            'component_category_ids.*' => ['integer', Rule::exists('categories', 'id')->where('category_type', 'component')],
            'component_definition_ids' => ['nullable', 'array'],
            'component_definition_ids.*' => ['integer', 'exists:component_definitions,id'],
            'applies_to_all' => ['sometimes', 'boolean'],
            'is_required' => ['sometimes', 'boolean'],
            'result_label_mode' => ['nullable', Rule::in(WorkflowProfileItem::LABEL_MODES)],
        ];
    }

    protected function prepareForValidation(): void
    {
        $manualOverride = $this->boolean('manual_slug_override');
        $name = trim((string) $this->input('name'));
        $slugInput = trim((string) $this->input('slug'));
        $source = $manualOverride
            ? ($slugInput !== '' ? $slugInput : $name)
            : $name;

        if ($source === '') {
            $this->merge([
                'manual_slug_override' => $manualOverride,
                'slug' => null,
            ]);

            return;
        }

        $this->merge([
            'manual_slug_override' => $manualOverride,
            'slug' => TestType::generateUniqueSlug($source),
        ]);
    }
}
