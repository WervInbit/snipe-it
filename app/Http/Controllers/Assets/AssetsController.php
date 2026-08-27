<?php

namespace App\Http\Controllers\Assets;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\BuildsComponentWorkflowOptions;
use App\Http\Requests\StoreAssetRequest;
use App\Http\Requests\UpdateAssetRequest;
use App\Models\Actionlog;
use Illuminate\Support\Facades\Log;
use App\Models\Asset;
use App\Models\AssetModel;
use App\Models\Company;
use App\Models\ComponentDefinition;
use App\Models\ComponentEvent;
use App\Models\ComponentInstance;
use App\Models\ComponentStorageLocation;
use App\Models\ModelNumber;
use App\Models\Location;
use App\Models\Setting;
use App\Models\Statuslabel;
use App\Models\User;
use App\Models\WorkflowProfile;
use App\View\Label;
use App\Services\QrLabelService;
use App\Services\Assets\LegacyAssetAssignmentCleanupService;
use App\Services\Components\AttachedComponentIssueService;
use App\Services\ModelAttributes\EffectiveAttributeResolver;
use App\Services\ModelAttributes\ModelAttributeManager;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use TypeError;
use Illuminate\Support\Collection;

/**
 * This class controls all actions related to assets for
 * the Snipe-IT Asset Management application.
 *
 * @version    v1.0
 * @author [A. Gianotto] [<snipe@snipe.net>]
 */
class AssetsController extends Controller
{
    use BuildsComponentWorkflowOptions;

    private const LEGACY_ASSIGNMENT_FIELDS = [
        'assigned_user',
        'assigned_asset',
        'assigned_location',
        'assigned_to',
        'assigned_type',
        'checkout_to_type',
    ];

    protected $barCodeDimensions = ['height' => 2, 'width' => 22];

    public function __construct()
    {
        $this->middleware('auth');
        parent::__construct();
    }

    /**
     * Returns a view that invokes the ajax tables which actually contains
     * the content for the assets listing, which is generated in getDatatable.
     *
     * @author [A. Gianotto] [<snipe@snipe.net>]
     * @see AssetController::getDatatable() method that generates the JSON response
     * @since [v1.0]
     * @param Request $request
     */
    public function index(Request $request) : View
    {
        $this->authorize('index', Asset::class);
        $company = Company::find($request->input('company_id'));

        return view('hardware/index')->with('company', $company);
    }

    /**
     * Returns a view that presents a form to create a new asset.
     *
     * @author [A. Gianotto] [<snipe@snipe.net>]
     * @since [v1.0]
     * @param Request $request
     * @internal param int $model_id
     */
    public function create(Request $request) : View
    {
        $this->authorize('create', Asset::class);
        $view = view('hardware/edit')
            ->with('statuslabel_list', Helper::statusLabelList())
            ->with('item', new Asset)
            ->with('statuslabel_types', Helper::statusTypeList());

        if ($request->filled('model_id')) {
            $selected_model = AssetModel::find($request->input('model_id'));
            $view->with('selected_model', $selected_model);
        }

        return $view;
    }

