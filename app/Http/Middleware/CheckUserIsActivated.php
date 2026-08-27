<?php

namespace App\Http\Middleware;

use App\Helpers\Helper;
use Closure;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckUserIsActivated
{
    /**
     * The Guard implementation.
     *
     * @var Guard
     */
    protected $auth;

    /**
     * Create a new filter instance.
     *
     * @param  Guard  $auth
     * @return void
     */
    public function __construct(Guard $auth)
    {
        $this->auth = $auth;
    }

    /**
     * Handle an incoming request.
     *
     * @param  Request  $request
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // If there is a user AND the user is NOT activated, send them to the login page
        // This prevents people who still have active sessions logged in and their status gets toggled
        // to inactive (aka unable to login)
        if (($request->user()) && (! $request->user()->isActivated())) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json(
                    Helper::formatStandardApiResponse('error', null, trans('general.unauthorized')),
                    Response::HTTP_UNAUTHORIZED
                );
            }

            Auth::logout();

            return redirect()->guest('login');
        }

        return $next($request);
    }
}
