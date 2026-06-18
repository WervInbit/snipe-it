<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SessionKeepaliveController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $request->session()->put('last_keepalive_at', now()->timestamp);

        return response()
            ->json([
                'ok' => true,
                'expires_in' => (int) config('session.lifetime', 0) * 60,
            ])
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
    }
}