    /**
     * Validate and process new asset form data.
     *
     * @author [A. Gianotto] [<snipe@snipe.net>]
     * @since [v1.0]
     */
    public function store(StoreAssetRequest $request, ModelAttributeManager $attributeManager, EffectiveAttributeResolver $resolver) : RedirectResponse
    {
        $this->authorize(Asset::class);

        if ($request->hasAny(self::LEGACY_ASSIGNMENT_FIELDS)) {
            return redirect()->back()
                ->withInput()
                ->with('error', trans('admin/hardware/message.legacy_assignment_disabled'));
        }

        // There are a lot more rules to add here but prevents
        // errors around `asset_tags` not being present below.
        $this->validate($request, ['asset_tags' => ['array']]);

        // Handle asset tags - there could be one, or potentially many.
        // This is only necessary on create, not update, since bulk editing is handled
        // differently
        $requestedTags = $request->input('asset_tags', []);

        $settings = Setting::getSettings();

        [$selectedModelId, $selectedModelNumberId] = $this->resolveModelSelection($request);
        $modelForAttributes = $selectedModelId ? AssetModel::find($selectedModelId) : null;
        $modelNumber = null;

        if ($modelForAttributes) {
            $availableModelNumbers = $this->availableModelNumbersFor($modelForAttributes);
            if ($selectedModelNumberId) {
                $modelNumber = $availableModelNumbers->firstWhere('id', $selectedModelNumberId);
            }

            if (!$modelNumber && $availableModelNumbers->count() === 1) {
                $modelNumber = $availableModelNumbers->first();
            }

            if ($modelNumber) {
                $missing = $resolver->resolveForModelNumber($modelNumber)
                    ->filter(fn (\App\Services\ModelAttributes\ResolvedAttribute $attribute) => $attribute->definition->required_for_category && $attribute->value === null);
            } else {
                $missing = collect();
            }

            if ($missing->isNotEmpty()) {
                return redirect()->back()->withInput()->withErrors([
                    'model_id' => __('Complete the model specification before creating assets. Missing: :list', [
                        'list' => $missing->pluck('definition.label')->implode(', '),
                    ]),
                ]);
            }

            if ($availableModelNumbers->isNotEmpty() && !$modelNumber) {
                return redirect()->back()->withInput()->withErrors([
                    'model_number_id' => __('Select a valid model number.'),
                ]);
            }

        }
        elseif ($request->input('model_id_selector') || $request->input('model_id')) {
            return redirect()->back()->withInput()->withErrors([
                'model_id' => __('Select a valid model.'),
            ]);
        }

        $successes = [];
        $failures = [];
        $serials = $request->input('serials', []);
        $asset = null;
        $qr = app(QrLabelService::class);
        $count = max(count($requestedTags), count($serials), 1);

        for ($a = 1; $a <= $count; $a++) {
            $asset = new Asset();
            if ($request->boolean("asset_tag_case_override.$a")) {
                $asset->preserveAssetTagCase();
            }
            if ($request->boolean("serial_case_override.$a")) {
                $asset->preserveSerialCase();
            }
            $asset->model()->associate($modelForAttributes);
            if ($modelNumber) {
                $asset->model_number_id = $modelNumber->id;
            } else {
                $asset->model_number_id = null;
            }
            $asset->name = $request->input('name');

            // Check for a corresponding serial
            if (array_key_exists($a, $serials) && $serials[$a] !== '') {
                $asset->serial = $serials[$a];
            }

            $requestedTag = $requestedTags[$a] ?? null;
            $requestedTag = is_string($requestedTag) ? trim($requestedTag) : '';
            if ($requestedTag !== '') {
                $asset->asset_tag = $requestedTag;
            } else {
                $asset->asset_tag = Asset::generateTag();
                if (!$asset->asset_tag) {
                    $asset->asset_tag = Asset::generateTag();
                }
            }

            $asset->allowDuplicateSerial($request->boolean("allow_duplicate_serials.$a"));

            $asset->company_id              = Company::getIdForCurrentUser($request->input('company_id'));
            $asset->model_id                = $modelForAttributes?->id;
            $asset->order_number            = $request->input('order_number');
            $asset->notes                   = $request->input('notes');
            $asset->location_note           = $request->input('location_note');
            $asset->created_by              = auth()->id();
            $asset->status_id               = request('status_id');
            $asset->warranty_months         = request('warranty_months', null);
            $asset->purchase_cost           = request('purchase_cost');
            $asset->purchase_date           = request('purchase_date', null);
            $asset->asset_eol_date          = request('asset_eol_date', null);
            $asset->withStatusChangeNote($request->input('status_change_note'));
            $asset->supplier_id             = request('supplier_id', null);
            $asset->is_sellable             = (bool) request('is_sellable', 0);
            $asset->rtd_location_id         = request('rtd_location_id', null);
            $asset->byod                    = (bool) request('byod', 0);

            if ($asset->location_note) {
                $custom = Location::customLocation();
                $asset->rtd_location_id = $custom->id;
            }

            $asset->location_id = $asset->rtd_location_id;

            if ($request->has('use_cloned_image')) {
                $cloned_model_img = Asset::select('image')->find($request->input('clone_image_from_id'));
                if ($cloned_model_img) {
                    $new_image_name = 'clone-'.date('U').'-'.$cloned_model_img->image;
                    $new_image = 'assets/'.$new_image_name;
                    Storage::disk('public')->copy('assets/'.$cloned_model_img->image, $new_image);
                    $asset->image = $new_image_name;
                }

            } else {
                $asset = $request->handleImages($asset);
            }

            // Update custom fields in the database.
            // Validation for these fields is handled through the AssetRequest form request
            $model = AssetModel::find($request->get('model_id'));

            if (($model) && ($model->fieldset)) {
                foreach ($model->fieldset->fields as $field) {
                    if ($field->field_encrypted == '1') {
                        if (Gate::allows('assets.view.encrypted_custom_fields')) {
                            if (is_array($request->input($field->db_column))) {
                                $asset->{$field->db_column} = Crypt::encrypt(implode(', ', $request->input($field->db_column)));
                            } else {
                                $asset->{$field->db_column} = Crypt::encrypt($request->input($field->db_column));
                            }
                        }
                    } else {
                        if (is_array($request->input($field->db_column))) {
                            $asset->{$field->db_column} = implode(', ', $request->input($field->db_column));
                        } else {
                            $asset->{$field->db_column} = $request->input($field->db_column);
                        }
                    }
                }
            }

            // Validate the asset before saving
            if ($asset->isValid() && $asset->save()) {
                if ($asset->model_id) {
                    try {
                        $attributeManager->saveAssetOverrides($asset, $request->input('attribute_overrides', []));
                    } catch (ValidationException $exception) {
                        $asset->delete();
                        throw $exception;
                    }
                }

                $qr->generate($asset);

                $successes[] = "<a href='" . route('hardware.show', $asset) . "' style='color: white;'>" . e($asset->asset_tag) . "</a>";

            } else {
                $failures[] = join(",", $asset->getErrors()->all());
            }
        }
        $redirectOption = $request->get('redirect_option');
        if ($redirectOption === 'back') {
            session()->put(['redirect_option' => 'index']);
        } else {
            session()->put(['redirect_option' => $redirectOption ?: 'item']);
        }

        session()->put(['checkout_to_type' => $request->get('checkout_to_type'),
                       'other_redirect' =>  'model' ]);



        if ($successes) {
            if ($failures) {
                //some succeeded, some failed
                return Helper::getRedirectOption($request, $asset->id, 'Assets') //FIXME - not tested
                ->with('success-unescaped', trans_choice('admin/hardware/message.create.multi_success_linked', $successes, ['links' => join(", ", $successes)]))
                    ->with('warning', trans_choice('admin/hardware/message.create.partial_failure', $failures, ['failures' => join("; ", $failures)]));
            } else {
                if (count($successes) == 1) {
                    //the most common case, keeping it so we don't have to make every use of that translation string be trans_choice'ed
                    //and re-translated
                    return Helper::getRedirectOption($request, $asset->id, 'Assets')
                        ->with('success-unescaped', trans('admin/hardware/message.create.success_linked', [
                            'link' => route('hardware.show', $asset),
                            'tag' => e($asset->asset_tag),
                        ]));
                } else {
                    //multi-success
                    return Helper::getRedirectOption($request, $asset->id, 'Assets')
                        ->with('success-unescaped', trans_choice('admin/hardware/message.create.multi_success_linked', $successes, ['links' => join(", ", $successes)]));
                }
            }

        }

        return redirect()->back()->withInput()->withErrors($asset->getErrors());
    }


