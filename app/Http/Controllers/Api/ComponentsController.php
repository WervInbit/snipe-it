<?php

namespace App\Http\Controllers\Api;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Http\Transformers\ComponentsTransformer;
use App\Http\Transformers\DatatablesTransformer;
use App\Http\Transformers\SelectlistTransformer;
use App\Models\Asset;
use App\Models\ComponentInstance;
use App\Models\ComponentStorageLocation;
use App\Models\Location;
use App\Services\ComponentLifecycleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

class ComponentsController extends Controller
{
    public function __construct(
        protected ComponentLifecycleService $lifecycle,
    ) {
    }

    public function index(Request $request): JsonResponse|array
    {
        $this->authorize('view', ComponentInstance::class);

        $allowedColumns = [
            'id',
            'component_tag',
            'display_name',
            'serial',
            'status',
            'condition_code',
            'source_type',
            'installed_as',
            'received_at',
            'created_at',
            'updated_at',
        ];

        $components = ComponentInstance::query()
            ->with([
                'componentDefinition.category',
                'componentDefinition.manufacturer',
                'company',
                'sourceAsset.model',
                'currentAsset.model',
                'storageLocation.siteLocation',
                'heldBy',
                'supplier',
                'createdBy',
            ]);

        $this->applyFilters($components, $request);

        $limit = app('api_limit_value');
        $order = $request->input('order') === 'asc' ? 'asc' : 'desc';
        $sort = in_array($request->input('sort'), $allowedColumns, true)
            ? $request->input('sort')
            : 'updated_at';

        $components->orderBy($sort, $order);

        $total = $components->count();
        $offset = $this->resolveOffset($request, $total, $limit);
        $components = $components->skip($offset)->take($limit)->get();

        return (new ComponentsTransformer())->transformComponents($components, $total);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', ComponentInstance::class);

        $validator = Validator::make($request->all(), $this->createRules());

        if ($validator->fails()) {
            return response()->json(Helper::formatStandardApiResponse('error', null, $validator->errors()), 422);
        }

        $component = $this->lifecycle->createInstance($this->payloadFromRequest($request), $request->user());

        return response()->json(Helper::formatStandardApiResponse(
            'success',
            (new ComponentsTransformer())->transformComponent($component->fresh($this->showRelations())),
            'Component created.'
        ));
    }

    public function show(ComponentInstance $component_id): array
    {
        $this->authorize('view', $component_id);

        return (new ComponentsTransformer())->transformComponent(
            $component_id->load($this->showRelations())
        );
    }

    public function update(Request $request, ComponentInstance $component_id): JsonResponse
    {
        $this->authorize('update', $component_id);

        $validator = Validator::make($request->all(), $this->updateRules($component_id));
        $this->rejectLifecycleMutationFields($request, $validator);

        if ($validator->fails()) {
            return response()->json(Helper::formatStandardApiResponse('error', null, $validator->errors()), 422);
        }

        $component_id->fill($this->metadataPayloadFromRequest($request));
        $component_id->updated_by = $request->user()?->id;
        $component_id->save();

        return response()->json(Helper::formatStandardApiResponse(
            'success',
            (new ComponentsTransformer())->transformComponent($component_id->fresh($this->showRelations())),
            'Component updated.'
        ));
    }

    public function destroy(ComponentInstance $component_id): JsonResponse
    {
        $this->authorize('delete', $component_id);

        if ($component_id->status === ComponentInstance::STATUS_INSTALLED) {
            return response()->json(Helper::formatStandardApiResponse(
                'error',
                null,
                'Installed components must be removed before deletion.'
            ), 422);
        }

        $component_id->delete();

        return response()->json(Helper::formatStandardApiResponse('success', null, 'Component deleted.'));
    }

    public function removeToTray(Request $request, ComponentInstance $component_id): JsonResponse
    {
        $this->authorize('move', $component_id);

        $validator = Validator::make($request->all(), [
            'note' => ['nullable', 'string'],
            'related_work_order_id' => ['nullable', 'integer', 'exists:work_orders,id'],
            'related_work_order_task_id' => ['nullable', 'integer', 'exists:work_order_tasks,id'],
        ]);

        if ($validator->fails()) {
            return response()->json(Helper::formatStandardApiResponse('error', null, $validator->errors()), 422);
        }

        try {
            $component = $this->lifecycle->removeToTray(
                $component_id,
                $request->user(),
                $request->only(['note', 'related_work_order_id', 'related_work_order_task_id'])
            );
        } catch (InvalidArgumentException $exception) {
            return $this->lifecycleErrorResponse($exception->getMessage());
        }

        return response()->json(Helper::formatStandardApiResponse(
            'success',
            (new ComponentsTransformer())->transformComponent($component->fresh($this->showRelations())),
            'Component moved to tray.'
        ));
    }

