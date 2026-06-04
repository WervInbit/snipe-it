<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\TestType\ReorderTestTypesRequest;
use App\Http\Requests\TestType\StoreTestTypeRequest;
use App\Http\Requests\TestType\UpdateTestTypeRequest;
use App\Models\AttributeDefinition;
use App\Models\Category;
use App\Models\ComponentDefinition;
use App\Models\TestType;
use App\Models\WorkflowProfileItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class TestTypeController extends Controller
{
    public function index(): View
    {
        $this->authorize('index', TestType::class);
        $testTypes = TestType::with(['attributeDefinition', 'categories', 'componentCategories', 'componentDefinitions'])
            ->ordered()
            ->get();
        $attributeDefinitions = AttributeDefinition::orderBy('label')->get();
        $categories = Category::query()
            ->where('category_type', 'asset')
            ->orderBy('name')
            ->get();
        $componentCategories = Category::query()
            ->where('category_type', 'component')
            ->orderBy('name')
            ->get();
        $componentDefinitions = ComponentDefinition::query()
            ->with('category')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('settings.testtypes', compact(
            'testTypes',
            'attributeDefinitions',
            'categories',
            'componentCategories',
            'componentDefinitions'
        ));
    }

    public function store(StoreTestTypeRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $categoryIds = $data['category_ids'] ?? [];
        $componentCategoryIds = $data['component_category_ids'] ?? [];
        $componentDefinitionIds = $data['component_definition_ids'] ?? [];
        unset($data['category_ids'], $data['component_category_ids'], $data['component_definition_ids'], $data['manual_slug_override']);

        $data['is_required'] = $request->has('is_required')
            ? $request->boolean('is_required')
            : true;
        $data['applies_to_all'] = $request->boolean('applies_to_all');
        $data['result_label_mode'] = $data['result_label_mode'] ?? WorkflowProfileItem::LABEL_MODE_PASS_FAIL;
        $data['display_order'] = ((int) TestType::query()->max('display_order')) + 1;

        $testType = TestType::create($data);
        $testType->categories()->sync($categoryIds);
        $testType->componentCategories()->sync($componentCategoryIds);
        $testType->componentDefinitions()->sync($componentDefinitionIds);

        return redirect()
            ->route('settings.testtypes.index')
            ->with('success', trans('admin/testtypes/message.create.success'));
    }

    public function update(UpdateTestTypeRequest $request, TestType $testtype): RedirectResponse
    {
        $data = $request->validated();
        $categoryIds = $data['category_ids'] ?? [];
        $componentCategoryIds = $data['component_category_ids'] ?? [];
        $componentDefinitionIds = $data['component_definition_ids'] ?? [];
        unset($data['category_ids'], $data['component_category_ids'], $data['component_definition_ids'], $data['manual_slug_override']);

        if ($request->has('is_required')) {
            $data['is_required'] = $request->boolean('is_required');
        }
        $data['applies_to_all'] = $request->boolean('applies_to_all');
        $data['result_label_mode'] = $data['result_label_mode'] ?? WorkflowProfileItem::LABEL_MODE_PASS_FAIL;

        $testtype->update($data);
        $testtype->categories()->sync($categoryIds);
        $testtype->componentCategories()->sync($componentCategoryIds);
        $testtype->componentDefinitions()->sync($componentDefinitionIds);

        return redirect()->route('settings.testtypes.index')
            ->with('success', trans('admin/testtypes/message.update.success'));
    }

    public function destroy(TestType $testtype): RedirectResponse
    {
        $this->authorize('delete', $testtype);

        if ($testtype->results()->exists()) {
            return redirect()
                ->route('settings.testtypes.index')
                ->with('error', trans('admin/testtypes/message.delete.in_use'));
        }

        $testtype->delete();

        return redirect()
            ->route('settings.testtypes.index')
            ->with('success', trans('admin/testtypes/message.delete.success'));
    }

    public function reorder(ReorderTestTypesRequest $request): JsonResponse
    {
        $order = array_values(array_map('intval', $request->input('order', [])));
        $types = TestType::query()->whereIn('id', $order)->get()->keyBy('id');

        DB::transaction(function () use ($order, $types): void {
            foreach ($order as $position => $id) {
                if (!$types->has($id)) {
                    continue;
                }

                /** @var TestType $type */
                $type = $types->get($id);
                if ((int) $type->display_order !== $position) {
                    $type->display_order = $position;
                    $type->save();
                }
            }
        });

        return response()->json(['status' => 'ok']);
    }
}