    /**
     * Returns a view that presents a form to edit an existing asset.
     *
     * @author [A. Gianotto] [<snipe@snipe.net>]
     * @since [v1.0]
     * @return \Illuminate\Contracts\View\View
     */
    public function edit(Asset $asset, EffectiveAttributeResolver $resolver) : View | RedirectResponse
    {
        $this->authorize($asset);
        session()->put('back_url', url()->previous());
        $asset->loadMissing('model.modelNumbers', 'modelNumber');
        $specAttributes = $asset->model
            ? $resolver->resolveForAsset($asset)
                ->reject(fn ($attribute) => $attribute->definition->key === Asset::CONDITION_GRADE_ATTRIBUTE_KEY)
                ->values()
            : collect();
        $modelNumbers = $asset->model ? $this->availableModelNumbersFor($asset->model, $asset->model_number_id) : collect();
        $selectedModelNumber = $asset->modelNumber ?? $asset->model?->primaryModelNumber;
        $testSummary = $asset->latestTestIssueSummary();

        return view('hardware/edit')
            ->with('item', $asset)
            ->with('statuslabel_list', Helper::statusLabelList())
            ->with('statuslabel_types', Helper::statusTypeList())
            ->with('specAttributes', $specAttributes)
            ->with('modelNumbers', $modelNumbers)
            ->with('selectedModelNumber', $selectedModelNumber)
            ->with('testSummary', $testSummary);
    }


