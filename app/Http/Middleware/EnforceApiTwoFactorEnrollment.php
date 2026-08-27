<?php

namespace App\Http\Middleware;

use App\Helpers\Helper;
use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnforceApiTwoFactorEnrollment
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        if ($user === null) {
            return $next($request);
        }

        $settings = Setting::getSettings();
        if ($settings === null) {
            return $next($request);
        }

        $mode = (string) $settings->two_factor_enabled;
        if ($mode !== '1' && $mode !== '2') {
            return $next($request);
        }

        if ($mode === '1' && (string) $user->two_factor_optin !== '1') {
            return $next($request);
        }

        if ((string) $user->two_factor_enrolled !== '1') {
            return response()->json(
                Helper::formatStandardApiResponse(
                    'error',
                    null,
                    trans('auth/message.two_factor.please_enroll')
                ),
                Response::HTTP_FORBIDDEN
            );
        }

        return $next($request);
    }
}
