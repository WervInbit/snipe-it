<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CheckForSetup
{
    private const REQUIRED_LIFECYCLE_MIGRATION = '2026_07_23_130000_add_lifecycle_stage_to_status_labels';
    private const UPGRADE_REQUIRED_MESSAGE =
        'Application upgrade required: database migrations are incomplete. '
        . 'Run "php artisan migrate --force" before serving this release.';

    protected $except = [
        '_debugbar*',
        'health'
    ];

    public function handle($request, Closure $next, $guard = null)
    {

        /**
         * Skip this middleware for the debugbar and health check
         */
        if ($request->is($this->except)) {
            return $next($request);
        }

        if (Setting::setupCompleted()) {
            if (! $this->requiredSchemaIsPresent()) {
                return response(self::UPGRADE_REQUIRED_MESSAGE, 503)
                    ->header('Retry-After', '60');
            }

            if ($request->is('setup*')) {
                return redirect(config('app.url'));
            } else {
                return $next($request);
            }
        } else {
            if (! config('app.allow_web_setup')) {
                return response(
                    'Web setup is disabled. Complete migrations and create the first administrator from the CLI.',
                    503
                )->header('Retry-After', '60');
            }

            if (! ($request->is('setup*')) && ! ($request->is('.env'))) {
                return redirect(config('app.url') . '/setup');
            }

            return $next($request);
        }
    }

    private function requiredSchemaIsPresent(): bool
    {
        return Schema::hasTable('migrations')
            && DB::table('migrations')
                ->where('migration', self::REQUIRED_LIFECYCLE_MIGRATION)
                ->exists()
            && Schema::hasColumn('status_labels', 'lifecycle_stage');
    }
}
