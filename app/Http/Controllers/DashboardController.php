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
                'name' => 'Stand-by',
                'icon' => 'pause',
                'description' => 'Afwachting intake of gegevenswiping',
            ],
            [
                'name' => 'Being Processed',
                'icon' => 'cogs',
                'description' => 'Tests, imaging of reparatie in uitvoering',
            ],
            [
                'name' => 'QA Hold',
                'icon' => 'flag',
                'description' => 'Wacht op accessoires, cosmetica of finale QA',
            ],
            [
                'name' => 'Ready for Sale',
                'icon' => 'box-open',
                'description' => 'Vrijgegeven voor verkoop of distributie',
            ],
            [
                'name' => 'Sold',
                'icon' => 'check',
                'description' => 'Verlaten de voorraad na afronding order',
            ],
            [
                'name' => 'Broken / Parts',
                'icon' => 'tools',
                'description' => 'Wordt gestript voor onderdelen of diagnose',
            ],
            [
                'name' => 'Internal Use',
                'icon' => 'building',
                'description' => 'In gebruik bij interne teams of labs',
            ],
            [
                'name' => 'Archived',
                'icon' => 'archive',
                'description' => 'Historisch voorbeeld, niet actief',
            ],
            [
                'name' => 'Returned / RMA',
                'icon' => 'undo-alt',
                'description' => 'Retour ingestroomd; wacht op beoordeling',
            ],
        ]);

        $statusLabels = Statuslabel::select(['id', 'name', 'color'])
            ->whereIn('name', $definitions->pluck('name')->all())
            ->get()
            ->keyBy('name');

        return $definitions->map(function (array $definition) use ($statusLabels) {
            $status = $statusLabels->get($definition['name']);

            return [
                'label' => $definition['name'],
                'icon' => $definition['icon'],
                'description' => $definition['description'],
                'status_id' => $status?->id,
                'color' => $status?->color,
                'available' => (bool) $status,
            ];
        });
    }
}
