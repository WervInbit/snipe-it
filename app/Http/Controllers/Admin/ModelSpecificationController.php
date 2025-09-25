<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ModelSpecificationRequest;
use App\Models\AssetModel;
use App\Services\ModelAttributes\EffectiveAttributeResolver;
use App\Services\ModelAttributes\ModelAttributeManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ModelSpecificationController extends Controller
{
    public function __construct(
        private readonly EffectiveAttributeResolver $resolver,
        private readonly ModelAttributeManager $attributeManager
    ) {
    }

    public function edit(AssetModel $model): View
    {
        $this->authorize('update', $model);

        $resolved = $this->resolver->resolveForModel($model);

        return view('models.spec', [
            'model' => $model->loadMissing('category'),
            'attributes' => $resolved,
        ]);
    }

    public function update(ModelSpecificationRequest $request, AssetModel $model): RedirectResponse
    {
        $this->authorize('update', $model);

        $this->attributeManager->saveModelAttributes($model, $request->input('attributes', []));

        return redirect()
            ->route('models.spec.edit', $model)
            ->with('success', __('Model specification updated.'));
    }
}
