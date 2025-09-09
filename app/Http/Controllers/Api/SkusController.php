<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Transformers\SelectlistTransformer;
use App\Models\Asset;
use Illuminate\Http\Request;

class SkusController extends Controller
{
    public function selectlist(Request $request) : array
    {
        $this->authorize('view.selectlists');
        $assets = Asset::select(['id', 'asset_tag as name'])
            ->where('model_id', $request->input('model_id'))
            ->orderBy('asset_tag', 'ASC')
            ->paginate(50);

        foreach ($assets as $asset) {
            $asset->use_text = $asset->name;
            $asset->use_image = null;
        }

        return (new SelectlistTransformer)->transformSelectlist($assets);
    }
}
