<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\TestType\StoreTestTypeRequest;
use App\Http\Requests\TestType\UpdateTestTypeRequest;
use App\Models\AttributeDefinition;
use App\Models\TestType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\View\View;

class TestTypeController extends Controller
{
    public function index(): View
    {
        $this->authorize('index', TestType::class);
        $testTypes = TestType::with('attributeDefinition')->orderBy('name')->get();
        $attributeDefinitions = AttributeDefinition::orderBy('label')->get();

        return view('settings.testtypes', compact('testTypes', 'attributeDefinitions'));
    }

    public function store(StoreTestTypeRequest $request): RedirectResponse
    {
        $data = $request->validated();

        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        if ($data['slug'] === '') {
            $data['slug'] = Str::slug($data['name'] . '-' . Str::random(4));
        }

        $data['category'] = 'attribute';

        TestType::create($data);

        return redirect()
            ->route('settings.testtypes.index')
            ->with('success', trans('admin/testtypes/message.create.success'));
    }

    public function update(UpdateTestTypeRequest $request, TestType $testtype): RedirectResponse
    {
        $data = $request->validated();

        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        if ($data['slug'] === '') {
            $data['slug'] = Str::slug($data['name'] . '-' . Str::random(4));
        }

        $data['category'] = 'attribute';

        $testtype->update($data);

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
