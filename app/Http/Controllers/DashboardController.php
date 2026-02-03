<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Http\RedirectResponse;
use \Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Collection;
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
        $counts['component'] = $user->can('view', \App\Models\Component::class)
            ? \App\Models\Component::count()
            : 0;
        $counts['user'] = $user->can('view', \App\Models\User::class)
            ? \App\Models\Company::scopeCompanyables($user)->count()
            : 0;
        $counts['grand_total'] = $counts['asset'] + $counts['accessory'] + $counts['license'] + $counts['consumable'];

        if ((! file_exists(storage_path().'/oauth-private.key')) || (! file_exists(storage_path().'/oauth-public.key'))) {
            Artisan::call('migrate', ['--force' => true]);
            Artisan::call('passport:install', ['--no-interaction' => true]);
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
                'icon' => 'box-open',
                'description' => 'Goedgekeurd en klaar voor verkoop of uitlevering.',
            ],
            [
                'status' => 'Sold',
                'icon' => 'check',
                'description' => 'Reeds verkocht of uit voorraad verwijderd.',
            ],
            [
                'status' => 'Broken / Parts',
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
                'icon' => 'undo-alt',
                'description' => 'Retour ontvangen en wacht op opnieuw beoordelen.',
            ],
        ]);

        $statusLabels = Statuslabel::select(['id', 'name', 'color'])
            ->whereIn('name', $definitions->pluck('status')->all())
            ->get()
            ->keyBy('name');

        return $definitions->map(function (array $definition) use ($statusLabels) {
            $status = $statusLabels->get($definition['status']);
            $label = RefurbStatus::displayName($definition['status']);

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
