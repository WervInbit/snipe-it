<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Http\RedirectResponse;
use \Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Collection;
use App\Models\Statuslabel;


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
     * Check authorization and display admin dashboard, otherwise display
     * the user's checked-out assets.
     *
     * @author [A. Gianotto] [<snipe@snipe.net>]
     * @since [v1.0]
     */
    public function index() : View | RedirectResponse
    {
        // Show the page
        if (auth()->user()->hasAccess('admin') || auth()->user()->hasAccess('supervisor')) {
            $asset_stats = null;

            $counts['asset'] = \App\Models\Asset::count();
            $counts['accessory'] = \App\Models\Accessory::count();
            $counts['license'] = \App\Models\License::assetcount();
            $counts['consumable'] = \App\Models\Consumable::count();
            $counts['component'] = \App\Models\Component::count();
            $counts['user'] = \App\Models\Company::scopeCompanyables(auth()->user())->count();
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
        } else {
            Session::reflash();

            // Redirect to the profile page
            return redirect()->intended('account/view-assets');
        }
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
                'label' => 'Stand-by',
                'icon' => 'pause',
                'description' => 'Wachtend op intake of gegevenswiping.',
            ],
            [
                'status' => 'Being Processed',
                'label' => 'In verwerking',
                'icon' => 'cogs',
                'description' => 'Actief in test-, wipe- of herstelproces.',
            ],
            [
                'status' => 'QA Hold',
                'label' => 'QA wacht',
                'icon' => 'flag',
                'description' => 'Blokkeert tot accessoires, cosmetica of QA-uitkomst gereed zijn.',
            ],
            [
                'status' => 'Ready for Sale',
                'label' => 'Verkoopklaar',
                'icon' => 'box-open',
                'description' => 'Goedgekeurd en klaar voor verkoop of uitlevering.',
            ],
            [
                'status' => 'Sold',
                'label' => 'Verkocht',
                'icon' => 'check',
                'description' => 'Reeds verkocht of uit voorraad verwijderd.',
            ],
            [
                'status' => 'Broken / Parts',
                'label' => 'Defect / Onderdelen',
                'icon' => 'tools',
                'description' => 'Niet verkoopbaar; gebruikt voor onderdelen of diagnose.',
            ],
            [
                'status' => 'Internal Use',
                'label' => 'Intern gebruik',
                'icon' => 'building',
                'description' => 'Toegekend aan interne teams of labopstellingen.',
            ],
            [
                'status' => 'Archived',
                'label' => 'Gearchiveerd',
                'icon' => 'archive',
                'description' => 'Historisch dossier; niet actief in omloop.',
            ],
            [
                'status' => 'Returned / RMA',
                'label' => 'Retour / RMA',
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

            return [
                'label' => $definition['label'] ?? $definition['status'],
                'icon' => $definition['icon'],
                'description' => $definition['description'],
                'status_id' => $status?->id,
                'color' => $status?->color,
                'available' => (bool) $status,
            ];
        });
    }
}
