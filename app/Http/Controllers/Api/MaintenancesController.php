<?php

namespace App\Http\Controllers\Api;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Http\Transformers\MaintenancesTransformer;
use App\Models\Asset;
use App\Models\Company;
use App\Models\Maintenance;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Read-only API access to imported historical asset-maintenance records.
 */
class MaintenancesController extends Controller
{
    public function index(Request $request): JsonResponse | array
    {
        $this->authorize('view', Asset::class);

        $maintenances = Maintenance::select('maintenances.*')
            ->with(
                'asset',
                'asset.model',
                'asset.location',
                'asset.defaultLoc',
                'supplier',
                'asset.company',
                'asset.assetstatus',
                'adminuser'
            );

        if ($request->filled('search')) {
            $maintenances = $maintenances->TextSearch($request->input('search'));
        }

        if ($request->filled('asset_id')) {
            $maintenances->where('asset_id', $request->input('asset_id'));
        }

        if ($request->filled('supplier_id')) {
            $maintenances->where('maintenances.supplier_id', $request->input('supplier_id'));
        }

        if ($request->filled('created_by')) {
            $maintenances->where('maintenances.created_by', $request->input('created_by'));
        }

        if ($request->filled('asset_maintenance_type')) {
            $maintenances->where('asset_maintenance_type', $request->input('asset_maintenance_type'));
        }

        $limit = app('api_limit_value');
        $allowedColumns = [
            'id',
            'name',
            'asset_maintenance_time',
            'asset_maintenance_type',
            'cost',
            'start_date',
            'completion_date',
            'notes',
            'asset_tag',
            'asset_name',
            'serial',
            'created_by',
            'supplier',
            'location',
            'is_warranty',
            'status_label',
        ];

        $order = $request->input('order') === 'asc' ? 'asc' : 'desc';
        $sort = in_array($request->input('sort'), $allowedColumns, true)
            ? $request->input('sort')
            : 'created_at';

        $maintenances = match ($sort) {
            'created_by' => $maintenances->OrderByCreatedBy($order),
            'supplier' => $maintenances->OrderBySupplier($order),
            'asset_tag' => $maintenances->OrderByTag($order),
            'asset_name' => $maintenances->OrderByAssetName($order),
            'serial' => $maintenances->OrderByAssetSerial($order),
            'location' => $maintenances->OrderLocationName($order),
            'status_label' => $maintenances->OrderStatusName($order),
            default => $maintenances->orderBy($sort, $order),
        };

        $total = $maintenances->count();
        $offset = $this->resolveOffset($request, $total, $limit);
        $rows = $maintenances->skip($offset)->take($limit)->get();

        return (new MaintenancesTransformer())->transformMaintenances($rows, $total);
    }

    public function show($maintenanceId): JsonResponse | array
    {
        $this->authorize('view', Asset::class);

        $maintenance = Maintenance::findOrFail($maintenanceId);
        if (! Company::isCurrentUserHasAccess($maintenance->asset)) {
            return response()->json(
                Helper::formatStandardApiResponse(
                    'error',
                    null,
                    'You cannot view a maintenance for that asset'
                )
            );
        }

        return (new MaintenancesTransformer())->transformMaintenance($maintenance);
    }
}
