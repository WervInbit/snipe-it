<?php

namespace App\Http\Middleware;

use Closure;
use Laravel\Passport\Http\Middleware\CreateFreshApiToken;

class IssueFreshApiTokenIfTwoFactorComplete extends CreateFreshApiToken
{
    public function handle($request, Closure $next, $guard = null)
    {
        if (! CheckForTwoFactor::isComplete($request)) {
            return $next($request);
        }

        return parent::handle($request, $next, $guard);
    }
}
