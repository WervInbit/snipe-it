<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\Maintenance;
use Illuminate\Contracts\View\View;

/**
 * Read-only access to imported historical asset-maintenance records.
 */
class MaintenancesController extends Controller
{
    public function index(): View
    {
        $this->authorize('view', Asset::class);

        return view('maintenances.index');
    }

    public function show(Maintenance $maintenance): View
    {
        $this->authorize('view', $maintenance);

        return view('maintenances.view')->with('maintenance', $maintenance);
    }
}