    /**
     * Returns a view that presents information about an asset for detail view.
     *
     * @author [A. Gianotto] [<snipe@snipe.net>]
     * @param int $assetId
     * @since [v1.0]
     * @return \Illuminate\Contracts\View\View
     */
    public function show(Asset $asset) : View | RedirectResponse
    {
        $this->authorize('view', $asset);
        $settings = Setting::getSettings();

        if (isset($asset)) {
            $audit_log = Actionlog::where('action_type', '=', 'audit')
                ->where('item_id', '=', $asset->id)
                ->where('item_type', '=', Asset::class)
                ->orderBy('created_at', 'DESC')->first();

            if ($asset->location) {
                $use_currency = $asset->location->currency;
            } else {
                if ($settings->default_currency != '') {
                    $use_currency = $settings->default_currency;
                } else {
                    $use_currency = trans('general.currency');
                }
            }

            $componentHistory = ComponentEvent::query()
                ->with([
                    'componentInstance' => fn ($query) => $query
                        ->withTrashed()
                        ->with([
                            'componentDefinition.category',
                            'componentDefinition.manufacturer',
                            'storageLocation.siteLocation',
                        ]),
                    'performedBy',
                    'fromAsset.model',
                    'toAsset.model',
                    'fromStorageLocation',
                    'toStorageLocation',
                ])
                ->whereIn('component_instance_id', function ($query) use ($asset): void {
                    $query->select('component_instance_id')
                        ->from('component_events')
                        ->where(function ($inner) use ($asset): void {
                            $inner->where('from_asset_id', $asset->id)
                                ->orWhere('to_asset_id', $asset->id);
                        });
                })
                ->orderByDesc('created_at')
                ->get();

            $asset->load([
                'tests.audits.user',
                'tests.profile',
                'tests.results.audits.user',
                'tests.results.type',
                'tests.results.attributeDefinition',
                'tests.results.photos',
                'tests.user',
                'trackedComponents.componentDefinition.category',
                'trackedComponents.componentDefinition.manufacturer',
                'trackedComponents.storageLocation.siteLocation',
                'trackedComponents.heldBy',
                'trackedComponents.createdBy',
                'sourcedComponents.componentDefinition.category',
                'sourcedComponents.componentDefinition.manufacturer',
                'sourcedComponents.currentAsset.model',
                'sourcedComponents.storageLocation.siteLocation',
                'sourcedComponents.heldBy',
                'modelNumber.componentTemplates.componentDefinition.category',
                'modelNumber.componentTemplates.componentDefinition.manufacturer',
            ]);

            $resolvedAttributes = app(\App\Services\ModelAttributes\EffectiveAttributeResolver::class)
                ->resolveForAsset($asset)
                ->reject(fn ($attribute) => $attribute->definition->key === Asset::CONDITION_GRADE_ATTRIBUTE_KEY)
                ->values();
            $componentRoster = app(\App\Services\Components\AssetComponentRosterService::class)
                ->buildForAsset($asset);
            $topLevelComponentIds = $componentRoster->rows
                ->pluck('component')
                ->filter(fn ($component) => $component instanceof ComponentInstance && !$component->parent_component_instance_id)
                ->pluck('id')
                ->values();
            $removedExpectedSubcomponentsByParent = ComponentInstance::query()
                ->with([
                    'componentDefinition.category',
                    'componentDefinition.manufacturer',
                    'currentAsset.model',
                    'storageLocation.siteLocation',
                    'heldBy',
                ])
                ->whereIn('ancestry_parent_component_instance_id', $topLevelComponentIds)
                ->where('source_type', ComponentInstance::SOURCE_EXPECTED_BASELINE)
                ->where('is_materialized_expected', true)
                ->whereNull('parent_component_instance_id')
                ->orderByDesc('updated_at')
                ->get()
                ->groupBy('ancestry_parent_component_instance_id');
            $testSummary = $asset->latestTestIssueSummary();
            $workflowProfiles = WorkflowProfile::query()
                ->active()
                ->forAsset($asset)
                ->whereHas('items')
                ->withCount('items')
                ->ordered()
                ->get();
            $componentLocations = $this->storageLocationsByType();
            $currentUserTrayComponents = ComponentInstance::query()
                ->with(['componentDefinition.category', 'componentDefinition.manufacturer', 'sourceAsset.model'])
                ->inTray()
                ->heldBy(auth()->user())
                ->orderBy('component_tag')
                ->get();

            return view('hardware/view', compact('asset', 'settings'))
                ->with('resolvedAttributes', $resolvedAttributes)
                ->with('componentRoster', $componentRoster)
                ->with('removedExpectedSubcomponentsByParent', $removedExpectedSubcomponentsByParent)
                ->with('use_currency', $use_currency)
                ->with('audit_log', $audit_log)
                ->with('testSummary', $testSummary)
                ->with('workflowProfiles', $workflowProfiles)
                ->with('componentHistory', $componentHistory)
                ->with('componentDefinitions', $this->activeComponentDefinitions())
                ->with('componentConditionOptions', $this->conditionOptions())
                ->with('componentSourceTypeOptions', array_diff_key(
                    $this->sourceTypeOptions(),
                    [ComponentInstance::SOURCE_EXTRACTED => __('Extracted')]
                ))
                ->with('stockComponentLocations', $componentLocations['stock'])
                ->with('verificationComponentLocations', $componentLocations['verification'])
                ->with('destructionComponentLocations', $componentLocations['destruction'])
                ->with('currentUserTrayComponents', $currentUserTrayComponents)
                ->with('statuslabel_list', Helper::statusLabelList());
        }

        return redirect()->route('hardware.index')->with('error', trans('admin/hardware/message.does_not_exist'));
    }

    /**
     * Update only the status from the detail view.
     *
     * @param Request $request
     * @param Asset $asset
     */
    public function updateStatus(
        Request $request,
        Asset $asset,
        LegacyAssetAssignmentCleanupService $legacyAssignmentCleanup
    ) : RedirectResponse
    {
        $validated = $request->validate([
            'status_id' => ['required', 'integer', 'exists:status_labels,id'],
            'status_change_note' => ['nullable', 'string', 'max:65535'],
            'quality_grade' => ['nullable', Rule::in(array_keys(Asset::qualityGradeOptions()))],
            'ack_failed_tests' => ['nullable', 'boolean'],
            'ack_component_issues' => ['nullable', 'boolean'],
        ]);

        $status = Statuslabel::find($validated['status_id']);
        $canUpdateAsset = Gate::allows('update', $asset);

        if ($status && (Asset::isPreSaleStatus($status) || Asset::isSoldStatus($status))) {
            Gate::authorize('assets.sale_transition');
            $this->authorize('view', $asset);
        } else {
            $this->authorize('update', $asset);
        }

        if (array_key_exists('quality_grade', $validated) && !$canUpdateAsset) {
            abort(403);
        }

        if ($status && $this->statusRequiresTestAck($status)) {
            $issueLines = $this->testIssueLines($asset);

            if ($issueLines->isNotEmpty() && !$request->boolean('ack_failed_tests')) {
                return redirect()->back()
                    ->withInput()
                    ->with('warning', trans('tests.status_change_warning'))
                    ->with('test_issue_details', $issueLines->all())
                    ->with('requires_ack_failed_tests', true);
            }
        }

        if ($status && $this->statusRequiresComponentIssueAck($status)) {
            $warningRedirect = $this->componentIssueWarningRedirect($request, $asset);
            if ($warningRedirect) {
                return $warningRedirect;
            }
        }

        $asset->status_id = $validated['status_id'];
        if (array_key_exists('quality_grade', $validated)) {
            $asset->quality_grade = $validated['quality_grade'];
        }
        $asset->withStatusChangeNote($validated['status_change_note'] ?? null);

        $clearLegacyAssignment = $legacyAssignmentCleanup->statusRetiresAssignment($status);

        if ($asset->save()) {
            if ($clearLegacyAssignment) {
                $legacyAssignmentCleanup->clear($asset);
            }

            return redirect()->route('hardware.show', $asset)
                ->with('success', trans('admin/hardware/message.update.success'));
        }

        return redirect()->back()->withInput()->withErrors($asset->getErrors());
    }

