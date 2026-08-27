<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;

class CheckForTwoFactor
{
    /**
     * Routes to ignore for Two Factor Auth
     */
    public const IGNORE_ROUTES = ['two-factor', 'two-factor-enroll', 'setup', 'logout'];

    /**
     * Determine whether this request's authenticated session has completed
     * the configured two-factor challenge.
     */
    public static function isComplete(Request $request): bool
    {
        $user = $request->user();

        if ($user === null) {
            return true;
        }

        $settings = Setting::getSettings();
        if ($settings === null) {
            return true;
        }

        $mode = (string) $settings->two_factor_enabled;
        if ($mode !== '1' && $mode !== '2') {
            return true;
        }

        if ($mode === '1' && (string) $user->two_factor_optin !== '1') {
            return true;
        }

        return $request->hasSession()
            && (string) $request->session()->get('2fa_authed') === (string) $user->getAuthIdentifier();
    }

    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure                 $next
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Skip the logic if the user is on the two factor pages or the setup pages

        // TODO - what we have below only works because our ROUTE uri's look _exactly_ like the route *names*.
        // The problem is that, in the new(-ish) Laravel routing system, the route-name doesn't match if the route _verb_ is wrong.
        // so we can have a blade that POST's to a route('two-factor') - but that route *name* is only matched when the method is GET
        // because we attached the name to the GET, not to the POST (as route names *SHOULD* be unique in Laravel)
        // there has got to be a better way to do this, but this is the best I could come up with for now.
        if (in_array($request->route()->getName(), self::IGNORE_ROUTES) || in_array($request->route()->uri(), self::IGNORE_ROUTES)) {
            return $next($request);
        }

        if (! self::isComplete($request)) {
            $user = $request->user();

            self::rememberIntendedUrl($request);

            // Otherwise make sure they're enrolled and show them the 2FA code screen
            if (($user->two_factor_secret != '') && ($user->two_factor_enrolled == '1')) {
                return redirect()->route('two-factor')->with('info', trans('auth/message.two_factor.enter_two_factor_code'));
            }

            return redirect()->route('two-factor-enroll')->with('success', trans('auth/message.two_factor.please_enroll'));
        }

        return $next($request);
    }

    /**
     * Preserve an interactive deep link until the two-factor step completes.
     */
    private static function rememberIntendedUrl(Request $request): void
    {
        if ($request->isMethod('GET') && ! $request->expectsJson()) {
            $request->session()->put('url.intended', $request->fullUrl());
        }
    }
}
