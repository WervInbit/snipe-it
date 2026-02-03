<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class StartController extends Controller
{
    public function index(): View | RedirectResponse
    {
        return redirect()->route('home');
    }
}