    /**
     * Validate and process asset edit form.
     *
     * @param int $assetId
     * @since [v1.0]
     * @author [A. Gianotto] [<snipe@snipe.net>]
     */
    public function update(
        UpdateAssetRequest $request,
        Asset $asset,
        ModelAttributeManager $attributeManager,
        LegacyAssetAssignmentCleanupService $legacyAssignmentCleanup
    ) : RedirectResponse
    {

        $this->authorize($asset);

        if ($request->hasAny(self::LEGACY_ASSIGNMENT_FIELDS)) {
            return redirect()->back()
                ->withInput()
                ->with('error', trans('admin/hardware/message.legacy_assignment_disabled'));
        }

        [$selectedModelId, $selectedModelNumberId] = $this->resolveModelSelection($request);
        $selectedModel = $selectedModelId ? AssetModel::find($selectedModelId) : $asset->model;

        if ($selectedModelId && !$selectedModel) {
            return redirect()->back()->withInput()->withErrors([
                'model_id' => __('Select a valid model.'),
            ]);
        }

        $availableModelNumbers = $selectedModel
            ? $this->availableModelNumbersFor($selectedModel, $asset->model_number_id)
            : collect();

        $selectedModelNumber = null;

        if ($selectedModelNumberId) {
            $selectedModelNumber = $availableModelNumbers->firstWhere('id', $selectedModelNumberId);
        }

        if (!$selectedModelNumber && $availableModelNumbers->count() === 1) {
            $selectedModelNumber = $availableModelNumbers->first();
            $selectedModelNumberId = $selectedModelNumber?->id;
        }

        if ($selectedModel && $availableModelNumbers->isNotEmpty() && !$selectedModelNumber) {
            return redirect()->back()->withInput()->withErrors([
                'model_number_id' => __('Select a valid model number.'),
            ]);
        }

        if ($selectedModel) {
            $asset->model()->associate($selectedModel);
            $asset->setRelation('model', $selectedModel);
        } else {
            $asset->model()->dissociate();
            $asset->unsetRelation('model');
        }
        $asset->model_number_id = $selectedModelNumber?->id;
        $asset->unsetRelation('modelNumber');
        if ($asset->isDirty('model_id') || $asset->isDirty('model_number_id')) {
            $asset->tests_completed_ok = false;
        }

        $asset->status_id = $request->input('status_id', null);
        $asset->warranty_months = $request->input('warranty_months', null);
        $asset->purchase_cost = $request->input('purchase_cost', null);
        $asset->purchase_date = $request->input('purchase_date', null);
        $asset->withStatusChangeNote($request->input('status_change_note'));
        if ($request->filled('purchase_date') && !$request->filled('asset_eol_date') && (($selectedModel?->eol ?? 0) > 0)) {
            $asset->purchase_date = $request->input('purchase_date', null); 
            $asset->asset_eol_date = Carbon::parse($request->input('purchase_date'))->addMonths($selectedModel->eol)->format('Y-m-d');
            $asset->eol_explicit = false;
        } elseif ($request->filled('asset_eol_date')) {
           $asset->asset_eol_date = $request->input('asset_eol_date', null);
            $months = (int) Carbon::parse($asset->asset_eol_date)->diffInMonths($asset->purchase_date, true);
           if(($selectedModel?->eol ?? 0) > 0) {
               if($selectedModel->eol > 0 && $months != $selectedModel->eol) {
                   $asset->eol_explicit = true;
               } else {
                   $asset->eol_explicit = false;
               }
           } else {
               $asset->eol_explicit = true;
           }
        } elseif (!$request->filled('asset_eol_date') && (($selectedModel?->eol ?? 0) == 0)) {
           $asset->asset_eol_date = null;
		   $asset->eol_explicit = false;
        }
        $asset->supplier_id = $request->input('supplier_id', null);
        $asset->is_sellable = $request->boolean('is_sellable');
        $asset->byod = $request->boolean('byod');

        if ($request->has('location_note')) {
            $asset->location_note = $request->input('location_note');
        }

        if ($request->filled('location_note')) {
            $custom = Location::customLocation();
            $asset->rtd_location_id = $custom->id;
            $asset->location_id = $custom->id;
        } elseif ($request->has('rtd_location_id')) {
            $asset->location_note = null;
            $asset->rtd_location_id = $request->input('rtd_location_id');
            $asset->location_id = $asset->rtd_location_id;
        }

        session()->put([
            'redirect_option' => $request->get('redirect_option'),
            'checkout_to_type' => $request->get('checkout_to_type'),
            'other_redirect' => $request->get('redirect_option') === 'other_redirect' ? 'model' : null,
        ]);

        $status = Statuslabel::find($request->input('status_id'));

        if ($status && $this->statusRequiresTestAck($status)) {
            $issueLines = $this->testIssueLines($asset);

            if ($issueLines->isNotEmpty() && !$request->boolean('ack_failed_tests')) {
                return redirect()->back()
                    ->withInput()
                    ->with('warning', trans('tests.status_change_warning'))
                    ->with('test_issue_details', $issueLines->all())
                    ->with('requires_ack_failed_tests', true);
            }
        }

        if ($status && $this->statusRequiresComponentIssueAck($status)) {
            $warningRedirect = $this->componentIssueWarningRedirect($request, $asset);
            if ($warningRedirect) {
                return $warningRedirect;
            }
        }

        $clearLegacyAssignment = $legacyAssignmentCleanup->statusRetiresAssignment($status);

        if ($request->filled('image_delete')) {
            try {
                unlink(public_path().'/uploads/assets/'.basename($asset->image));
                $asset->image = '';
            } catch (\Exception $e) {
                Log::info($e);
            }
        }

        // Update the asset data

        if ($request->boolean('asset_tag_case_override.1')) {
            $asset->preserveAssetTagCase();
        }
        if ($request->boolean('serial_case_override.1')) {
            $asset->preserveSerialCase();
        }

        $serialInput = $request->input('serials');
        if (is_array($serialInput)) {
            $serialInput = $serialInput[1] ?? null;
        }
        if ($serialInput === '') {
            $serialInput = null;
        }
        $asset->serial = is_scalar($serialInput) ? (string) $serialInput : null;

        $asset->allowDuplicateSerial($request->boolean('allow_duplicate_serials.1'));

        $asset->name = $request->input('name');
        $asset->company_id = Company::getIdForCurrentUser($request->input('company_id'));
        $asset->model_id = $selectedModel?->id;
        $asset->order_number = $request->input('order_number');

        $asset_tags = $request->input('asset_tags');
        $original_tag = $asset->asset_tag;

        if ($request->filled('asset_tags')) {
            $incoming_tag = is_array($asset_tags) ? $asset_tags[1] : $asset_tags;
            if (!auth()->user()->isAdmin() && strtoupper((string) $incoming_tag) !== strtoupper((string) $original_tag)) {
                return redirect()->back()->withErrors(['asset_tag' => trans('admin/hardware/message.tag_immutable')]);
            }
            $asset->asset_tag = $incoming_tag;
        }

        $asset->notes = $request->input('notes');

        $asset = $request->handleImages($asset);

        // Update custom fields in the database.
        // FIXME: No idea why this is returning a Builder error on db_column_name.
        // Need to investigate and fix. Using static method for now.
        $model = $selectedModel;
        if (($model) && ($model->fieldset)) {
            foreach ($model->fieldset->fields as $field) {
                if ($field->element == 'checkbox' && !$request->has($field->db_column)) {
                    $asset->{$field->db_column} = null;
                }
                if ($request->has($field->db_column)) {
                    if ($field->field_encrypted == '1') {
                        if (Gate::allows('assets.view.encrypted_custom_fields')) {
                            if (is_array($request->input($field->db_column))) {
                                $asset->{$field->db_column} = Crypt::encrypt(implode(', ', $request->input($field->db_column)));
                            } else {
                                $asset->{$field->db_column} = Crypt::encrypt($request->input($field->db_column));
                            }
                        }
                    } else {
                        if (is_array($request->input($field->db_column))) {
                            $asset->{$field->db_column} = implode(', ', $request->input($field->db_column));
                        } else {
                            $asset->{$field->db_column} = $request->input($field->db_column);
                        }
                    }
                }
            }
        }
            if ($asset->save()) {
                if ($clearLegacyAssignment) {
                    $legacyAssignmentCleanup->clear($asset);
                }

                if ($asset->model_id) {
                    try {
                        $attributeManager->saveAssetOverrides($asset, $request->input('attribute_overrides', []));
                    } catch (ValidationException $exception) {
                        return redirect()->back()->withInput()->withErrors($exception->errors());
                    }
                }

            return Helper::getRedirectOption($request, $asset->id, 'Assets')
                ->with('success', trans('admin/hardware/message.update.success'));
        }

        return redirect()->back()->withInput()->withErrors($asset->getErrors());
    }

