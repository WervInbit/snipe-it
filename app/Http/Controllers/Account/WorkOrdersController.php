<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\ComponentEvent;
use App\Models\WorkOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class WorkOrdersController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('portal.view');

        $user = $request->user();
        $workOrders = WorkOrder::query()
            ->withoutGlobalScope(\App\Models\CompanyableScope::class)
            ->visibleTo($user)
            ->with(['company', 'primaryContact'])
            ->withCount(['assets', 'tasks'])
            ->latest('id')
            ->paginate(25);

        return view('account.work-orders.index', compact('workOrders'));
    }

    public function show(Request $request, WorkOrder $workOrder): View
    {
        Gate::authorize('portal.view');
        $this->authorize('view', $workOrder);

        $workOrder->load([
            'company',
            'primaryContact',
            'assets.asset',
            'tasks' => fn ($query) => $query->where('customer_visible', true)->with(['assignee', 'workOrderAsset']),
        ]);

        return view('account.work-orders.show', [
            'workOrder' => $workOrder,
            'componentEvents' => $workOrder->portalShowsComponents() ? $this->componentEventsFor($workOrder) : collect(),
        ]);
    }

    protected function componentEventsFor(WorkOrder $workOrder)
    {
        return ComponentEvent::query()
            ->with([
                'componentInstance.componentDefinition',
                'fromAsset',
                'toAsset',
                'relatedWorkOrderTask',
            ])
            ->where(function ($query) use ($workOrder): void {
                $query->where('related_work_order_id', $workOrder->id)
                    ->orWhereIn('related_work_order_task_id', $workOrder->tasks()->select('id'));
            })
            ->where(function ($query): void {
                $query->whereNull('related_work_order_task_id')
                    ->orWhereHas('relatedWorkOrderTask', fn ($taskQuery) => $taskQuery->where('customer_visible', true));
            })
            ->orderByDesc('created_at')
            ->get();
    }
}
