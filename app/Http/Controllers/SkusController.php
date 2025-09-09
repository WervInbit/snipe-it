<?php

namespace App\Http\Controllers;

use App\Models\Sku;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Contracts\View\View;

class SkusController extends Controller
{
    public function index(): View
    {
        $this->authorize('view', Sku::class);
        return view('skus/index');
    }

    public function create(Request $request): View
    {
        $this->authorize('create', Sku::class);
        $sku = new Sku();
        if ($request->filled('model_id')) {
            $sku->model_id = $request->input('model_id');
        }
        return view('skus/edit')->with('item', $sku);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Sku::class);
        $sku = new Sku();
        $sku->fill($request->only('name', 'model_id'));
        if ($sku->save()) {
            return redirect()->route('skus.index')->with('success', trans('admin/skus/message.create.success'));
        }
        return redirect()->back()->withInput()->withErrors($sku->getErrors());
    }

    public function show(Sku $sku): View
    {
        $this->authorize('view', Sku::class);
        return view('skus/view', compact('sku'));
    }

    public function edit(Sku $sku): View
    {
        $this->authorize('update', Sku::class);
        return view('skus/edit')->with('item', $sku);
    }

    public function update(Request $request, Sku $sku): RedirectResponse
    {
        $this->authorize('update', Sku::class);
        $sku->fill($request->only('name', 'model_id'));
        if ($sku->save()) {
            return redirect()->route('skus.index')->with('success', trans('admin/skus/message.update.success'));
        }
        return redirect()->back()->withInput()->withErrors($sku->getErrors());
    }

    public function destroy(Sku $sku): RedirectResponse
    {
        $this->authorize('delete', Sku::class);
        if ($sku->assets()->count() > 0) {
            return redirect()->route('skus.index')->with('error', trans('admin/skus/message.assoc_assets'));
        }
        $sku->delete();
        return redirect()->route('skus.index')->with('success', trans('admin/skus/message.delete.success'));
    }
}
