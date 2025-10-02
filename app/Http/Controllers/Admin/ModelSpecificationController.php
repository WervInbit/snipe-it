<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ModelSpecificationRequest;
use App\Models\AssetModel;
use App\Models\ModelNumber;
use App\Services\ModelAttributes\EffectiveAttributeResolver;
use App\Services\ModelAttributes\ModelAttributeManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ModelSpecificationController extends Controller
{
    public function __construct(
        private readonly EffectiveAttributeResolver $resolver,
        private readonly ModelAttributeManager $attributeManager
    ) {
    }

    public function edit(Request $request, AssetModel $model): View
    {
        $this->authorize('update', $model);

        $model->loadMissing([
            'category',
            'modelNumbers' => fn ($query) => $query->orderBy('code'),
        ]);

        $modelNumbers = $model->modelNumbers;

        if ($modelNumbers->isEmpty()) {
            return view('models.spec', [
                'model' => $model,
                'item' => $model,
                'modelNumber' => null,
                'modelNumbers' => $modelNumbers,
                'attributes' => collect(),
            ]);
        }

        $modelNumberId = (int) $request->input('model_number_id');
        /** @var ModelNumber|null $modelNumber */
        $modelNumber = $modelNumbers->firstWhere('id', $modelNumberId);

        if (!$modelNumber) {
            $modelNumber = $model->primaryModelNumber ?? $modelNumbers->first();
        }

        $resolved = $this->resolver->resolveForModelNumber($modelNumber);

        return view('models.spec', [
            'model' => $model,
            'item' => $model,
            'modelNumber' => $modelNumber,
            'modelNumbers' => $modelNumbers,
            'attributes' => $resolved,
        ]);
    }

    public function update(ModelSpecificationRequest $request, AssetModel $model): RedirectResponse
    {
        $this->authorize('update', $model);

        $modelNumberId = (int) $request->input('model_number_id');
        $modelNumber = $modelNumberId
            ? $model->modelNumbers()->whereKey($modelNumberId)->first()
            : $model->primaryModelNumber;

        if (!$modelNumber) {
            return redirect()
                ->route('models.spec.edit', $model)
                ->with('error', __('Add a model number before editing the specification.'));
        }

        $this->attributeManager->saveModelAttributes($modelNumber, $request->input('attributes', []));

        return redirect()
            ->route('models.spec.edit', ['model' => $model, 'model_number_id' => $modelNumber->id])
            ->with('success', __('Model specification updated.'));
    

public function editForNumber(Request $request, AssetModel $model, ModelNumber $modelNumber): View
{
    $this->ensureModelNumber($model, $modelNumber);

    $request = $request->duplicate(array_merge($request->all(), [
        'model_number_id' => $modelNumber->id,
    ]));

    return $this->edit($request, $model);
}

public function updateForNumber(ModelSpecificationRequest $request, AssetModel $model, ModelNumber $modelNumber): RedirectResponse
{
    $this->ensureModelNumber($model, $modelNumber);

    $request = $request->duplicate(array_merge($request->all(), [
        'model_number_id' => $modelNumber->id,
    ]));

    return $this->update($request, $model);
}

private function ensureModelNumber(AssetModel $model, ModelNumber $candidate): void
{
    if ($candidate->model_id !== $model->id) {
        abort(404);
    }
}

}
