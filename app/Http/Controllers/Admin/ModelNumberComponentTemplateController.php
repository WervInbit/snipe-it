<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AssetModel;
use App\Models\ComponentDefinition;
use App\Models\ModelNumber;
use App\Models\ModelNumberComponentTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ModelNumberComponentTemplateController extends Controller
{
    public function index(AssetModel $model, ModelNumber $modelNumber): RedirectResponse
    {
        $this->authorize('update', $model);
        $this->ensureModelNumber($model, $modelNumber);

        return redirect()->to(route('models.numbers.spec.edit', [$model, $modelNumber]) . '#expected-components');
    }

    public function store(Request $request, AssetModel $model, ModelNumber $modelNumber): RedirectResponse
    {
        $this->authorize('update', $model);
        $this->ensureModelNumber($model, $modelNumber);

        $data = $this->validatedData($request, $modelNumber);
        $data['sort_order'] = $data['sort_order'] ?? (($modelNumber->componentTemplates()->max('sort_order') ?? -1) + 1);

        $modelNumber->componentTemplates()->create($data);
        $this->normalizeSortOrder($modelNumber);

        return redirect()
            ->to(route('models.numbers.spec.edit', [$model, $modelNumber]) . '#expected-components')
            ->with('success', __('Expected component added.'));
    }

    public function update(
        Request $request,
        AssetModel $model,
        ModelNumber $modelNumber,
        ModelNumberComponentTemplate $componentTemplate
    ): RedirectResponse {
        $this->authorize('update', $model);
        $this->ensureModelNumber($model, $modelNumber);
        $this->ensureTemplate($modelNumber, $componentTemplate);

        $componentTemplate->fill($this->validatedData($request, $modelNumber))->save();
        $this->normalizeSortOrder($modelNumber);

        return redirect()
            ->to(route('models.numbers.spec.edit', [$model, $modelNumber]) . '#expected-components')
            ->with('success', __('Expected component updated.'));
    }

    public function destroy(
        AssetModel $model,
        ModelNumber $modelNumber,
        ModelNumberComponentTemplate $componentTemplate
    ): RedirectResponse {
        $this->authorize('update', $model);
        $this->ensureModelNumber($model, $modelNumber);
        $this->ensureTemplate($modelNumber, $componentTemplate);

        $componentTemplate->delete();
        $this->normalizeSortOrder($modelNumber);

        return redirect()
            ->to(route('models.numbers.spec.edit', [$model, $modelNumber]) . '#expected-components')
            ->with('success', __('Expected component removed.'));
    }

    public function reorder(Request $request, AssetModel $model, ModelNumber $modelNumber): RedirectResponse
    {
        $this->authorize('update', $model);
        $this->ensureModelNumber($model, $modelNumber);

        $data = $request->validate([
            'template_id' => [
                'required',
                'integer',
                Rule::exists('model_number_component_templates', 'id')->where(
                    fn ($query) => $query->where('model_number_id', $modelNumber->id)
                ),
            ],
            'direction' => ['required', Rule::in(['up', 'down'])],
        ]);

        $templates = $modelNumber->componentTemplates()->orderBy('sort_order')->orderBy('id')->get()->values();
        $currentIndex = $templates->search(fn (ModelNumberComponentTemplate $template) => $template->id === (int) $data['template_id']);

        if ($currentIndex === false) {
            abort(404);
        }

        $swapIndex = $data['direction'] === 'up'
            ? $currentIndex - 1
            : $currentIndex + 1;

        if (!isset($templates[$swapIndex])) {
            return redirect()->to(route('models.numbers.spec.edit', [$model, $modelNumber]) . '#expected-components');
        }

        $current = $templates[$currentIndex];
        $swap = $templates[$swapIndex];
        $templates[$currentIndex] = $swap;
        $templates[$swapIndex] = $current;

        $this->persistTemplateOrder($templates);

        return redirect()
            ->to(route('models.numbers.spec.edit', [$model, $modelNumber]) . '#expected-components')
            ->with('success', __('Expected component order updated.'));
    }

    protected function validatedData(Request $request, ModelNumber $modelNumber): array
    {
        $data = $request->validate([
            'component_definition_id' => [
                'required',
                'integer',
                Rule::exists('component_definitions', 'id')->where(
                    fn ($query) => $query->where('is_active', true)
                ),
            ],
            'expected_qty' => ['required', 'integer', 'min:1'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $componentDefinition = ComponentDefinition::query()
            ->where('is_active', true)
            ->findOrFail((int) $data['component_definition_id']);

        $data['model_number_id'] = $modelNumber->id;
        $data['expected_name'] = $componentDefinition->name;
        $data['is_required'] = true;
        $data['slot_name'] = null;
        $data['notes'] = null;

        return $data;
    }

    protected function normalizeSortOrder(ModelNumber $modelNumber): void
    {
        $this->persistTemplateOrder(
            $modelNumber->componentTemplates()->orderBy('sort_order')->orderBy('id')->get()
        );
    }

    protected function persistTemplateOrder(Collection $templates): void
    {
        DB::transaction(function () use ($templates): void {
            foreach ($templates->values() as $index => $template) {
                $template->forceFill(['sort_order' => $index])->save();
            }
        });
    }

    protected function activeComponentDefinitions(): Collection
    {
        return ComponentDefinition::query()
            ->with(['category', 'manufacturer'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    protected function ensureModelNumber(AssetModel $model, ModelNumber $modelNumber): void
    {
        abort_unless((int) $modelNumber->model_id === (int) $model->id, 404);
    }

    protected function ensureTemplate(ModelNumber $modelNumber, ModelNumberComponentTemplate $componentTemplate): void
    {
        abort_unless((int) $componentTemplate->model_number_id === (int) $modelNumber->id, 404);
    }
}
