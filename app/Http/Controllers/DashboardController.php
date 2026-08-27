<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use \Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Collection;
use App\Models\ComponentInstance;
use App\Models\Statuslabel;
use App\Support\RefurbStatus;


/**
 * This controller handles all actions related to the Admin Dashboard
 * for the Snipe-IT Asset Management application.
 *
 * @author A. Gianotto <snipe@snipe.net>
 * @version v1.0
 */
class DashboardController extends Controller
{
    /**
     * Display the main dashboard for signed-in users.
     *
     * @author [A. Gianotto] [<snipe@snipe.net>]
     * @since [v1.0]
     */
    public function index() : View | RedirectResponse
    {
        $asset_stats = null;
        $user = auth()->user();

        $counts['asset'] = $user->can('view', \App\Models\Asset::class)
            ? \App\Models\Asset::AssetsForShow()->count()
            : 0;
        $counts['accessory'] = $user->can('view', \App\Models\Accessory::class)
            ? \App\Models\Accessory::count()
            : 0;
        $counts['license'] = $user->can('view', \App\Models\License::class)
            ? \App\Models\License::assetcount()
            : 0;
        $counts['consumable'] = $user->can('view', \App\Models\Consumable::class)
            ? \App\Models\Consumable::count()
            : 0;
        $counts['component'] = $user->can('view', ComponentInstance::class)
            ? ComponentInstance::count()
            : 0;
        $counts['user'] = $user->can('view', \App\Models\User::class)
            ? \App\Models\Company::scopeCompanyables($user)->count()
            : 0;
        $counts['grand_total'] = $counts['asset'] + $counts['accessory'] + $counts['license'] + $counts['consumable'] + $counts['component'];

        if ((! file_exists(storage_path().'/oauth-private.key')) || (! file_exists(storage_path().'/oauth-public.key'))) {
            Log::critical('Laravel Passport signing keys are unavailable.');

            if (app()->environment('production')) {
                abort(503, 'OAuth signing keys are unavailable. Contact the system administrator.');
            }
        }

        $refurbFilters = $this->buildRefurbFilters();

        return view('dashboard')
            ->with('asset_stats', $asset_stats)
            ->with('counts', $counts)
            ->with('refurbFilters', $refurbFilters);
    }

    /**
     * Prepare the list of refurbishment status filters for the dashboard.
     *
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    protected function buildRefurbFilters(): Collection
    {
        $definitions = collect([
            [
                'status' => 'Stand-by',
                'icon' => 'pause',
                'description' => 'Wachtend op intake of gegevenswiping.',
            ],
            [
                'status' => 'Being Processed',
                'icon' => 'cogs',
                'description' => 'Actief in test-, wipe- of herstelproces.',
            ],
            [
                'status' => 'QA Hold',
                'icon' => 'flag',
                'description' => 'Blokkeert tot accessoires, cosmetica of QA-uitkomst gereed zijn.',
            ],
            [
                'status' => 'Ready for Sale',
                'lifecycle_stage' => Statuslabel::LIFECYCLE_READY_FOR_SALE,
                'icon' => 'box-open',
                'description' => 'Goedgekeurd en klaar voor verkoop of uitlevering.',
            ],
            [
                'status' => 'Sold',
                'lifecycle_stage' => Statuslabel::LIFECYCLE_SOLD,
                'icon' => 'check',
                'description' => 'Reeds verkocht of uit voorraad verwijderd.',
            ],
            [
                'status' => 'Broken / Parts',
                'lifecycle_stage' => Statuslabel::LIFECYCLE_BROKEN_PARTS,
                'icon' => 'tools',
                'description' => 'Niet verkoopbaar; gebruikt voor onderdelen of diagnose.',
            ],
            [
                'status' => 'Internal Use',
                'icon' => 'building',
                'description' => 'Toegekend aan interne teams of labopstellingen.',
            ],
            [
                'status' => 'Archived',
                'icon' => 'archive',
                'description' => 'Historisch dossier; niet actief in omloop.',
            ],
            [
                'status' => 'Returned / RMA',
                'lifecycle_stage' => Statuslabel::LIFECYCLE_RETURNED,
                'icon' => 'undo-alt',
                'description' => 'Retour ontvangen en wacht op opnieuw beoordelen.',
            ],
        ]);

        $statusLabels = Statuslabel::select(['id', 'name', 'color', 'lifecycle_stage'])->get();
        $statusLabelsByName = $statusLabels->keyBy('name');
        $statusLabelsByStage = $statusLabels
            ->whereNotNull('lifecycle_stage')
            ->keyBy('lifecycle_stage');

        return $definitions->map(function (array $definition) use ($statusLabelsByName, $statusLabelsByStage) {
            $status = isset($definition['lifecycle_stage'])
                ? $statusLabelsByStage->get($definition['lifecycle_stage'])
                : $statusLabelsByName->get($definition['status']);
            $label = $status?->name ?? RefurbStatus::displayName($definition['status']);

            return [
                'label' => $label,
                'icon' => $definition['icon'],
                'description' => $definition['description'],
                'status_id' => $status?->id,
                'color' => $status?->color,
                'available' => (bool) $status,
            ];
        });
    }
}
