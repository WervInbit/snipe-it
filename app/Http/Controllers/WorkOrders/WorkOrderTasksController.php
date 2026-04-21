<?php

namespace App\Http\Controllers\WorkOrders;

use App\Http\Controllers\Controller;
use App\Models\WorkOrder;
use App\Models\WorkOrderAsset;
use App\Models\WorkOrderTask;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class WorkOrderTasksController extends Controller
{
    public function store(Request $request, WorkOrder $workOrder): RedirectResponse
    {
        $this->authorize('update', $workOrder);

        $data = $this->validatedData($request, $workOrder);
        $task = new WorkOrderTask($data);
        $task->work_order_id = $workOrder->id;
        $task->save();

        return redirect()
            ->to(route('work-orders.show', $workOrder) . '#tasks')
            ->with('success', __('Task added to work order.'));
    }

    public function update(Request $request, WorkOrder $workOrder, WorkOrderTask $workOrderTask): RedirectResponse
    {
        $this->authorize('update', $workOrder);
        $this->ensureChildBelongsToWorkOrder($workOrder, $workOrderTask);

        $workOrderTask->fill($this->validatedData($request, $workOrder));
        $workOrderTask->save();

        return redirect()
            ->to(route('work-orders.show', $workOrder) . '#tasks')
            ->with('success', __('Task updated.'));
    }

    public function destroy(WorkOrder $workOrder, WorkOrderTask $workOrderTask): RedirectResponse
    {
        $this->authorize('update', $workOrder);
        $this->ensureChildBelongsToWorkOrder($workOrder, $workOrderTask);

        $workOrderTask->delete();

        return redirect()
            ->to(route('work-orders.show', $workOrder) . '#tasks')
            ->with('success', __('Task removed from work order.'));
    }

    protected function validatedData(Request $request, WorkOrder $workOrder): array
    {
        $data = $request->validate([
            'work_order_asset_id' => ['nullable', 'integer', 'exists:work_order_assets,id'],
            'task_type' => ['required', 'string', 'max:100'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['required', Rule::in(array_keys(WorkOrderTask::statusOptions()))],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
            'customer_visible' => ['sometimes', 'boolean'],
            'customer_status_label' => ['nullable', 'string', 'max:255'],
            'notes_internal' => ['nullable', 'string'],
            'notes_customer' => ['nullable', 'string'],
            'started_at' => ['nullable', 'date'],
            'completed_at' => ['nullable', 'date'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $data['customer_visible'] = $request->boolean('customer_visible');

        if (!empty($data['work_order_asset_id'])) {
            $assetBelongs = WorkOrderAsset::query()
                ->whereKey($data['work_order_asset_id'])
                ->where('work_order_id', $workOrder->id)
                ->exists();

            if (!$assetBelongs) {
                abort(422, 'Selected device does not belong to this work order.');
            }
        }

        return $data;
    }

    protected function ensureChildBelongsToWorkOrder(WorkOrder $workOrder, WorkOrderTask $workOrderTask): void
    {
        abort_unless((int) $workOrderTask->work_order_id === (int) $workOrder->id, 404);
    }
}