    /**
     * Delete a given asset (mark as deleted).
     *
     * @author [A. Gianotto] [<snipe@snipe.net>]
     * @param int $assetId
     * @since [v1.0]
     */
    public function destroy(
        Request $request,
        $assetId,
        LegacyAssetAssignmentCleanupService $legacyAssignmentCleanup
    ) : RedirectResponse
    {
        // Check if the asset exists
        if (is_null($asset = Asset::find($assetId))) {
            // Redirect to the asset management page with error
            return redirect()->route('hardware.index')->with('error', trans('admin/hardware/message.does_not_exist'));
        }

        $this->authorize('delete', $asset);

        $legacyAssignmentCleanup->clear($asset);


        $asset->delete();

        return redirect()->route('hardware.index')->with('success', trans('admin/hardware/message.delete.success'));
    }

    /**
     * Searches the assets table by serial, and redirects if it finds one
     *
     * @author [A. Gianotto] [<snipe@snipe.net>]
     * @since [v3.0]
     */
    public function getAssetBySerial(Request $request) : RedirectResponse
    {
        $topsearch = ($request->get('topsearch')=="true");

        if (!$asset = Asset::where('serial', '=', $request->get('serial'))->first()) {
            return redirect()->route('hardware.index')->with('error', trans('admin/hardware/message.does_not_exist'));
        }
        $this->authorize('view', $asset);
        return redirect()->route('hardware.show', $asset->id)->with('topsearch', $topsearch);
    }

