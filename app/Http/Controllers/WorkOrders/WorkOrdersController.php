<?php

namespace App\Http\Controllers\WorkOrders;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\Company;
use App\Models\ComponentEvent;
use App\Models\Setting;
use App\Models\User;
use App\Models\WorkOrder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class WorkOrdersController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', WorkOrder::class);

        $search = trim((string) $request->input('search'));

        $workOrders = $this->workOrdersQuery($request)
            ->with(['company'])
            ->withCount(['assets', 'tasks'])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($inner) use ($search): void {
                    $inner->where('work_order_number', 'like', "%{$search}%")
                        ->orWhere('title', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhereHas('company', fn ($companyQuery) => $companyQuery->where('name', 'like', "%{$search}%"));
                });
            })
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        return view('work-orders.index', compact('workOrders', 'search'));
    }

    public function create(Request $request): View
    {
        $this->authorize('create', WorkOrder::class);

        return view('work-orders.create', [
            'workOrder' => new WorkOrder([
                'company_id' => $this->defaultCompanyId($request),
                'status' => WorkOrder::STATUS_DRAFT,
                'priority' => WorkOrder::PRIORITY_NORMAL,
                'visibility_profile' => WorkOrder::VISIBILITY_PROFILE_FULL,
                'intake_date' => now()->toDateString(),
            ]),
            ...$this->formOptions($request),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', WorkOrder::class);

        $data = $this->validatedData($request);
        $visibleUserIds = $this->validatedVisibleUserIds($request);
        $workOrder = new WorkOrder($data);
        $workOrder->portal_visibility_json = $this->portalVisibilityPayload($request);
        $workOrder->created_by = $request->user()?->id;
        $workOrder->updated_by = $request->user()?->id;
        $workOrder->save();

        $this->syncVisibleUsers($request, $workOrder, $visibleUserIds);

        return redirect()
            ->route('work-orders.show', $workOrder)
            ->with('success', __('Work order created.'));
    }

    public function show(Request $request, WorkOrder $workOrder): View
    {
        $this->authorize('viewAny', WorkOrder::class);
        $this->authorize('view', $workOrder);

        $workOrder->load([
            'company',
            'primaryContact',
            'createdBy',
            'updatedBy',
            'visibleUsers',
            'assets.asset',
            'tasks.assignee',
            'tasks.workOrderAsset',
        ]);

        return view('work-orders.show', [
            'workOrder' => $workOrder,
            'componentEvents' => $this->componentEventsFor($workOrder),
            ...$this->formOptions($request),
        ]);
    }

    public function edit(Request $request, WorkOrder $workOrder): View
    {
        $this->authorize('update', $workOrder);

        $workOrder->load('visibleUsers');

        return view('work-orders.edit', [
            'workOrder' => $workOrder,
            ...$this->formOptions($request),
        ]);
    }

    public function update(Request $request, WorkOrder $workOrder): RedirectResponse
    {
        $this->authorize('update', $workOrder);

        $data = $this->validatedData($request);
        $visibleUserIds = $this->validatedVisibleUserIds($request);

        if (!$request->user()?->can('manageVisibility', $workOrder)) {
            $data['visibility_profile'] = $workOrder->visibility_profile;
        }

        $workOrder->fill($data);
        $workOrder->updated_by = $request->user()?->id;

        if ($request->user()?->can('manageVisibility', $workOrder)) {
            $workOrder->portal_visibility_json = $this->portalVisibilityPayload($request);
        }

        $workOrder->save();

        $this->syncVisibleUsers($request, $workOrder, $visibleUserIds);

        return redirect()
            ->route('work-orders.show', $workOrder)
            ->with('success', __('Work order updated.'));
    }

    protected function componentEventsFor(WorkOrder $workOrder)
    {
        return ComponentEvent::query()
            ->with([
                'componentInstance.componentDefinition',
                'performedBy',
                'fromAsset',
                'toAsset',
                'relatedWorkOrderTask',
            ])
            ->where(function ($query) use ($workOrder): void {
                $query->where('related_work_order_id', $workOrder->id)
                    ->orWhereIn('related_work_order_task_id', $workOrder->tasks()->select('id'));
            })
            ->orderByDesc('created_at')
            ->get();
    }

    protected function validatedData(Request $request): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'company_id' => ['nullable', 'integer', 'exists:companies,id'],
            'primary_contact_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'status' => ['required', Rule::in(array_keys(WorkOrder::statusOptions()))],
            'priority' => ['nullable', Rule::in(array_keys(WorkOrder::priorityOptions()))],
            'visibility_profile' => ['required', Rule::in(array_keys(WorkOrder::visibilityProfileOptions()))],
            'intake_date' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:intake_date'],
        ]);

        if ($this->usesRestrictedCompanyScope($request)) {
            $data['company_id'] = (int) $request->user()->company_id;
        }

        $this->ensureSelectableUser($request, $data['primary_contact_user_id'] ?? null, 'primary_contact_user_id');

        return $data;
    }

    protected function formOptions(Request $request): array
    {
        $userOptions = $this->userOptionsQuery($request)->get();

        return [
            'companies' => $this->companyOptionsQuery($request)->get(),
            'contacts' => $userOptions,
            'visibleUsers' => $userOptions,
            'assetOptions' => Asset::query()->orderBy('asset_tag')->get(),
            'taskAssignees' => $userOptions,
            'statusOptions' => WorkOrder::statusOptions(),
            'priorityOptions' => WorkOrder::priorityOptions(),
            'visibilityProfileOptions' => WorkOrder::visibilityProfileOptions(),
        ];
    }

    protected function portalVisibilityPayload(Request $request): array
    {
        if ($request->input('visibility_profile') !== WorkOrder::VISIBILITY_PROFILE_CUSTOM) {
            return [];
        }

        return [
            'show_components' => $request->boolean('portal_show_components'),
            'show_notes_customer' => $request->boolean('portal_show_notes_customer'),
        ];
    }

    protected function syncVisibleUsers(Request $request, WorkOrder $workOrder, Collection $visibleUserIds): void
    {
        if (!$request->user()?->can('manageVisibility', $workOrder)) {
            return;
        }

        $workOrder->visibleUsers()->sync(
            $visibleUserIds
                ->mapWithKeys(fn ($id) => [$id => ['granted_by' => $request->user()?->id]])
                ->all()
        );
    }

    protected function validatedVisibleUserIds(Request $request): Collection
    {
        if (!$request->user()?->can('manageVisibility', WorkOrder::class)
            && !$request->user()?->can('manageVisibility', new WorkOrder())) {
            return collect();
        }

        $visibleUserIds = collect($request->input('visible_user_ids', []))
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        foreach ($visibleUserIds as $userId) {
            $this->ensureSelectableUser($request, $userId, 'visible_user_ids');
        }

        return $visibleUserIds;
    }

    protected function ensureSelectableUser(Request $request, ?int $userId, string $field): void
    {
        if (!$userId) {
            return;
        }

        if ($this->userOptionsQuery($request)->whereKey($userId)->exists()) {
            return;
        }

        throw ValidationException::withMessages([
            $field => __('The selected user is outside your company scope.'),
        ]);
    }

    protected function userOptionsQuery(Request $request)
    {
        $query = User::query()
            ->orderBy('first_name')
            ->orderBy('last_name');

        if ($this->usesRestrictedCompanyScope($request)) {
            $query->where('company_id', $request->user()->company_id);
        }

        return $query;
    }

    protected function workOrdersQuery(Request $request)
    {
        $query = WorkOrder::query();

        if ($this->bypassesCompanyScope($request)) {
            $query->withoutGlobalScope(\App\Models\CompanyableScope::class);
        }

        return $query;
    }

    protected function companyOptionsQuery(Request $request)
    {
        $query = Company::query()->orderBy('name');

        if ($this->bypassesCompanyScope($request)) {
            $query->withoutGlobalScope(\App\Models\CompanyableScope::class);
        }

        return $query;
    }

    protected function usesRestrictedCompanyScope(Request $request): bool
    {
        return $this->fullMultipleCompaniesEnabled()
            && !$request->user()?->isSuperUser()
            && $request->user()?->company_id !== null;
    }

    protected function defaultCompanyId(Request $request): ?int
    {
        if (!$this->usesRestrictedCompanyScope($request)) {
            return null;
        }

        return (int) $request->user()->company_id;
    }

    protected function bypassesCompanyScope(Request $request): bool
    {
        return $this->fullMultipleCompaniesEnabled()
            && !$request->user()?->isSuperUser()
            && $request->user()?->company_id === null;
    }

    protected function fullMultipleCompaniesEnabled(): bool
    {
        return (int) (Setting::getSettings()?->full_multiple_companies_support ?? 0) === 1;
    }
}
