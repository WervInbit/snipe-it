<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Gate;

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

}
