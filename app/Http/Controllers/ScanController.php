<?php

namespace App\Http\Controllers;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use App\Models\Asset;
use App\Models\Accessory;
use App\Models\Component;
use App\Models\Consumable;

class ScanController extends Controller
{
    /**
     * Display the asset scanning page.
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function index()
    {
        Gate::authorize('scanning');
        return view('scan.index');
    }

    /**
     * Resolve a scanned code and redirect to the appropriate item page.
     */
    public function resolve(string $code): RedirectResponse
    {
        Gate::authorize('scanning');
        $code = trim($code);
        if ($code === '') {
            return redirect()->route('hardware.index')->with('error', 'Empty scan code');
        }

        if (str_contains($code, ':')) {
            [$prefix, $id] = explode(':', $code, 2);
            $prefix = strtoupper(trim($prefix));
            $id = trim($id);

            switch ($prefix) {
                case 'A':
                case 'ASSET':
                    if ($asset = Asset::where('qr_uid', $id)->first()) {
                        $this->authorize('view', $asset);
                        return redirect()->route('hardware.show', $asset->id);
                    }
                    break;
                case 'ACC':
                case 'ACCESSORY':
                    if (class_exists(Accessory::class) && ($item = Accessory::where('qr_uid', $id)->first())) {
                        $this->authorize('view', $item);
                        return redirect()->route('accessories.show', $item->id);
                    }
                    break;
                case 'CMP':
                case 'COMPONENT':
                    if (class_exists(Component::class) && ($item = Component::where('qr_uid', $id)->first())) {
                        $this->authorize('view', $item);
                        return redirect()->route('components.show', $item->id);
                    }
                    break;
                case 'CON':
                case 'CONSUMABLE':
                    if (class_exists(Consumable::class) && ($item = Consumable::where('qr_uid', $id)->first())) {
                        $this->authorize('view', $item);
                        return redirect()->route('consumables.show', $item->id);
                    }
                    break;
            }

            return redirect()->route('hardware.index')->with('warning', 'Item not found for scanned code');
        }

        // Fallback: treat as asset tag
        return redirect()->route('findbytag/hardware', ['any' => $code]);
    }

}
