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

        return view('start.user');
    }
}

