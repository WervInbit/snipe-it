<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Transformers\SelectlistTransformer;
use App\Http\Transformers\SkusTransformer;
use App\Helpers\Helper;
use App\Models\Sku;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SkusController extends Controller
{
    public function index(Request $request): array
    {
        $this->authorize('view', Sku::class);

        $allowed_columns = ['id', 'name', 'model_id', 'created_at', 'updated_at'];
        $skus = Sku::select(['id', 'name', 'model_id', 'created_at', 'updated_at'])
            ->with('model');

        if ($request->filled('model_id')) {
            $skus->where('model_id', $request->input('model_id'));
        }

        if ($request->filled('search')) {
            $skus->where('name', 'like', '%' . $request->input('search') . '%');
        }

        $offset = (int) $request->input('offset', 0);
        $limit = (int) $request->input('limit', app('api_limit_value'));
        $order = $request->input('order') === 'desc' ? 'desc' : 'asc';
        $sort = in_array($request->input('sort'), $allowed_columns) ? $request->input('sort') : 'name';

        $total = $skus->count();
        $skus = $skus->orderBy($sort, $order)->skip($offset)->take($limit)->get();

        return (new SkusTransformer)->transformSkus($skus, $total);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', Sku::class);
        $sku = new Sku();
        $sku->fill($request->only('name', 'model_id'));
        if ($sku->save()) {
            return response()->json(Helper::formatStandardApiResponse('success', $sku, trans('admin/skus/message.create.success')));
        }
        return response()->json(Helper::formatStandardApiResponse('error', null, $sku->getErrors()));
    }

    public function show(Sku $sku): array
    {
        $this->authorize('view', Sku::class);
        return (new SkusTransformer)->transformSku($sku);
    }

    public function update(Request $request, Sku $sku): JsonResponse
    {
        $this->authorize('update', Sku::class);
        $sku->fill($request->only('name', 'model_id'));
        if ($sku->save()) {
            return response()->json(Helper::formatStandardApiResponse('success', $sku, trans('admin/skus/message.update.success')));
        }
        return response()->json(Helper::formatStandardApiResponse('error', null, $sku->getErrors()));
    }

    public function destroy(Sku $sku): JsonResponse
    {
        $this->authorize('delete', Sku::class);
        if ($sku->assets()->count() > 0) {
            return response()->json(Helper::formatStandardApiResponse('error', null, trans('admin/skus/message.assoc_assets')));
        }
        $sku->delete();
        return response()->json(Helper::formatStandardApiResponse('success', null, trans('admin/skus/message.delete.success')));
    }

    public function selectlist(Request $request) : array
    {
        $this->authorize('view.selectlists');
        $skus = Sku::select(['id', 'name'])
            ->where('model_id', $request->input('model_id'))
            ->orderBy('name', 'ASC')
            ->paginate(50);

        foreach ($skus as $sku) {
            $sku->use_text = $sku->name;
            $sku->use_image = null;
        }

        return (new SelectlistTransformer)->transformSelectlist($skus);
    }
}