    /**
     * Searches the assets table by asset tag, and redirects if it finds one
     *
     * @author [A. Gianotto] [<snipe@snipe.net>]
     * @since [v3.0]
     * @return \Illuminate\Http\RedirectResponse
     */
    public function getAssetByTag(Request $request, $tag=null) : RedirectResponse
    {
        $tag = $tag ? $tag : $request->get('assetTag');
        $topsearch = ($request->get('topsearch') == 'true');

        // Search for an exact and unique asset tag match
        $assets = Asset::where('asset_tag', '=', $tag);

        // If not a unique result, redirect to the index view
        if ($assets->count() != 1) {
            return redirect()->route('hardware.index')
                ->with('search', $tag)
                ->with('warning', trans('admin/hardware/message.does_not_exist_var', [ 'asset_tag' => $tag ]));
        }
        $asset = $assets->first();
        $this->authorize('view', $asset);

        return redirect()->route('hardware.show', $asset->id)->with('topsearch', $topsearch);
    }

    private function statusRequiresTestAck(?Statuslabel $status): bool
    {
        return Asset::statusRequiresTestAck($status);
    }

    private function statusRequiresComponentIssueAck(?Statuslabel $status): bool
    {
        return Asset::statusRequiresTestAck($status);
    }

    private function testIssueLines(Asset $asset): Collection
    {
        $testSummary = $asset->latestTestIssueSummary();
        $issueLines = collect();
        $missingProfiles = $testSummary['missing_profiles'] ?? collect();

        if ($missingProfiles->isNotEmpty()) {
            $issueLines->push(trans('tests.missing_workflow_profiles', [
                'profiles' => $missingProfiles->implode(', '),
            ]));
        } elseif ($testSummary['missing_run']) {
            $issueLines->push(trans('tests.no_test_run_recorded'));
        }

        if ($testSummary['failed']->isNotEmpty()) {
            $issueLines->push(trans('tests.failed_list', ['tests' => $testSummary['failed']->implode(', ')]));
        }

        if ($testSummary['incomplete']->isNotEmpty()) {
            $issueLines->push(trans('tests.incomplete_list', ['tests' => $testSummary['incomplete']->implode(', ')]));
        }

        return $issueLines;
    }

    private function componentIssueWarningRedirect(Request $request, Asset $asset): ?RedirectResponse
    {
        if ($request->boolean('ack_component_issues')) {
            return null;
        }

        $issueLines = app(AttachedComponentIssueService::class)->warningLinesForAsset($asset);

        if ($issueLines === []) {
            return null;
        }

        return redirect()->back()
            ->withInput()
            ->with('warning', __('Attached damaged or needs-attention components remain on this asset. Submit again to confirm the selling-state change.'))
            ->with('component_issue_details', $issueLines)
            ->with('requires_ack_component_issues', true);
    }


    /**
     * Return a 2D barcode for the asset
     *
     * @author [A. Gianotto] [<snipe@snipe.net>]
     * @param int $assetId
     * @since [v1.0]
     * @return mixed
     */
    public function getBarCode($assetId = null)
    {
        $settings = Setting::getSettings();
        if ($asset = Asset::withTrashed()->find($assetId)) {
            $barcode_file = public_path().'/uploads/barcodes/'.str_slug($settings->label2_1d_type).'-'.str_slug($asset->asset_tag).'.png';

            if (isset($asset->id, $asset->asset_tag)) {
                if (file_exists($barcode_file)) {
                    $header = ['Content-type' => 'image/png'];

                    return response()->file($barcode_file, $header);
                } else {
                    // Calculate barcode width in pixel based on label width (inch)
                    $barcode_width = ($settings->labels_width - $settings->labels_display_sgutter) * 200.000000000001;

                    $barcode = new \Com\Tecnick\Barcode\Barcode();
                    try {
                        $barcode_obj = $barcode->getBarcodeObj($settings->label2_1d_type, $asset->asset_tag, ($barcode_width < 300 ? $barcode_width : 300), 50);
                        file_put_contents($barcode_file, $barcode_obj->getPngData());

                        return response($barcode_obj->getPngData())->header('Content-type', 'image/png');
                    } catch (\Exception|TypeError $e) {
                        Log::debug('The barcode format is invalid.');

                        return response(file_get_contents(public_path('uploads/barcodes/invalid_barcode.gif')))->header('Content-type', 'image/gif');
                    }
                }
            }
        }
        return null;
    }

    /**
     * Return a label for an individual asset.
     *
     * @author [L. Swartzendruber] [<logan.swartzendruber@gmail.com>
     * @param int $assetId
     * @return \Illuminate\Contracts\View\View
     */
    public function getLabel($assetId = null)
    {
        if (isset($assetId)) {
            $asset = Asset::find($assetId);
            $this->authorize('view', $asset);

            return (new Label())
                ->with('assets', collect([ $asset ]))
                ->with('settings', Setting::getSettings())
                ->with('template', request()->get('template'))
                ->with('offset', request()->get('offset'))
                ->with('bulkedit', false)
                ->with('count', 0);
        }
    }

