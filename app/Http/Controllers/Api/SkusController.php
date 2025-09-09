<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Transformers\SelectlistTransformer;
use App\Models\Sku;
use Illuminate\Http\Request;

class SkusController extends Controller
{
    public function selectlist(Request $request) : array
    {
        $this->authorize('view.selectlists');
        $skus = Sku::select(['id', 'name'])
            ->where('model_id', $request->input('model_id'))
            ->orderBy('name', 'ASC')
            ->paginate(50);

        foreach ($skus as $sku) {
            $sku->use_text = $sku->name;
            $sku->use_image = null;
        }

        return (new SelectlistTransformer)->transformSelectlist($skus);
    }
}
