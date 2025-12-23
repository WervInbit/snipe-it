<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\TestType\StoreTestTypeRequest;
use App\Http\Requests\TestType\UpdateTestTypeRequest;
use App\Models\AttributeDefinition;
use App\Models\Category;
use App\Models\TestType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\View\View;

class TestTypeController extends Controller
{
    public function index(): View
    {
        $this->authorize('index', TestType::class);
        $testTypes = TestType::with(['attributeDefinition', 'categories'])->orderBy('name')->get();
        $attributeDefinitions = AttributeDefinition::orderBy('label')->get();
        $categories = Category::query()
            ->where('category_type', 'asset')
            ->orderBy('name')
            ->get();

        return view('settings.testtypes', compact('testTypes', 'attributeDefinitions', 'categories'));
    }

    public function store(StoreTestTypeRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $categoryIds = $data['category_ids'] ?? [];
        unset($data['category_ids']);

        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        if ($data['slug'] === '') {
            $data['slug'] = Str::slug($data['name'] . '-' . Str::random(4));
        }

        $data['is_required'] = $request->has('is_required')
            ? $request->boolean('is_required')
            : true;

        $testType = TestType::create($data);
        $testType->categories()->sync($categoryIds);

        return redirect()
            ->route('settings.testtypes.index')
            ->with('success', trans('admin/testtypes/message.create.success'));
    }

    public function update(UpdateTestTypeRequest $request, TestType $testtype): RedirectResponse
    {
        $data = $request->validated();
        $categoryIds = $data['category_ids'] ?? [];
        unset($data['category_ids']);

        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        if ($data['slug'] === '') {
            $data['slug'] = Str::slug($data['name'] . '-' . Str::random(4));
        }

        if ($request->has('is_required')) {
            $data['is_required'] = $request->boolean('is_required');
        }

        $testtype->update($data);
        $testtype->categories()->sync($categoryIds);

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
}
