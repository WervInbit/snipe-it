<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\ComponentConditionWarningException;
use App\Exceptions\ComponentLifecycleWarningException;
use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Http\Transformers\ComponentsTransformer;
use App\Http\Transformers\DatatablesTransformer;
use App\Http\Transformers\SelectlistTransformer;
use App\Models\Asset;
use App\Models\Company;
use App\Models\ComponentDefinition;
use App\Models\ComponentInstance;
use App\Models\ComponentStorageLocation;
use App\Models\Location;
use App\Models\Setting;
use App\Models\User;
use App\Services\ComponentLifecycleService;
use App\Services\ModelAttributes\ComponentInstanceAttributeManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class ComponentsController extends Controller
{
    public function __construct(
        protected ComponentLifecycleService $lifecycle,
        protected ComponentInstanceAttributeManager $attributeManager,
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
            'lifecycle_status',
            'condition_code',
            'condition_status',
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
        $this->validateRequestedInitialState($request, $validator);

        if ($validator->fails()) {
            return response()->json(Helper::formatStandardApiResponse('error', null, $validator->errors()), 422);
        }

        $initialState = $this->requestedInitialState($request);
        $this->authorizeRequestedInitialState($initialState);

        try {
            $component = DB::transaction(function () use ($request, $initialState): ComponentInstance {
                $component = $this->createInRequestedInitialState(
                    $this->payloadFromRequest($request),
                    $initialState,
                    $request
                );

                if ($request->exists('instance_attributes')) {
                    $this->attributeManager->sync($component, $request->input('instance_attributes', []));
                }

                return $component->fresh($this->showRelations());
            });
        } catch (ValidationException $exception) {
            return response()->json(Helper::formatStandardApiResponse('error', null, $exception->errors()), 422);
        } catch (ComponentLifecycleWarningException $exception) {
            return $this->lifecycleWarningResponse($exception);
        } catch (ComponentConditionWarningException $exception) {
            return $this->conditionWarningResponse($exception);
        } catch (InvalidArgumentException $exception) {
            return $this->lifecycleErrorResponse($exception->getMessage());
        }

        return response()->json(Helper::formatStandardApiResponse(
            'success',
            (new ComponentsTransformer())->transformComponent($component),
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
        $this->validateUpdateTraceability($request, $component_id, $validator);

        if ($validator->fails()) {
            return response()->json(Helper::formatStandardApiResponse('error', null, $validator->errors()), 422);
        }

        try {
            $component = DB::transaction(function () use ($request, $component_id): ComponentInstance {
                if ($request->exists('serial')) {
                    $this->lifecycle->updateSerial($component_id, $request->input('serial'), [
                        'performed_by' => $request->user(),
                    ]);
                }

                $component_id = $this->lifecycle->updateMetadata(
                    $component_id,
                    $this->metadataPayloadFromRequest($request),
                    ['performed_by' => $request->user()]
                );

                if ($request->exists('instance_attributes')) {
                    $this->attributeManager->sync($component_id, $request->input('instance_attributes', []));
                }

                return $component_id->fresh($this->showRelations());
            });
        } catch (ValidationException $exception) {
            return response()->json(Helper::formatStandardApiResponse('error', null, $exception->errors()), 422);
        } catch (InvalidArgumentException $exception) {
            return $this->lifecycleErrorResponse($exception->getMessage());
        }

        return response()->json(Helper::formatStandardApiResponse(
            'success',
            (new ComponentsTransformer())->transformComponent($component),
            'Component updated.'
        ));
    }

    public function destroy(Request $request, ComponentInstance $component_id): JsonResponse
    {
        $this->authorize('delete', $component_id);

        try {
            $this->lifecycle->deleteInstance($component_id, $request->user());
        } catch (InvalidArgumentException $exception) {
            return $this->lifecycleErrorResponse($exception->getMessage());
        }

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
        $isAssetTransfer = $component_id->effectiveLifecycleStatus() === ComponentInstance::LIFECYCLE_ATTACHED
            && (int) $component_id->current_asset_id !== (int) $request->input('asset_id');
        $this->authorize($isAssetTransfer ? 'move' : 'install', $component_id);

        $validator = Validator::make($request->all(), [
            'asset_id' => ['required', 'integer'],
            'installed_as' => ['nullable', 'string', 'max:255'],
            'condition_warning_confirmed' => ['nullable', 'boolean'],
            'lifecycle_warning_confirmed' => ['nullable', 'boolean'],
            'note' => ['nullable', 'string'],
            'related_work_order_id' => ['nullable', 'integer', 'exists:work_orders,id'],
            'related_work_order_task_id' => ['nullable', 'integer', 'exists:work_order_tasks,id'],
        ]);
        $this->validateLifecycleAssetReference($request, $validator, 'asset_id');

        if ($validator->fails()) {
            return response()->json(Helper::formatStandardApiResponse('error', null, $validator->errors()), 422);
        }

        try {
            $asset = Asset::findOrFail($request->input('asset_id'));
            $component = $this->lifecycle->installIntoAsset($component_id, $asset, [
                'performed_by' => $request->user(),
                'installed_as' => $request->input('installed_as'),
                'condition_warning_confirmed' => $request->boolean('condition_warning_confirmed'),
                'lifecycle_warning_confirmed' => $request->boolean('lifecycle_warning_confirmed'),
                'note' => $request->input('note'),
                'related_work_order_id' => $request->input('related_work_order_id'),
                'related_work_order_task_id' => $request->input('related_work_order_task_id'),
            ]);
        } catch (ComponentLifecycleWarningException $exception) {
            return $this->lifecycleWarningResponse($exception);
        } catch (ComponentConditionWarningException $exception) {
            return $this->conditionWarningResponse($exception);
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
            'storage_location_id' => ['required', 'integer'],
            'needs_verification' => ['nullable', 'boolean'],
            'verification_location_id' => ['nullable', 'integer'],
            'note' => ['nullable', 'string'],
        ]);
        $this->validateLifecycleStorageReferences(
            $request,
            $component_id,
            $validator,
            ['storage_location_id', 'verification_location_id'],
            false
        );

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
            'storage_location_id' => ['nullable', 'integer'],
            'note' => ['nullable', 'string'],
        ]);
        $this->validateLifecycleStorageReferences(
            $request,
            $component_id,
            $validator,
            ['storage_location_id'],
            false
        );

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
            'storage_location_id' => ['nullable', 'integer'],
            'note' => ['nullable', 'string'],
        ]);
        $this->validateLifecycleStorageReferences(
            $request,
            $component_id,
            $validator,
            ['storage_location_id'],
            false
        );

        if ($validator->fails()) {
            return response()->json(Helper::formatStandardApiResponse('error', null, $validator->errors()), 422);
        }

        try {
            $location = $request->filled('storage_location_id')
                ? ComponentStorageLocation::findOrFail($request->input('storage_location_id'))
                : null;
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
        $this->authorize('destroy', $component_id);

        $validator = Validator::make($request->all(), [
            'storage_location_id' => ['nullable', 'integer'],
            'note' => ['nullable', 'string'],
        ]);
        $this->validateLifecycleStorageReferences(
            $request,
            $component_id,
            $validator,
            ['storage_location_id'],
            true
        );

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
        $this->authorize('destroy', $component_id);

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
            'component_definition_id' => [
                'nullable',
                'integer',
                Rule::exists('component_definitions', 'id')->where(
                    fn ($query) => $query
                        ->where('is_active', true)
                        ->whereNull('deleted_at')
                ),
            ],
            // Companyable foreign keys are resolved through scoped Eloquent
            // queries in validateRequestedInitialState(); raw exists rules do
            // not apply FMCS global scopes.
            'company_id' => ['nullable', 'integer'],
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
            'lifecycle_status' => ['nullable', Rule::in(array_keys(ComponentInstance::lifecycleStatusOptions()))],
            'condition_code' => ['nullable', Rule::in([
                ComponentInstance::CONDITION_UNKNOWN,
                ComponentInstance::CONDITION_GOOD,
                ComponentInstance::CONDITION_POOR,
                ComponentInstance::CONDITION_BROKEN,
            ])],
            'condition_status' => ['nullable', Rule::in(array_keys(ComponentInstance::conditionStatusOptions()))],
            'source_type' => ['nullable', Rule::in([
                ...array_keys(ComponentInstance::sourceTypeOptions()),
            ])],
            'source_asset_id' => ['nullable', 'integer'],
            'current_asset_id' => ['nullable', 'integer'],
            'storage_location_id' => ['nullable', 'integer'],
            'held_by_user_id' => ['nullable', 'integer'],
            'installed_as' => ['nullable', 'string', 'max:255'],
            'supplier_id' => ['nullable', 'integer', 'exists:suppliers,id'],
            'purchase_cost' => ['nullable', 'numeric', 'gte:0'],
            'received_at' => ['nullable', 'date'],
            'metadata_json' => ['nullable', 'array'],
            'notes' => ['nullable', 'string'],
            'instance_attributes' => ['nullable', 'array'],
            'instance_attributes.*.attribute_definition_id' => ['nullable', 'integer'],
            'instance_attributes.*.attribute_key' => ['nullable', 'string', 'max:100'],
            'instance_attributes.*.attribute_search' => ['nullable', 'string', 'max:255'],
            'instance_attributes.*.value' => ['nullable'],
            'instance_attributes.*.resolves_to_spec' => ['nullable', 'boolean'],
            'condition_warning_confirmed' => ['nullable', 'boolean'],
            'lifecycle_warning_confirmed' => ['nullable', 'boolean'],
        ];
    }

    protected function updateRules(?ComponentInstance $component = null): array
    {
        return [
            'component_definition_id' => [
                'nullable',
                'integer',
                Rule::exists('component_definitions', 'id')->where(
                    fn ($query) => $query
                        ->where('is_active', true)
                        ->whereNull('deleted_at')
                ),
            ],
            'display_name' => ['nullable', 'string', 'max:255'],
            'serial' => ['nullable', 'string', 'max:255'],
            'supplier_id' => ['nullable', 'integer', 'exists:suppliers,id'],
            'purchase_cost' => ['nullable', 'numeric', 'gte:0'],
            'received_at' => ['nullable', 'date'],
            'metadata_json' => ['nullable', 'array'],
            'notes' => ['nullable', 'string'],
            'instance_attributes' => ['nullable', 'array'],
            'instance_attributes.*.attribute_definition_id' => ['nullable', 'integer'],
            'instance_attributes.*.attribute_key' => ['nullable', 'string', 'max:100'],
            'instance_attributes.*.attribute_search' => ['nullable', 'string', 'max:255'],
            'instance_attributes.*.value' => ['nullable'],
            'instance_attributes.*.resolves_to_spec' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Generic component creation is intentionally capable of registering a
     * component in its real initial lifecycle state. Validate that state as a
     * whole before the first database write instead of validating each foreign
     * key independently.
     */
    protected function validateRequestedInitialState(Request $request, $validator): void
    {
        $validator->after(function ($validator) use ($request): void {
            $state = $this->requestedInitialState($request);
            $actor = $request->user();

            $this->rejectInternalCreateFields($request, $validator);
            $this->validateLifecycleAndConditionCompatibility($request, $state, $validator);
            $this->validateInitialPlacementFields($request, $state, $validator);

            $company = null;
            if ($request->filled('company_id')) {
                $company = Company::query()->find((int) $request->input('company_id'));

                if (!$company) {
                    $validator->errors()->add('company_id', __('The selected company is invalid.'));
                }
            }

            $sourceAsset = null;
            if ($request->filled('source_asset_id')) {
                $sourceAsset = Asset::query()->find((int) $request->input('source_asset_id'));

                if (!$sourceAsset) {
                    $validator->errors()->add('source_asset_id', __('The selected source asset is invalid.'));
                }
            }

            $currentAsset = null;
            if ($request->filled('current_asset_id')) {
                $currentAsset = Asset::query()->find((int) $request->input('current_asset_id'));

                if (!$currentAsset) {
                    $validator->errors()->add('current_asset_id', __('The selected current asset is invalid.'));
                }
            }

            $holder = null;
            if ($request->filled('held_by_user_id')) {
                $holder = User::query()->find((int) $request->input('held_by_user_id'));

                if (!$holder || !$actor || (int) $holder->id !== (int) $actor->id) {
                    $validator->errors()->add(
                        'held_by_user_id',
                        __('A newly registered tray component can only be assigned to the acting user.')
                    );
                }
            }

            $storageLocation = null;
            if ($request->filled('storage_location_id')) {
                $storageLocation = ComponentStorageLocation::query()
                    ->with('siteLocation')
                    ->find((int) $request->input('storage_location_id'));

                if (!$storageLocation || !$storageLocation->is_active) {
                    $validator->errors()->add('storage_location_id', __('The selected storage location is invalid.'));
                    $storageLocation = null;
                } elseif ($storageLocation->site_location_id && !$storageLocation->siteLocation) {
                    $validator->errors()->add('storage_location_id', __('The selected storage location is not accessible.'));
                }
            }

            $definition = null;
            if ($request->filled('component_definition_id')) {
                $definition = ComponentDefinition::query()
                    ->where('is_active', true)
                    ->find((int) $request->input('component_definition_id'));

                if (!$definition) {
                    $validator->errors()->add('component_definition_id', __('The selected component definition is invalid.'));
                }
            }

            $effectiveCompanyId = $this->effectiveRequestedCompanyId(
                $company,
                $currentAsset,
                $sourceAsset,
                $actor
            );

            $this->validateCompanyConsistency(
                $validator,
                $effectiveCompanyId,
                $company,
                $currentAsset,
                $sourceAsset,
                $holder,
                $storageLocation,
                $actor
            );
            $this->validateStorageLocationForState($state, $storageLocation, $validator);
            $this->validateSerialTrackingMode($definition, $request->input('serial'), 'serial', $validator);

            if ($state['lifecycle_status'] === ComponentInstance::LIFECYCLE_ATTACHED
                && $definition
                && !$definition->canBeInstalledOnAsset()
            ) {
                $validator->errors()->add(
                    'component_definition_id',
                    __('This component definition cannot be installed directly on an asset.')
                );
            }
        });
    }

    protected function validateUpdateTraceability(
        Request $request,
        ComponentInstance $component,
        $validator
    ): void {
        $validator->after(function ($validator) use ($request, $component): void {
            if (!$request->exists('component_definition_id') && !$request->exists('serial')) {
                return;
            }

            $definition = $component->componentDefinition;
            if ($request->exists('component_definition_id')) {
                $definitionId = $request->input('component_definition_id');
                $definition = filled($definitionId)
                    ? ComponentDefinition::query()->where('is_active', true)->find((int) $definitionId)
                    : null;
            }

            $serial = $request->exists('serial')
                ? $request->input('serial')
                : $component->serial;

            $this->validateSerialTrackingMode($definition, $serial, 'serial', $validator);

            if ($component->effectiveLifecycleStatus() === ComponentInstance::LIFECYCLE_ATTACHED
                && $definition
                && !$definition->canBeInstalledOnAsset()
            ) {
                $validator->errors()->add(
                    'component_definition_id',
                    __('This component definition cannot be installed directly on an asset.')
                );
            }
        });
    }

    /**
     * @return array{status:string,lifecycle_status:string,condition_code:string,condition_status:string}
     */
    protected function requestedInitialState(Request $request): array
    {
        $requestedStatus = $request->filled('status')
            ? (string) $request->input('status')
            : ComponentInstance::STATUS_IN_STOCK;
        $lifecycleStatus = $request->filled('lifecycle_status')
            ? (string) $request->input('lifecycle_status')
            : ComponentInstance::lifecycleStatusForLegacyStatus($requestedStatus);
        $conditionCode = $request->filled('condition_code')
            ? (string) $request->input('condition_code')
            : ComponentInstance::legacyConditionCodeForConditionStatus(
                $request->filled('condition_status') ? (string) $request->input('condition_status') : null
            );
        $conditionStatus = $request->filled('condition_status')
            ? (string) $request->input('condition_status')
            : ComponentInstance::conditionStatusForLegacyState($requestedStatus, $conditionCode);

        return [
            'status' => $requestedStatus,
            'lifecycle_status' => $lifecycleStatus,
            'condition_code' => $conditionCode,
            'condition_status' => $conditionStatus,
        ];
    }

    protected function authorizeRequestedInitialState(array $state): void
    {
        $ability = match ($state['lifecycle_status']) {
            ComponentInstance::LIFECYCLE_ATTACHED => 'install',
            ComponentInstance::LIFECYCLE_DESTRUCTION_PENDING,
            ComponentInstance::LIFECYCLE_DESTROYED,
            ComponentInstance::LIFECYCLE_SOLD_RETURNED => 'destroy',
            default => 'move',
        };

        $this->authorize($ability, ComponentInstance::class);

        if ($state['condition_status'] !== ComponentInstance::CONDITION_STATUS_GOOD) {
            $this->authorize('verify', ComponentInstance::class);
        }
    }

    protected function createInRequestedInitialState(
        array $payload,
        array $state,
        Request $request
    ): ComponentInstance {
        $actor = $request->user();
        $payload['status'] = ComponentInstance::legacyStatusForLifecycleStatus($state['lifecycle_status']);
        $payload['lifecycle_status'] = $state['lifecycle_status'];
        $payload['condition_code'] = $state['condition_code'];
        $payload['condition_status'] = $state['condition_status'];

        $effectiveCompanyId = $this->effectiveRequestedCompanyId(
            $request->filled('company_id')
                ? Company::query()->find((int) $request->input('company_id'))
                : null,
            $request->filled('current_asset_id')
                ? Asset::query()->find((int) $request->input('current_asset_id'))
                : null,
            $request->filled('source_asset_id')
                ? Asset::query()->find((int) $request->input('source_asset_id'))
                : null,
            $actor
        );

        if ($effectiveCompanyId) {
            $payload['company_id'] = $effectiveCompanyId;
        }

        if ($state['lifecycle_status'] === ComponentInstance::LIFECYCLE_IN_STOCK) {
            $payload['current_asset_id'] = null;
            $payload['held_by_user_id'] = null;
            $payload['installed_as'] = null;

            return $this->lifecycle->createInstance($payload, $actor);
        }

        if ($state['lifecycle_status'] === ComponentInstance::LIFECYCLE_SOLD_RETURNED) {
            $payload['current_asset_id'] = null;
            $payload['storage_location_id'] = null;
            $payload['held_by_user_id'] = null;
            $payload['installed_as'] = null;

            return $this->lifecycle->createInstance($payload, $actor);
        }

        $targetAsset = $request->filled('current_asset_id')
            ? Asset::query()->findOrFail((int) $request->input('current_asset_id'))
            : null;
        $targetStorageLocation = $request->filled('storage_location_id')
            ? ComponentStorageLocation::query()->findOrFail((int) $request->input('storage_location_id'))
            : null;
        $installedAs = $payload['installed_as'] ?? null;

        $basePayload = array_merge($payload, [
            'status' => ComponentInstance::STATUS_IN_STOCK,
            'lifecycle_status' => ComponentInstance::LIFECYCLE_IN_STOCK,
            'current_asset_id' => null,
            'storage_location_id' => null,
            'held_by_user_id' => null,
            'installed_as' => null,
        ]);
        $component = $this->lifecycle->createInstance($basePayload, $actor);
        $context = [
            'performed_by' => $actor,
            'note' => $request->input('notes'),
        ];

        return match ($state['lifecycle_status']) {
            ComponentInstance::LIFECYCLE_ATTACHED => $this->lifecycle->installIntoAsset(
                $component,
                $targetAsset,
                array_merge($context, [
                    'installed_as' => $installedAs,
                    'condition_warning_confirmed' => $request->boolean('condition_warning_confirmed'),
                    'lifecycle_warning_confirmed' => $request->boolean('lifecycle_warning_confirmed'),
                ])
            ),
            ComponentInstance::LIFECYCLE_IN_TRAY => $this->lifecycle->removeToTray(
                $component,
                $actor,
                $context
            ),
            ComponentInstance::LIFECYCLE_DESTRUCTION_PENDING => $this->lifecycle->markDestructionPending(
                $component,
                $targetStorageLocation,
                $context
            ),
            ComponentInstance::LIFECYCLE_DESTROYED => $this->destroyNewComponent(
                $component,
                $targetStorageLocation,
                $context,
                $request->input('metadata_json')
            ),
            default => throw new InvalidArgumentException('Unsupported initial component lifecycle state.'),
        };
    }

    protected function destroyNewComponent(
        ComponentInstance $component,
        ?ComponentStorageLocation $location,
        array $context,
        mixed $evidence
    ): ComponentInstance {
        $component = $this->lifecycle->markDestructionPending($component, $location, $context);

        return $this->lifecycle->markDestroyed($component, array_merge($context, [
            'payload_json' => is_array($evidence) ? $evidence : null,
        ]));
    }

    protected function validateLifecycleAndConditionCompatibility(
        Request $request,
        array $state,
        $validator
    ): void {
        if ($request->filled('status')
            && $request->filled('lifecycle_status')
            && ComponentInstance::lifecycleStatusForLegacyStatus((string) $request->input('status'))
                !== $state['lifecycle_status']
        ) {
            $validator->errors()->add(
                'lifecycle_status',
                __('Lifecycle status does not match the supplied legacy status.')
            );
        }

        if ($request->filled('condition_code')
            && ComponentInstance::conditionStatusForConditionCode((string) $request->input('condition_code'))
                !== $state['condition_status']
        ) {
            $validator->errors()->add(
                'condition_status',
                __('Condition status does not match the supplied condition code.')
            );
        }

        if ($request->filled('status')
            && in_array((string) $request->input('status'), [
                ComponentInstance::STATUS_NEEDS_VERIFICATION,
                ComponentInstance::STATUS_DEFECTIVE,
            ], true)
            && ComponentInstance::conditionStatusForLegacyState(
                (string) $request->input('status'),
                $state['condition_code']
            ) !== $state['condition_status']
        ) {
            $validator->errors()->add(
                'condition_status',
                __('Condition status does not match the supplied legacy status.')
            );
        }
    }

    protected function validateInitialPlacementFields(Request $request, array $state, $validator): void
    {
        $lifecycleStatus = $state['lifecycle_status'];

        if ($lifecycleStatus === ComponentInstance::LIFECYCLE_ATTACHED) {
            if (!$request->filled('current_asset_id')) {
                $validator->errors()->add('current_asset_id', __('An attached component requires a current asset.'));
            }

            $this->rejectFilledFields($request, $validator, [
                'storage_location_id',
                'held_by_user_id',
            ], __('Attached components cannot have a storage location or tray holder.'));

            return;
        }

        if ($lifecycleStatus === ComponentInstance::LIFECYCLE_IN_STOCK) {
            $this->rejectFilledFields($request, $validator, [
                'current_asset_id',
                'held_by_user_id',
                'installed_as',
            ], __('Stock components cannot have an asset, tray holder, or installed position.'));

            return;
        }

        if ($lifecycleStatus === ComponentInstance::LIFECYCLE_IN_TRAY) {
            $this->rejectFilledFields($request, $validator, [
                'current_asset_id',
                'storage_location_id',
                'installed_as',
            ], __('Tray components cannot have an asset, storage location, or installed position.'));

            return;
        }

        if (in_array($lifecycleStatus, [
            ComponentInstance::LIFECYCLE_DESTRUCTION_PENDING,
            ComponentInstance::LIFECYCLE_DESTROYED,
            ComponentInstance::LIFECYCLE_SOLD_RETURNED,
        ], true)) {
            $fields = ['current_asset_id', 'held_by_user_id', 'installed_as'];
            if ($lifecycleStatus === ComponentInstance::LIFECYCLE_SOLD_RETURNED) {
                $fields[] = 'storage_location_id';
            }

            $this->rejectFilledFields(
                $request,
                $validator,
                $fields,
                __('Terminal and destruction states cannot retain active placement fields.')
            );

            if (in_array($lifecycleStatus, [
                ComponentInstance::LIFECYCLE_DESTROYED,
                ComponentInstance::LIFECYCLE_SOLD_RETURNED,
            ], true) && trim((string) $request->input('notes')) === '' && !is_array($request->input('metadata_json'))) {
                $validator->errors()->add(
                    'notes',
                    __('A note or structured evidence is required for a terminal initial state.')
                );
            }
        }
    }

    protected function rejectFilledFields(
        Request $request,
        $validator,
        array $fields,
        string $message
    ): void {
        foreach ($fields as $field) {
            if ($request->filled($field)) {
                $validator->errors()->add($field, $message);
            }
        }
    }

    protected function rejectInternalCreateFields(Request $request, $validator): void
    {
        $message = __('Internal component traceability fields cannot be supplied through the generic API.');

        foreach ([
            'parent_component_instance_id',
            'root_asset_id',
            'is_materialized_expected',
            'materialized_reason',
            'ancestry_parent_component_instance_id',
            'ancestry_attached_through_at',
            'ancestry_attached_through_event_id',
            'transfer_started_at',
            'needs_verification_at',
            'last_verified_at',
            'destroyed_at',
            'created_by',
            'updated_by',
            'uuid',
            'qr_uid',
        ] as $field) {
            if ($request->exists($field)) {
                $validator->errors()->add($field, $message);
            }
        }
    }

    protected function effectiveRequestedCompanyId(
        ?Company $company,
        ?Asset $currentAsset,
        ?Asset $sourceAsset,
        ?User $actor
    ): ?int {
        if ($company) {
            return (int) $company->id;
        }

        if ($currentAsset?->company_id) {
            return (int) $currentAsset->company_id;
        }

        if ($sourceAsset?->company_id) {
            return (int) $sourceAsset->company_id;
        }

        return $actor?->company_id ? (int) $actor->company_id : null;
    }

    protected function validateCompanyConsistency(
        $validator,
        ?int $effectiveCompanyId,
        ?Company $company,
        ?Asset $currentAsset,
        ?Asset $sourceAsset,
        ?User $holder,
        ?ComponentStorageLocation $storageLocation,
        ?User $actor
    ): void {
        foreach ([
            'current_asset_id' => $currentAsset?->company_id,
            'source_asset_id' => $sourceAsset?->company_id,
            'held_by_user_id' => $holder?->company_id,
        ] as $field => $relatedCompanyId) {
            if ($effectiveCompanyId && $relatedCompanyId && (int) $relatedCompanyId !== $effectiveCompanyId) {
                $validator->errors()->add($field, __('The selected record belongs to a different company.'));
            }
        }

        $storageCompanyId = $storageLocation?->siteLocation?->company_id;
        if ($effectiveCompanyId && $storageCompanyId && (int) $storageCompanyId !== $effectiveCompanyId) {
            $validator->errors()->add(
                'storage_location_id',
                __('The selected storage location belongs to a different company.')
            );
        }

        if ($this->fullMultipleCompanySupportEnabled()
            && $actor
            && !$actor->isSuperUser()
            && (!$effectiveCompanyId || (int) $actor->company_id !== $effectiveCompanyId)
        ) {
            $validator->errors()->add('company_id', __('The component company is outside your access scope.'));
        }

        if ($company && $effectiveCompanyId && (int) $company->id !== $effectiveCompanyId) {
            $validator->errors()->add('company_id', __('The selected company is inconsistent with component placement.'));
        }
    }

    protected function validateStorageLocationForState(
        array $state,
        ?ComponentStorageLocation $location,
        $validator
    ): void {
        if (!$location) {
            return;
        }

        if (in_array($state['lifecycle_status'], [
            ComponentInstance::LIFECYCLE_DESTRUCTION_PENDING,
            ComponentInstance::LIFECYCLE_DESTROYED,
        ], true)) {
            if ($location->type !== ComponentStorageLocation::TYPE_DESTRUCTION) {
                $validator->errors()->add(
                    'storage_location_id',
                    __('Destruction states require a destruction storage location.')
                );
            }

            return;
        }

        if ($location->type === ComponentStorageLocation::TYPE_DESTRUCTION) {
            $validator->errors()->add(
                'storage_location_id',
                __('Use the destruction lifecycle action for a destruction storage location.')
            );
        }
    }

    protected function validateSerialTrackingMode(
        ?ComponentDefinition $definition,
        mixed $serial,
        string $field,
        $validator
    ): void {
        if (!$definition) {
            return;
        }

        $serial = trim((string) ($serial ?? ''));

        if ($definition->serial_tracking_mode === 'required' && $serial === '') {
            $validator->errors()->add($field, __('A serial number is required for this component definition.'));
        }

        if ($definition->serial_tracking_mode === 'not_tracked' && $serial !== '') {
            $validator->errors()->add($field, __('This component definition does not track serial numbers.'));
        }
    }

    protected function validateLifecycleAssetReference(
        Request $request,
        $validator,
        string $field
    ): void {
        $validator->after(function ($validator) use ($request, $field): void {
            if (!$request->filled($field)) {
                return;
            }

            if (!Asset::query()->whereKey((int) $request->input($field))->exists()) {
                $validator->errors()->add($field, __('The selected asset is invalid.'));
            }
        });
    }

    protected function validateLifecycleStorageReferences(
        Request $request,
        ComponentInstance $component,
        $validator,
        array $fields,
        bool $requiresDestructionLocation
    ): void {
        $validator->after(function ($validator) use (
            $request,
            $component,
            $fields,
            $requiresDestructionLocation
        ): void {
            foreach ($fields as $field) {
                if (!$request->filled($field)) {
                    continue;
                }

                $location = ComponentStorageLocation::query()
                    ->with('siteLocation')
                    ->find((int) $request->input($field));

                if (!$location || !$location->is_active) {
                    $validator->errors()->add($field, __('The selected storage location is invalid.'));
                    continue;
                }

                if ($location->site_location_id && !$location->siteLocation) {
                    $validator->errors()->add($field, __('The selected storage location is not accessible.'));
                    continue;
                }

                $locationCompanyId = $location->siteLocation?->company_id;
                if ($component->company_id
                    && $locationCompanyId
                    && (int) $component->company_id !== (int) $locationCompanyId
                ) {
                    $validator->errors()->add(
                        $field,
                        __('The selected storage location belongs to a different company.')
                    );
                }

                if ($requiresDestructionLocation
                    && $location->type !== ComponentStorageLocation::TYPE_DESTRUCTION
                ) {
                    $validator->errors()->add($field, __('Select a destruction storage location.'));
                }

                if (!$requiresDestructionLocation
                    && $location->type === ComponentStorageLocation::TYPE_DESTRUCTION
                ) {
                    $validator->errors()->add(
                        $field,
                        __('Use the destruction lifecycle action for a destruction storage location.')
                    );
                }
            }
        });
    }

    protected function fullMultipleCompanySupportEnabled(): bool
    {
        return (int) (Setting::getSettings()?->full_multiple_companies_support ?? 0) === 1;
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
            'lifecycle_status',
            'condition_code',
            'condition_status',
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
            $payload['status'] = $payload['status']
                ?? ComponentInstance::legacyStatusForLifecycleStatus($payload['lifecycle_status'] ?? null);
            $payload['condition_code'] = $payload['condition_code']
                ?? ComponentInstance::legacyConditionCodeForConditionStatus($payload['condition_status'] ?? null);
            $payload['source_type'] = $payload['source_type'] ?? ComponentInstance::SOURCE_MANUAL;
        }

        return $payload;
    }

    protected function metadataPayloadFromRequest(Request $request): array
    {
        return $request->only([
            'component_definition_id',
            'display_name',
            'supplier_id',
            'purchase_cost',
            'received_at',
            'metadata_json',
            'notes',
        ]);
    }

    protected function rejectLifecycleMutationFields(Request $request, $validator): void
    {
        $message = __('Lifecycle and immutable traceability fields must be changed through their dedicated component actions.');

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
            'company_id',
            'component_tag',
            'status',
            'lifecycle_status',
            'condition_code',
            'condition_status',
            'source_type',
            'source_asset_id',
            'current_asset_id',
            'parent_component_instance_id',
            'root_asset_id',
            'storage_location_id',
            'held_by_user_id',
            'transfer_started_at',
            'needs_verification_at',
            'last_verified_at',
            'installed_as',
            'destroyed_at',
            'created_by',
            'updated_by',
        ];
    }

    protected function lifecycleErrorResponse(string $message): JsonResponse
    {
        return response()->json(Helper::formatStandardApiResponse('error', null, $message), 422);
    }

    protected function conditionWarningResponse(ComponentConditionWarningException $exception): JsonResponse
    {
        return response()->json(Helper::formatStandardApiResponse(
            'warning',
            $exception->payload(),
            $exception->getMessage()
        ), 409);
    }

    protected function lifecycleWarningResponse(ComponentLifecycleWarningException $exception): JsonResponse
    {
        return response()->json(Helper::formatStandardApiResponse(
            'warning',
            $exception->payload(),
            $exception->getMessage()
        ), 409);
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

        foreach (['status', 'lifecycle_status', 'condition_status', 'source_type', 'company_id', 'source_asset_id', 'current_asset_id', 'held_by_user_id', 'storage_location_id'] as $filter) {
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
            $query->where('condition_status', ComponentInstance::CONDITION_STATUS_NEEDS_ATTENTION);
        }
    }

    protected function showRelations(): array
    {
        return [
            'componentDefinition.category',
            'componentDefinition.manufacturer',
            'componentDefinition.attributeContributions.definition.options',
            'componentDefinition.attributeContributions.option',
            'instanceAttributes.definition.options',
            'instanceAttributes.option',
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