    /**
     * Returns a view that presents a form to clone an asset.
     *
     * @author [A. Gianotto] [<snipe@snipe.net>]
     * @param int $assetId
     * @since [v1.0]
     * @return \Illuminate\Contracts\View\View
     */
    public function getClone(Asset $asset)
    {
        $this->authorize('create', Asset::class);
        $cloned = clone $asset;
        $cloned_model = $asset;
        $cloned->id = null;
        $cloned->asset_tag = '';
        $cloned->serial = '';
        $cloned->assigned_to = '';
        $cloned->deleted_at = '';

        return view('hardware/edit')
            ->with('statuslabel_list', Helper::statusLabelList())
            ->with('statuslabel_types', Helper::statusTypeList())
            ->with('cloned_model', $cloned_model)
            ->with('item', $cloned);
    }

    /**
     * Restore a deleted asset.
     *
     * @author [A. Gianotto] [<snipe@snipe.net>]
     * @param int $assetId
     * @since [v1.0]
     * @return \Illuminate\Contracts\View\View
     */
    public function getRestore(
        LegacyAssetAssignmentCleanupService $legacyAssignmentCleanup,
        $assetId = null
    )
    {
        if ($asset = Asset::withTrashed()->find($assetId)) {
            $this->authorize('delete', $asset);

            if ($asset->deleted_at == '') {
                return redirect()->back()->with('error', trans('general.not_deleted', ['item_type' => trans('general.asset')]));
            }

            $restored = DB::transaction(function () use ($asset, $legacyAssignmentCleanup): bool {
                if (! $asset->restore()) {
                    return false;
                }

                $legacyAssignmentCleanup->clear($asset);

                return true;
            });

            if ($restored) {
                // Redirect them to the deleted page if there are more, otherwise the section index
                $deleted_assets = Asset::onlyTrashed()->count();
                if ($deleted_assets > 0) {
                    return redirect()->back()->with('success', trans('admin/hardware/message.restore.success'));
                }
                return redirect()->route('hardware.index')->with('success', trans('admin/hardware/message.restore.success'));
            }

            // Check validation to make sure we're not restoring an asset with the same asset tag (or unique attribute) as an existing asset
            return redirect()->back()->with('error', trans('general.could_not_restore', ['item_type' => trans('general.asset'), 'error' => $asset->getErrors()->first()]));
        }

        return redirect()->route('hardware.index')->with('error', trans('admin/hardware/message.does_not_exist'));
    }

    public function toggleSaleAvailability(Request $request, Asset $asset): RedirectResponse
    {
        $this->authorize('update', $asset);

        $desiredState = $request->boolean('is_sellable');

        if ($desiredState && $asset->byod) {
            return redirect()->back()->with('warning', trans('admin/hardware/message.internal_use_conflict'));
        }

        if ($desiredState) {
            $warningRedirect = $this->componentIssueWarningRedirect($request, $asset);
            if ($warningRedirect) {
                return $warningRedirect;
            }
        }

        $asset->is_sellable = $desiredState;
        $asset->save();

        return redirect()->back()->with('success', trans('general.updated'));
    }

    public function toggleInternalUse(Request $request, Asset $asset): RedirectResponse
    {
        $this->authorize('update', $asset);

        $asset->byod = $request->boolean('byod');

        if ($asset->byod) {
            $asset->is_sellable = false;
        }

        $asset->save();

        return redirect()->back()->with('success', trans('general.updated'));
    }

    private function resolveModelSelection(Request $request): array
    {
        $modelId = $request->input('model_id');
        $modelNumberId = $request->input('model_number_id');
        $composite = $request->input('model_id_selector');

        if ((!$modelId || $modelId === '') && $composite) {
            if (is_numeric($composite)) {
                $modelId = (int) $composite;
            } elseif (is_string($composite) && str_contains($composite, ':')) {
                [$rawModel, $rawNumber] = array_pad(explode(':', $composite, 2), 2, null);
                if ($rawModel !== null && $rawModel !== '') {
                    $modelId = (int) $rawModel;
                }
                if ($rawNumber !== null && $rawNumber !== '') {
                    $modelNumberId = (int) $rawNumber;
                }
            }
        }

        if ($modelId !== null && $modelId !== '') {
            $modelId = (int) $modelId;
        } else {
            $modelId = null;
        }

        if ($modelNumberId !== null && $modelNumberId !== '') {
            $modelNumberId = (int) $modelNumberId;
        } else {
            $modelNumberId = null;
        }

        return [$modelId, $modelNumberId];
    }

    /**
     * Retrieve active model numbers for a model, optionally including a specific ID.
     */
    private function availableModelNumbersFor(?AssetModel $model, ?int $includeId = null): Collection
    {
        if (!$model) {
            return collect();
        }

        $numbers = $model->modelNumbers()
            ->active()
            ->orderBy('code')
            ->get();

        if ($includeId) {
            $existing = $model->modelNumbers()->whereKey($includeId)->first();
            if ($existing && $numbers->doesntContain(fn ($number) => $number->id === $existing->id)) {
                $numbers->push($existing);
            }
        }

        return $numbers;
    }

}

