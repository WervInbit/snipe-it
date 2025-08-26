<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;

class StartController extends Controller
{
    public function index(): View
    {
        $user = auth()->user();

        if ($user->isSuperUser()) {
            return view('start.superuser');
        }

        if ($user->hasAccess('admin')) {
            return view('start.admin');
        }

        if ($user->hasAccess('supervisor')) {
            return view('start.supervisor');
        }

        if ($user->hasAccess('senior-refurbisher')) {
            return view('start.senior-refurbisher');
        }

        if ($user->hasAccess('refurbisher')) {
            return view('start.refurbisher');
        }

        return view('start.user');
    }
}

