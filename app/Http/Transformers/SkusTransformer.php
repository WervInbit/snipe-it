<?php

namespace App\Http\Transformers;

use App\Helpers\Helper;
use App\Models\Sku;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Gate;

class SkusTransformer
{
    public function transformSkus(Collection $skus, $total)
    {
        $array = [];
        foreach ($skus as $sku) {
            $array[] = self::transformSku($sku);
        }

        return (new DatatablesTransformer)->transformDatatables($array, $total);
    }

    public function transformSku(Sku $sku)
    {
        return [
            'id' => (int) $sku->id,
            'name' => e($sku->name),
            'model' => $sku->model ? [
                'id' => (int) $sku->model->id,
                'name' => e($sku->model->name),
            ] : null,
            'created_at' => Helper::getFormattedDateObject($sku->created_at, 'datetime'),
            'updated_at' => Helper::getFormattedDateObject($sku->updated_at, 'datetime'),
            'available_actions' => [
                'update' => Gate::allows('update', Sku::class),
                'delete' => ($sku->assets()->count() === 0) && Gate::allows('delete', Sku::class),
            ],
        ];
    }
}
