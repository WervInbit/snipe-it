<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AssetModel;
use App\Models\ModelNumber;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ModelNumberController extends Controller
{
    public function create(AssetModel $model): View
    {
        $this->authorize('update', $model);

        return view('models.model_numbers.create', [
            'model' => $model,
            'item' => new ModelNumber(),
        ]);
    }

    public function store(Request $request, AssetModel $model): RedirectResponse
    {
        $this->authorize('update', $model);

        $data = $this->validate($request, [
            'code' => [
                'required',
                'string',
                'max:255',
                Rule::unique('model_numbers', 'code')->where('model_id', $model->id),
            ],
            'label' => ['nullable', 'string', 'max:255'],
            'make_primary' => ['nullable', 'boolean'],
        ]);

        $modelNumber = $model->modelNumbers()->create([
            'code' => $data['code'],
            'label' => $data['label'] ?? null,
        ]);

        if ($request->boolean('make_primary') || !$model->primary_model_number_id) {
            $this->setPrimaryModelNumber($model, $modelNumber);
        }

        return redirect()
            ->route('models.show', $model)
            ->with('success', __('Model number added.'));
    }

    public function edit(AssetModel $model, ModelNumber $modelNumber): View
    {
        $this->authorize('update', $model);
        $this->ensureModelNumber($model, $modelNumber);

        return view('models.model_numbers.edit', [
            'model' => $model,
            'modelNumber' => $modelNumber,
            'item' => $modelNumber,
        ]);
    }

    public function update(Request $request, AssetModel $model, ModelNumber $modelNumber): RedirectResponse
    {
        $this->authorize('update', $model);
        $this->ensureModelNumber($model, $modelNumber);

        $data = $this->validate($request, [
            'code' => [
                'required',
                'string',
                'max:255',
                Rule::unique('model_numbers', 'code')
                    ->ignore($modelNumber->id)
                    ->where('model_id', $model->id),
            ],
            'label' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'in:active,deprecated'],
            'make_primary' => ['nullable', 'boolean'],
        ]);

        if ($data['status'] === 'deprecated' && $model->primary_model_number_id === $modelNumber->id) {
            return redirect()
                ->route('models.numbers.edit', [$model, $modelNumber])
                ->withInput()
                ->with('error', __('Cannot deprecate the primary model number.'));
        }

        $modelNumber->fill([
            'code' => $data['code'],
            'label' => $data['label'] ?? null,
        ])->save();

        if ($data['status'] === 'deprecated') {
            $modelNumber->deprecate();
        } else {
            $modelNumber->restoreStatus();
        }

        if ($request->boolean('make_primary')) {
            $this->setPrimaryModelNumber($model, $modelNumber);
        }

        return redirect()
            ->route('models.show', $model)
            ->with('success', __('Model number updated.'));
    }


    public function deprecate(AssetModel $model, ModelNumber $modelNumber): RedirectResponse
    {
        $this->authorize('update', $model);
        $this->ensureModelNumber($model, $modelNumber);

        if ($model->primary_model_number_id === $modelNumber->id) {
            return redirect()
                ->route('models.show', $model)
                ->with('error', __('Cannot deprecate the primary model number.'));
        }

        $modelNumber->deprecate();

        return redirect()
            ->route('models.show', $model)
            ->with('success', __('Model number deprecated.'));
    }

    public function restore(AssetModel $model, ModelNumber $modelNumber): RedirectResponse
    {
        $this->authorize('update', $model);
        $this->ensureModelNumber($model, $modelNumber);

        $modelNumber->restoreStatus();

        return redirect()
            ->route('models.show', $model)
            ->with('success', __('Model number restored.'));
    }

    public function destroy(AssetModel $model, ModelNumber $modelNumber): RedirectResponse
    {
        $this->authorize('update', $model);
        $this->ensureModelNumber($model, $modelNumber);

        if ($model->primary_model_number_id === $modelNumber->id) {
            return redirect()
                ->route('models.show', $model)
                ->with('error', __('Cannot delete the primary model number.'));
        }

        if ($modelNumber->assets()->exists()) {
            return redirect()
                ->route('models.show', $model)
                ->with('error', __('Cannot delete a model number that is in use by assets.'));
        }

        $modelNumber->delete();

        return redirect()
            ->route('models.show', $model)
            ->with('success', __('Model number removed.'));
    }

    public function makePrimary(AssetModel $model, ModelNumber $modelNumber): RedirectResponse
    {
        $this->authorize('update', $model);
        $this->ensureModelNumber($model, $modelNumber);

        if ($modelNumber->isDeprecated()) {
            $modelNumber->restoreStatus();
        }

        $this->setPrimaryModelNumber($model, $modelNumber);

        return redirect()
            ->route('models.show', $model)
            ->with('success', __('Primary model number updated.'));
    }

    private function ensureModelNumber(AssetModel $model, ModelNumber $candidate): void
    {
        if ($candidate->model_id !== $model->id) {
            abort(404);
        }
    }

    private function setPrimaryModelNumber(AssetModel $model, ModelNumber $modelNumber): void
    {
        $model->newQuery()
            ->whereKey($model->id)
            ->update([
                'primary_model_number_id' => $modelNumber->id,
                'model_number' => $modelNumber->code,
            ]);

        $model->forceFill([
            'primary_model_number_id' => $modelNumber->id,
            'model_number' => $modelNumber->code,
        ]);

        $model->setRelation('primaryModelNumber', $modelNumber);

        $model->assets()
            ->whereNull('model_number_id')
            ->update(['model_number_id' => $modelNumber->id]);

        $this->syncLegacyModelNumber($model, $modelNumber);
    }

    private function syncLegacyModelNumber(AssetModel $model, ModelNumber $modelNumber): void
    {
        if ($model->model_number !== $modelNumber->code) {
            $model->forceFill(['model_number' => $modelNumber->code])->save();
        }
    }
}

