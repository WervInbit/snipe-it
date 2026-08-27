<?php
/*! \mainpage Inbit Device Refurbishment Platform Code Documentation
 *
 * \section intro_sec Introduction
 *
 * This independent refurbishment-focused fork derives from Snipe-IT and keeps
 * its upstream attribution and license. Its application and API contracts have
 * diverged substantially from official Snipe-IT.
 *
 * Use README.md for the supported development entry point,
 * docs/production-deployment.md for production operations,
 * docs/api-compatibility.md for the API boundary, and CONTRIBUTING.md for
 * contributor requirements. Upstream installation and API documentation do
 * not define this fork.
 */

namespace App\Http\Controllers;

use App\Models\Accessory;
use App\Models\Actionlog;
use App\Models\Asset;
use App\Models\AssetModel;
use App\Models\Component;
use App\Models\ComponentInstance;
use App\Models\Consumable;
use App\Models\License;
use App\Models\ModelNumber;
use App\Models\Location;
use App\Models\Maintenance;
use App\Models\User;
use App\Models\WorkOrder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

abstract class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    static $map_object_type = [
        'accessories' => Accessory::class,
        'maintenances' => Maintenance::class,
        'assets' => Asset::class,
        'components' => Component::class,
        'component-instances' => ComponentInstance::class,
        'consumables' => Consumable::class,
        'hardware' => Asset::class,
        'licenses' => License::class,
        'locations' => Location::class,
        'models' => AssetModel::class,
        'model-numbers' => ModelNumber::class,
        'users' => User::class,
        'work-orders' => WorkOrder::class,
    ];

    static $map_storage_path = [
        'accessories' => 'private_uploads/accessories/',
        'maintenances' => 'private_uploads/maintenances/',
        'assets' => 'private_uploads/assets/',
        'components' => 'private_uploads/components/',
        'component-instances' => 'private_uploads/component_instances/',
        'consumables' => 'private_uploads/consumables/',
        'hardware' => 'private_uploads/assets/',
        'licenses' => 'private_uploads/licenses/',
        'locations' => 'private_uploads/locations/',
        'models' => 'private_uploads/models/',
        'model-numbers' => 'private_uploads/model_numbers/',
        'users' => 'private_uploads/users/',
        'work-orders' => 'private_uploads/work_orders/',
    ];

    static $map_file_prefix= [
        'accessories' => 'accessory',
        'maintenances' => 'maintenance',
        'assets' => 'asset',
        'components' => 'component',
        'component-instances' => 'component-instance',
        'consumables' => 'consumable',
        'hardware' => 'asset',
        'licenses' => 'license',
        'locations' => 'location',
        'models' => 'model',
        'model-numbers' => 'model-number',
        'users' => 'user',
        'work-orders' => 'work-order',
    ];

    public function __construct()
    {
        view()->share('signedIn', Auth::check());
        view()->share('user', auth()->user());
    }

    protected function resolveOffset(Request $request, int $total, int $limit): int
    {
        if ($total === 0) {
            return 0;
        }

        $requestedOffset = max(0, (int) $request->input('offset', 0));

        if ($requestedOffset < $total) {
            return $requestedOffset;
        }

        $remainder = $total % $limit;
        $lastPageOffset = $remainder === 0 ? max(0, $total - $limit) : $total - $remainder;

        return $lastPageOffset;
    }

    protected function resolveUploadedFileParent(string $objectType, $id): ?Model
    {
        abort_unless(
            isset(
                static::$map_object_type[$objectType],
                static::$map_storage_path[$objectType],
                static::$map_file_prefix[$objectType]
            ),
            404
        );

        $modelClass = static::$map_object_type[$objectType];

        return $modelClass::query()->find($id);
    }

    protected function uploadedFileLogQuery(string $objectType, Model $parent): Builder
    {
        return Actionlog::query()
            ->where('action_type', 'uploaded')
            ->whereNotNull('filename')
            ->where('item_type', static::$map_object_type[$objectType])
            ->where('item_id', $parent->getKey());
    }
}
