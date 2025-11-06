<?php

namespace App\Http\Controllers\Api;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAssetModelRequest;
use App\Http\Transformers\AssetModelsTransformer;
use App\Http\Transformers\AssetsTransformer;
use App\Http\Transformers\SelectlistTransformer;
use App\Models\Asset;
use App\Models\AssetModel;
use App\Models\ModelNumber;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\JsonResponse;

/**
 * This class controls all actions related to asset models for
 * the Snipe-IT Asset Management application.
 *
 * @version    v4.0
 * @author [A. Gianotto] [<snipe@snipe.net>]
 */
class AssetModelsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @author [A. Gianotto] [<snipe@snipe.net>]
     * @since [v4.0]
     */
    public function index(Request $request) : JsonResponse | array
    {
        $this->authorize('view', AssetModel::class);
        $allowed_columns =
            [
                'id',
                'image',
                'name',
                'model_number',
                'min_amt',
                'eol',
                'notes',
                'created_at',
                'manufacturer',
                'requestable',
                'assets_count',
                'category',
                'fieldset',
                'deleted_at',
                'updated_at',
            ];

        $assetmodels = AssetModel::select([
            'models.id',
            'models.image',
            'models.name',
            'models.model_number',
            'models.primary_model_number_id',
            'models.min_amt',
            'models.eol',
            'models.created_by',
            'models.requestable',
            'models.notes',
            'models.created_at',
            'models.category_id',
            'models.manufacturer_id',
            'models.depreciation_id',
            'models.fieldset_id',
            'models.deleted_at',
            'models.updated_at',
        ])
            ->with('category', 'depreciation', 'manufacturer', 'fieldset.fields.defaultValues', 'adminuser', 'primaryModelNumber')
            ->withCount(['assets', 'modelNumbers']);

        if ($request->input('status')=='deleted') {
            $assetmodels->onlyTrashed();
        }

        if ($request->filled('name')) {
            $assetmodels = $assetmodels->where('models.name', '=', $request->input('name'));
        }

        if ($request->filled('model_number')) {
            $assetmodels = $assetmodels->where('models.model_number', '=', $request->input('model_number'));
        }

        if ($request->input('requestable') == 'true') {
            $assetmodels = $assetmodels->where('models.requestable', '=', '1');
        } elseif ($request->input('requestable') == 'false') {
            $assetmodels = $assetmodels->where('models.requestable', '=', '0');
        }        

        if ($request->filled('notes')) {
            $assetmodels = $assetmodels->where('models.notes', '=', $request->input('notes'));
        }

        if ($request->filled('category_id')) {
            $assetmodels = $assetmodels->where('models.category_id', '=', $request->input('category_id'));
        }

        if ($request->filled('depreciation_id')) {
            $assetmodels = $assetmodels->where('models.depreciation_id', '=', $request->input('depreciation_id'));
        }

        if ($request->filled('search')) {
            $assetmodels->TextSearch($request->input('search'));
        }

        $limit = app('api_limit_value');

        $order = $request->input('order') === 'asc' ? 'asc' : 'desc';
        $sort = in_array($request->input('sort'), $allowed_columns) ? $request->input('sort') : 'models.created_at';

        switch ($request->input('sort')) {
            case 'manufacturer':
                $assetmodels->OrderManufacturer($order);
                break;
            case 'category':
                $assetmodels->OrderCategory($order);
                break;
            case 'fieldset':
                $assetmodels->OrderFieldset($order);
                break;
            case 'created_by':
                $assetmodels->OrderByCreatedByName($order);
                break;
            default:
                $assetmodels->orderBy($sort, $order);
                break;
        }

        $total = (clone $assetmodels)->count();
        $offset = $this->resolveOffset($request, $total, $limit);
        $assetmodels = $assetmodels->skip($offset)->take($limit)->get();

        return (new AssetModelsTransformer)->transformAssetModels($assetmodels, $total);
    }


    /**
     * Store a newly created resource in storage.
     *
     * @author [A. Gianotto] [<snipe@snipe.net>]
     * @since [v4.0]
     * @param  \App\Http\Requests\StoreAssetModelRequest  $request
     */
    public function store(StoreAssetModelRequest $request) : JsonResponse
    {
        $this->authorize('create', AssetModel::class);
        $payload = $request->all();
        $modelNumberInput = trim((string) ($payload['model_number'] ?? ''));
        $payload['model_number'] = $modelNumberInput !== '' ? $modelNumberInput : null;

        $assetmodel = new AssetModel;
        $assetmodel->fill($payload);
        $assetmodel = $request->handleImages($assetmodel);

        if ($assetmodel->save()) {
            $assetmodel->syncPrimaryModelNumber($payload['model_number'] ?? null);
            return response()->json(Helper::formatStandardApiResponse('success', (new AssetModelsTransformer)->transformAssetModel($assetmodel), trans('admin/models/message.create.success')));
        }
        return response()->json(Helper::formatStandardApiResponse('error', null, $assetmodel->getErrors()));


    }

    /**
     * Display the specified resource.
     *
     * @author [A. Gianotto] [<snipe@snipe.net>]
     * @since [v4.0]
     * @param  int  $id
     */
    public function show($id) :  array
    {
        $this->authorize('view', AssetModel::class);
        $assetmodel = AssetModel::withCount('assets as assets_count')->findOrFail($id);

        return (new AssetModelsTransformer)->transformAssetModel($assetmodel);
    }

    /**
     * Display the specified resource's assets
     *
     * @author [A. Gianotto] [<snipe@snipe.net>]
     * @since [v4.0]
     * @param  int  $id
     */
    public function assets($id) : array
    {
        $this->authorize('view', AssetModel::class);
        $assets = Asset::where('model_id', '=', $id)->get();

        return (new AssetsTransformer)->transformAssets($assets, $assets->count());
    }


    /**
     * Update the specified resource in storage.
     *
     * @author [A. Gianotto] [<snipe@snipe.net>]
     * @since [v4.0]
     * @param  \App\Http\Requests\ImageUploadRequest  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(StoreAssetModelRequest $request, $id) : JsonResponse
    {
        $this->authorize('update', AssetModel::class);
        $assetmodel = AssetModel::findOrFail($id);
        $payload = $request->all();
        $modelNumberInput = trim((string) ($payload['model_number'] ?? ''));
        $payload['model_number'] = $modelNumberInput !== '' ? $modelNumberInput : null;

        $assetmodel->fill($payload);
        $assetmodel = $request->handleImages($assetmodel);

        /**
         * Allow custom_fieldset_id to override and populate fieldset_id.
         * This is stupid, but required for legacy API support.
         *
         * I have no idea why we manually overrode that field name
         * in previous versions. I assume there was a good reason for
         * it, but I'll be damned if I can think of one. - snipe
         */
        if ($request->filled('custom_fieldset_id')) {
            $assetmodel->fieldset_id = $request->get('custom_fieldset_id');
        }


        if ($assetmodel->save()) {
            $assetmodel->syncPrimaryModelNumber($payload['model_number'] ?? null);
            return response()->json(Helper::formatStandardApiResponse('success', (new AssetModelsTransformer)->transformAssetModel($assetmodel), trans('admin/models/message.update.success')));
        }

        return response()->json(Helper::formatStandardApiResponse('error', null, $assetmodel->getErrors()));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @author [A. Gianotto] [<snipe@snipe.net>]
     * @since [v4.0]
     * @param  int  $id
     */
    public function destroy($id) : JsonResponse
    {
        $this->authorize('delete', AssetModel::class);
        $assetmodel = AssetModel::findOrFail($id);
        $this->authorize('delete', $assetmodel);

        if ($assetmodel->assets()->count() > 0) {
            return response()->json(Helper::formatStandardApiResponse('error', null, trans('admin/models/message.assoc_users')));
        }

        if ($assetmodel->image) {
            try {
                Storage::disk('public')->delete('assetmodels/'.$assetmodel->image);
            } catch (\Exception $e) {
                Log::info($e);
            }
        }

        $assetmodel->delete();

        return response()->json(Helper::formatStandardApiResponse('success', null, trans('admin/models/message.delete.success')));
    }

    /**
     * Gets a paginated collection for the select2 menus
     *
     * @author [A. Gianotto] [<snipe@snipe.net>]
     * @since [v4.0.16]
     * @see \App\Http\Transformers\SelectlistTransformer
     */
    public function selectlist(Request $request) : array
    {

        $this->authorize('view.selectlists');

        $settings = Setting::getSettings();

        $modelNumbers = ModelNumber::query()
            ->with(['model' => function ($query) {
                $query->with('manufacturer', 'category');
            }])
            ->whereHas('model', function ($query) {
                $query->whereNull('models.deleted_at');
            });

        if (!$request->boolean('include_deprecated')) {
            $modelNumbers->active();
        }

        if ($request->filled('category_id')) {
            $modelNumbers->whereHas('model', function ($query) use ($request) {
                $query->where('models.category_id', $request->input('category_id'));
            });
        }

        if ($request->filled('manufacturer_id')) {
            $modelNumbers->whereHas('model', function ($query) use ($request) {
                $query->where('models.manufacturer_id', $request->input('manufacturer_id'));
            });
        }

        if ($request->filled('search')) {
            $term = $request->input('search');
            $modelNumbers->where(function ($query) use ($term) {
                $query->where('model_numbers.label', 'LIKE', '%'.$term.'%')
                    ->orWhere('model_numbers.code', 'LIKE', '%'.$term.'%')
                    ->orWhereHas('model', function ($modelQuery) use ($term) {
                        $modelQuery->where('models.name', 'LIKE', '%'.$term.'%')
                            ->orWhereHas('manufacturer', function ($manufacturerQuery) use ($term) {
                                $manufacturerQuery->where('manufacturers.name', 'LIKE', '%'.$term.'%');
                            })
                            ->orWhereHas('category', function ($categoryQuery) use ($term) {
                                $categoryQuery->where('categories.name', 'LIKE', '%'.$term.'%');
                            });
                    });
            });
        }

        $modelNumbers->orderBy('model_numbers.label')->orderBy('model_numbers.code');

        $paginated = $modelNumbers->paginate(50);

        $transformedCollection = $paginated->getCollection()->map(function (ModelNumber $modelNumber) use ($settings) {
            $model = $modelNumber->model;

            if (!$model) {
                return null;
            }

            $segments = [];

            if ($settings->modellistCheckedValue('category') && $model->category) {
                $segments[] = $model->category->name;
            }

            if ($settings->modellistCheckedValue('manufacturer') && $model->manufacturer) {
                $segments[] = $model->manufacturer->name;
            }

            $label = $modelNumber->label ?: $modelNumber->code;

            $display = $model->name;
            if ($label) {
                $display .= ' — '.$label;
            }

            if ($modelNumber->isDeprecated()) {
                $display .= ' ('.trans('general.deprecated').')';
            }

            if (!empty($segments)) {
                $display = implode(' - ', $segments).' '.$display;
            }

            $modelNumber->use_text = $display;
            $modelNumber->use_image = ($settings->modellistCheckedValue('image') && $model->image)
                ? Storage::disk('public')->url('models/'.e($model->image))
                : null;

            $modelNumber->selectlist_id = $model->id.':'.$modelNumber->id;
            $modelNumber->selectlist_meta = [
                'model_id' => $model->id,
                'model_number_id' => $modelNumber->id,
                'model_name' => $model->name,
                'model_number_label' => $label,
                'is_deprecated' => $modelNumber->isDeprecated(),
            ];

            return $modelNumber;
        })->filter();

        $paginated->setCollection($transformedCollection->values());

        return (new SelectlistTransformer)->transformSelectlist($paginated);
    }

}
