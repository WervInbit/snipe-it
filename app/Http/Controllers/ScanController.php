<?php

namespace App\Http\Controllers;

class ScanController extends Controller
{
    /**
     * Display the asset scanning page.
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function index()
    {
        \Illuminate\Support\Facades\Gate::authorize('scanning');
        return view('scan.index');
    }
}
