<?php

namespace App\Http\Controllers\WorkOrders;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\WorkOrder;
use App\Models\WorkOrderAsset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class WorkOrderAssetsController extends Controller
{
    public function store(Request $request, WorkOrder $workOrder): RedirectResponse
    {
        $this->authorize('update', $workOrder);

        $data = $this->validatedData($request);
        $asset = new WorkOrderAsset($data);
        $asset->work_order_id = $workOrder->id;
        $this->syncSnapshotFields($asset);
        $asset->save();

        return redirect()
            ->to(route('work-orders.show', $workOrder) . '#devices')
            ->with('success', __('Device added to work order.'));
    }

    public function update(Request $request, WorkOrder $workOrder, WorkOrderAsset $workOrderAsset): RedirectResponse
    {
        $this->authorize('update', $workOrder);
        $this->ensureChildBelongsToWorkOrder($workOrder, $workOrderAsset);

        $workOrderAsset->fill($this->validatedData($request));
        $this->syncSnapshotFields($workOrderAsset);
        $workOrderAsset->save();

        return redirect()
            ->to(route('work-orders.show', $workOrder) . '#devices')
            ->with('success', __('Device updated.'));
    }

    public function destroy(WorkOrder $workOrder, WorkOrderAsset $workOrderAsset): RedirectResponse
    {
        $this->authorize('update', $workOrder);
        $this->ensureChildBelongsToWorkOrder($workOrder, $workOrderAsset);

        $workOrderAsset->delete();

        return redirect()
            ->to(route('work-orders.show', $workOrder) . '#devices')
            ->with('success', __('Device removed from work order.'));
    }

    protected function validatedData(Request $request): array
    {
        $data = $request->validate([
            'asset_id' => ['nullable', 'integer', 'exists:assets,id'],
            'customer_label' => ['nullable', 'string', 'max:255', 'required_without:asset_id'],
            'asset_tag_snapshot' => ['nullable', 'string', 'max:255'],
            'serial_snapshot' => ['nullable', 'string', 'max:255'],
            'qr_reference' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', 'max:100'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        if (!empty($data['asset_id']) && !Asset::query()->whereKey($data['asset_id'])->exists()) {
            throw ValidationException::withMessages([
                'asset_id' => __('The selected asset is outside your company scope.'),
            ]);
        }

        return $data;
    }

    protected function syncSnapshotFields(WorkOrderAsset $workOrderAsset): void
    {
        if (!$workOrderAsset->asset_id) {
            return;
        }

        $asset = Asset::query()->find($workOrderAsset->asset_id);

        if (!$asset) {
            return;
        }

        $workOrderAsset->asset_tag_snapshot = $asset->asset_tag;
        $workOrderAsset->serial_snapshot = $asset->serial;
    }

    protected function ensureChildBelongsToWorkOrder(WorkOrder $workOrder, WorkOrderAsset $workOrderAsset): void
    {
        abort_unless((int) $workOrderAsset->work_order_id === (int) $workOrder->id, 404);
    }
}