    public function install(Request $request, ComponentInstance $component_id): JsonResponse
    {
        $this->authorize('install', $component_id);

        $validator = Validator::make($request->all(), [
            'asset_id' => ['required', 'integer', 'exists:assets,id'],
            'installed_as' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string'],
            'related_work_order_id' => ['nullable', 'integer', 'exists:work_orders,id'],
            'related_work_order_task_id' => ['nullable', 'integer', 'exists:work_order_tasks,id'],
        ]);

        if ($validator->fails()) {
            return response()->json(Helper::formatStandardApiResponse('error', null, $validator->errors()), 422);
        }

        try {
            $asset = Asset::findOrFail($request->input('asset_id'));
            $component = $this->lifecycle->installIntoAsset($component_id, $asset, [
                'performed_by' => $request->user(),
                'installed_as' => $request->input('installed_as'),
                'note' => $request->input('note'),
                'related_work_order_id' => $request->input('related_work_order_id'),
                'related_work_order_task_id' => $request->input('related_work_order_task_id'),
            ]);
        } catch (InvalidArgumentException $exception) {
            return $this->lifecycleErrorResponse($exception->getMessage());
        }

        return response()->json(Helper::formatStandardApiResponse(
            'success',
            (new ComponentsTransformer())->transformComponent($component->fresh($this->showRelations())),
            'Component installed.'
        ));
    }

