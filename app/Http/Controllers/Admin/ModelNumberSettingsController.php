<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AssetModel;
use App\Models\ModelNumber;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\View\View;

class ModelNumberSettingsController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('index', AssetModel::class);

        $search = trim((string) $request->input('search'));

        $modelNumbers = ModelNumber::query()
            ->with(['model' => fn ($query) => $query->withTrashed()])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('code', 'like', "%{$search}%")
                        ->orWhere('label', 'like', "%{$search}%")
                        ->orWhereHas('model', function ($modelQuery) use ($search) {
                            $modelQuery->where('name', 'like', "%{$search}%");
                        });
                });
            })
            ->withCount('assets')
            ->orderBy('code')
            ->paginate(25)
            ->withQueryString();

        $availableModels = AssetModel::query()
            ->orderBy('name')
            ->pluck('name', 'id');

        return view('settings.model_numbers.index', [
            'modelNumbers' => $modelNumbers,
            'models' => $availableModels,
            'search' => $search,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'model_id' => ['required', 'integer', 'exists:models,id'],
            'code' => ['required', 'string', 'max:255'],
            'label' => ['nullable', 'string', 'max:255'],
            'make_primary' => ['nullable', 'boolean'],
        ]);

        $model = AssetModel::findOrFail($data['model_id']);
        $this->authorize('update', $model);

        $modelNumber = $model->modelNumbers()->create([
            'code' => $data['code'],
            'label' => Arr::get($data, 'label'),
        ]);

        if ($request->boolean('make_primary') || !$model->primary_model_number_id) {
            $model->forceFill([
                'primary_model_number_id' => $modelNumber->id,
                'model_number' => $modelNumber->code,
            ])->save();
        }

        return redirect()
            ->route('settings.model_numbers.index')
            ->with('success', __('Model number added.'));
    }

    public function update(Request $request, ModelNumber $modelNumber): RedirectResponse
    {
        $model = $modelNumber->model;
        $this->authorize('update', $model);

        $data = $request->validate([
            'code' => ['required', 'string', 'max:255'],
            'label' => ['nullable', 'string', 'max:255'],
            'make_primary' => ['nullable', 'boolean'],
        ]);

        $modelNumber->fill([
            'code' => $data['code'],
            'label' => Arr::get($data, 'label'),
        ])->save();

        if ($request->boolean('make_primary')) {
            $model->forceFill([
                'primary_model_number_id' => $modelNumber->id,
                'model_number' => $modelNumber->code,
            ])->save();
        }

        return redirect()
            ->route('settings.model_numbers.index')
            ->with('success', __('Model number updated.'));
    }

    public function destroy(ModelNumber $modelNumber): RedirectResponse
    {
        $model = $modelNumber->model;
        $this->authorize('update', $model);

        if ($model->primary_model_number_id === $modelNumber->id) {
            return redirect()
                ->route('settings.model_numbers.index')
                ->with('error', __('Cannot delete the primary model number.'));
        }

        if ($modelNumber->assets()->exists()) {
            return redirect()
                ->route('settings.model_numbers.index')
                ->with('error', __('Cannot delete a model number that is in use by assets.'));
        }

        $modelNumber->delete();

        return redirect()
            ->route('settings.model_numbers.index')
            ->with('success', __('Model number removed.'));
    }

    public function makePrimary(ModelNumber $modelNumber): RedirectResponse
    {
        $model = $modelNumber->model;
        $this->authorize('update', $model);

        $model->forceFill([
            'primary_model_number_id' => $modelNumber->id,
            'model_number' => $modelNumber->code,
        ])->save();

        return redirect()
            ->route('settings.model_numbers.index')
            ->with('success', __('Primary model number updated.'));
    }
}
