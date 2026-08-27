<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ForkDocumentationController extends Controller
{
    public function apiCompatibility(): View
    {
        $markdown = File::get(base_path('docs/api-compatibility.md'));
        $content = Str::markdown($markdown, [
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]);

        return view('help.api-compatibility', compact('content'));
    }
}