    public function moveToStock(Request $request, ComponentInstance $component_id): JsonResponse
    {
        $this->authorize('move', $component_id);

        $validator = Validator::make($request->all(), [
            'storage_location_id' => ['required', 'integer', 'exists:component_storage_locations,id'],
            'needs_verification' => ['nullable', 'boolean'],
            'verification_location_id' => ['nullable', 'integer', 'exists:component_storage_locations,id'],
            'note' => ['nullable', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json(Helper::formatStandardApiResponse('error', null, $validator->errors()), 422);
        }

        try {
            $location = ComponentStorageLocation::findOrFail($request->input('storage_location_id'));
            $verificationLocation = $request->filled('verification_location_id')
                ? ComponentStorageLocation::findOrFail($request->input('verification_location_id'))
                : $location;

            $component = $this->lifecycle->moveToStock($component_id, $location, [
                'performed_by' => $request->user(),
                'needs_verification' => $request->boolean('needs_verification'),
                'storage_location' => $verificationLocation,
                'note' => $request->input('note'),
            ]);
        } catch (InvalidArgumentException $exception) {
            return $this->lifecycleErrorResponse($exception->getMessage());
        }

        return response()->json(Helper::formatStandardApiResponse(
            'success',
            (new ComponentsTransformer())->transformComponent($component->fresh($this->showRelations())),
            'Component moved.'
        ));
    }

    public function flagNeedsVerification(Request $request, ComponentInstance $component_id): JsonResponse
    {
        $this->authorize('verify', $component_id);

        $validator = Validator::make($request->all(), [
            'storage_location_id' => ['nullable', 'integer', 'exists:component_storage_locations,id'],
            'note' => ['nullable', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json(Helper::formatStandardApiResponse('error', null, $validator->errors()), 422);
        }

        try {
            $location = $request->filled('storage_location_id')
                ? ComponentStorageLocation::findOrFail($request->input('storage_location_id'))
                : $component_id->storageLocation;

            $component = $this->lifecycle->flagNeedsVerification($component_id, [
                'performed_by' => $request->user(),
                'storage_location' => $location,
                'note' => $request->input('note'),
            ]);
        } catch (InvalidArgumentException $exception) {
            return $this->lifecycleErrorResponse($exception->getMessage());
        }

        return response()->json(Helper::formatStandardApiResponse(
            'success',
            (new ComponentsTransformer())->transformComponent($component->fresh($this->showRelations())),
            'Verification required.'
        ));
    }

    public function confirmVerification(Request $request, ComponentInstance $component_id): JsonResponse
    {
        $this->authorize('verify', $component_id);

        $validator = Validator::make($request->all(), [
            'storage_location_id' => ['required', 'integer', 'exists:component_storage_locations,id'],
            'note' => ['nullable', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json(Helper::formatStandardApiResponse('error', null, $validator->errors()), 422);
        }

        try {
            $location = ComponentStorageLocation::findOrFail($request->input('storage_location_id'));
            $component = $this->lifecycle->confirmVerification($component_id, $location, [
                'performed_by' => $request->user(),
                'note' => $request->input('note'),
            ]);
        } catch (InvalidArgumentException $exception) {
            return $this->lifecycleErrorResponse($exception->getMessage());
        }

        return response()->json(Helper::formatStandardApiResponse(
            'success',
            (new ComponentsTransformer())->transformComponent($component->fresh($this->showRelations())),
            'Verification confirmed.'
        ));
    }

    public function markDestructionPending(Request $request, ComponentInstance $component_id): JsonResponse
    {
        $this->authorize('move', $component_id);

        $validator = Validator::make($request->all(), [
            'storage_location_id' => ['nullable', 'integer', 'exists:component_storage_locations,id'],
            'note' => ['nullable', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json(Helper::formatStandardApiResponse('error', null, $validator->errors()), 422);
        }

        try {
            $location = $request->filled('storage_location_id')
                ? ComponentStorageLocation::findOrFail($request->input('storage_location_id'))
                : null;

            $component = $this->lifecycle->markDestructionPending($component_id, $location, [
                'performed_by' => $request->user(),
                'note' => $request->input('note'),
            ]);
        } catch (InvalidArgumentException $exception) {
            return $this->lifecycleErrorResponse($exception->getMessage());
        }

        return response()->json(Helper::formatStandardApiResponse(
            'success',
            (new ComponentsTransformer())->transformComponent($component->fresh($this->showRelations())),
            'Component marked for destruction.'
        ));
    }

    public function markDestroyed(Request $request, ComponentInstance $component_id): JsonResponse
    {
        $this->authorize('move', $component_id);

        $validator = Validator::make($request->all(), [
            'note' => ['nullable', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json(Helper::formatStandardApiResponse('error', null, $validator->errors()), 422);
        }

        try {
            $component = $this->lifecycle->markDestroyed($component_id, [
                'performed_by' => $request->user(),
                'note' => $request->input('note'),
            ]);
        } catch (InvalidArgumentException $exception) {
            return $this->lifecycleErrorResponse($exception->getMessage());
        }

        return response()->json(Helper::formatStandardApiResponse(
            'success',
            (new ComponentsTransformer())->transformComponent($component->fresh($this->showRelations())),
            'Component destroyed.'
        ));
    }

    public function getAssets(Request $request, ComponentInstance $component_id): array
    {
        $this->authorize('view', $component_id);

        $events = $component_id->events()
            ->with(['fromAsset.model', 'toAsset.model', 'performedBy'])
            ->where(function ($query) {
                $query->whereNotNull('from_asset_id')->orWhereNotNull('to_asset_id');
            })
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($event) {
                return [
                    'id' => $event->id,
                    'event_type' => $event->event_type,
                    'from_asset' => $event->fromAsset ? [
                        'id' => (int) $event->fromAsset->id,
                        'name' => e($event->fromAsset->present()->name()),
                    ] : null,
                    'to_asset' => $event->toAsset ? [
                        'id' => (int) $event->toAsset->id,
                        'name' => e($event->toAsset->present()->name()),
                    ] : null,
                    'performed_by' => $event->performedBy ? [
                        'id' => (int) $event->performedBy->id,
                        'name' => e($event->performedBy->present()->fullName()),
                    ] : null,
                    'note' => $event->note,
                    'created_at' => Helper::getFormattedDateObject($event->created_at, 'datetime'),
                ];
            });

        return (new DatatablesTransformer())->transformDatatables($events->all(), $events->count());
    }

    public function selectlist(Request $request): array
    {
        $this->authorize('view', ComponentInstance::class);

        $components = ComponentInstance::query()
            ->select([
                'component_instances.id',
                'component_instances.component_tag',
                'component_instances.display_name',
                'component_instances.serial',
                'component_instances.status',
            ]);

        if ($request->filled('search')) {
            $search = '%'.$request->get('search').'%';
            $components->where(function ($query) use ($search): void {
                $query->where('component_tag', 'LIKE', $search)
                    ->orWhere('display_name', 'LIKE', $search)
                    ->orWhere('serial', 'LIKE', $search);
            });
        }

        $components = $components->orderBy('component_tag')->paginate(50);
        $components->setCollection($components->getCollection()->map(function (ComponentInstance $component) {
            $component->use_text = trim($component->component_tag.' '.$component->display_name);
            $component->selectlist_meta = [
                'status' => $component->status,
                'serial' => $component->serial,
            ];

            return $component;
        }));

        return (new SelectlistTransformer())->transformSelectlist($components);
    }

    protected function createRules(?ComponentInstance $component = null): array
    {
        $ignoreId = $component?->id;

        return [
            'component_definition_id' => ['nullable', 'integer', 'exists:component_definitions,id'],
            'company_id' => ['nullable', 'integer', 'exists:companies,id'],
            'component_tag' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('component_instances', 'component_tag')->ignore($ignoreId),
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if ($value && Asset::withTrashed()->where('asset_tag', $value)->exists()) {
                        $fail('Component tags must be globally unique and cannot overlap with asset tags.');
                    }
                },
            ],
            'display_name' => ['required_without:component_definition_id', 'nullable', 'string', 'max:255'],
            'serial' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::in(config('components.statuses', []))],
            'condition_code' => ['nullable', Rule::in([
                ComponentInstance::CONDITION_UNKNOWN,
                ComponentInstance::CONDITION_GOOD,
                ComponentInstance::CONDITION_FAIR,
                ComponentInstance::CONDITION_POOR,
                ComponentInstance::CONDITION_BROKEN,
            ])],
            'source_type' => ['nullable', Rule::in([
                ...array_keys(ComponentInstance::sourceTypeOptions()),
            ])],
            'source_asset_id' => ['nullable', 'integer', 'exists:assets,id'],
            'current_asset_id' => ['nullable', 'integer', 'exists:assets,id'],
            'storage_location_id' => ['nullable', 'integer', 'exists:component_storage_locations,id'],
            'held_by_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'installed_as' => ['nullable', 'string', 'max:255'],
            'supplier_id' => ['nullable', 'integer', 'exists:suppliers,id'],
            'purchase_cost' => ['nullable', 'numeric', 'gte:0'],
            'received_at' => ['nullable', 'date'],
            'metadata_json' => ['nullable', 'array'],
            'notes' => ['nullable', 'string'],
        ];
    }

    protected function updateRules(?ComponentInstance $component = null): array
    {
        return [
            'component_definition_id' => ['nullable', 'integer', 'exists:component_definitions,id'],
            'display_name' => ['nullable', 'string', 'max:255'],
            'serial' => ['nullable', 'string', 'max:255'],
            'supplier_id' => ['nullable', 'integer', 'exists:suppliers,id'],
            'purchase_cost' => ['nullable', 'numeric', 'gte:0'],
            'received_at' => ['nullable', 'date'],
            'metadata_json' => ['nullable', 'array'],
            'notes' => ['nullable', 'string'],
        ];
    }

    protected function payloadFromRequest(Request $request, bool $forUpdate = false): array
    {
        $payload = $request->only([
            'component_definition_id',
            'company_id',
            'component_tag',
            'display_name',
            'serial',
            'status',
            'condition_code',
            'source_type',
            'source_asset_id',
            'current_asset_id',
            'storage_location_id',
            'held_by_user_id',
            'installed_as',
            'supplier_id',
            'purchase_cost',
            'received_at',
            'metadata_json',
            'notes',
        ]);

        if (!$forUpdate) {
            $payload['status'] = $payload['status'] ?? ComponentInstance::STATUS_IN_STOCK;
            $payload['condition_code'] = $payload['condition_code'] ?? ComponentInstance::CONDITION_UNKNOWN;
            $payload['source_type'] = $payload['source_type'] ?? ComponentInstance::SOURCE_MANUAL;
        }

        return $payload;
    }

    protected function metadataPayloadFromRequest(Request $request): array
    {
        return $request->only([
            'component_definition_id',
            'display_name',
            'serial',
            'supplier_id',
            'purchase_cost',
            'received_at',
            'metadata_json',
            'notes',
        ]);
    }

    protected function rejectLifecycleMutationFields(Request $request, $validator): void
    {
        $message = __('Lifecycle state must be changed via the dedicated component lifecycle endpoints.');

        $validator->after(function ($validator) use ($request, $message): void {
            foreach ($this->lifecycleMutationFields() as $field) {
                if ($request->exists($field)) {
                    $validator->errors()->add($field, $message);
                }
            }
        });
    }

    protected function lifecycleMutationFields(): array
    {
        return [
            'status',
            'current_asset_id',
            'storage_location_id',
            'held_by_user_id',
            'transfer_started_at',
            'needs_verification_at',
            'last_verified_at',
            'installed_as',
            'destroyed_at',
        ];
    }

    protected function lifecycleErrorResponse(string $message): JsonResponse
    {
        return response()->json(Helper::formatStandardApiResponse('error', null, $message), 422);
    }

    protected function applyFilters($query, Request $request): void
    {
        if ($request->filled('search')) {
            $search = '%'.$request->input('search').'%';
            $query->where(function ($searchQuery) use ($search): void {
                $searchQuery->where('component_tag', 'LIKE', $search)
                    ->orWhere('display_name', 'LIKE', $search)
                    ->orWhere('serial', 'LIKE', $search)
                    ->orWhere('installed_as', 'LIKE', $search)
                    ->orWhereHas('componentDefinition', function ($definitionQuery) use ($search): void {
                        $definitionQuery->where('name', 'LIKE', $search)
                            ->orWhere('part_code', 'LIKE', $search)
                            ->orWhere('model_number', 'LIKE', $search);
                    })
                    ->orWhereHas('sourceAsset', function ($assetQuery) use ($search): void {
                        $assetQuery->where('asset_tag', 'LIKE', $search)
                            ->orWhere('name', 'LIKE', $search)
                            ->orWhere('serial', 'LIKE', $search);
                    })
                    ->orWhereHas('currentAsset', function ($assetQuery) use ($search): void {
                        $assetQuery->where('asset_tag', 'LIKE', $search)
                            ->orWhere('name', 'LIKE', $search)
                            ->orWhere('serial', 'LIKE', $search);
                    });
            });
        }

        foreach (['status', 'source_type', 'company_id', 'source_asset_id', 'current_asset_id', 'held_by_user_id', 'storage_location_id'] as $filter) {
            if ($request->filled($filter)) {
                $query->where($filter, $request->input($filter));
            }
        }

        if ($request->filled('component_definition_id')) {
            $query->where('component_definition_id', $request->input('component_definition_id'));
        }

        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->input('supplier_id'));
        }

        if ($request->filled('manufacturer_id')) {
            $query->whereHas('componentDefinition', function ($definitionQuery) use ($request): void {
                $definitionQuery->where('manufacturer_id', $request->input('manufacturer_id'));
            });
        }

        if ($request->filled('location_id')) {
            $locationIds = Location::getLocationHierarchy((int) $request->input('location_id'));
            $query->whereHas('storageLocation', function ($locationQuery) use ($locationIds): void {
                $locationQuery->whereIn('site_location_id', $locationIds);
            });
        }

        if ($request->filled('category_id')) {
            $query->whereHas('componentDefinition', function ($definitionQuery) use ($request): void {
                $definitionQuery->where('category_id', $request->input('category_id'));
            });
        }

        if ($request->boolean('needs_verification')) {
            $query->where('status', ComponentInstance::STATUS_NEEDS_VERIFICATION);
        }
    }

    protected function showRelations(): array
    {
        return [
            'componentDefinition.category',
            'componentDefinition.manufacturer',
            'company',
            'sourceAsset.model',
            'currentAsset.model',
            'storageLocation.siteLocation',
            'heldBy',
            'supplier',
            'createdBy',
            'updatedBy',
            'events.performedBy',
            'events.fromAsset.model',
            'events.toAsset.model',
            'events.fromStorageLocation',
            'events.toStorageLocation',
            'events.relatedWorkOrder',
            'events.relatedWorkOrderTask',
            'uploads.adminuser',
        ];
    }
}
