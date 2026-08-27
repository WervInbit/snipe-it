<?php

use App\Rules\ExternalUrl;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

return new class extends Migration
{
    public function up(): void
    {
        $settings = DB::table('settings')
            ->whereNotNull('webhook_endpoint')
            ->where('webhook_endpoint', '!=', '')
            ->get(['id', 'webhook_endpoint']);

        foreach ($settings as $setting) {
            $isUnsafe = Validator::make(
                ['webhook_endpoint' => $setting->webhook_endpoint],
                ['webhook_endpoint' => ['required', 'url', new ExternalUrl]],
            )->fails();

            if (! $isUnsafe) {
                continue;
            }

            DB::table('settings')
                ->where('id', $setting->id)
                ->update(['webhook_endpoint' => null]);

            // Webhook paths and query strings frequently contain credentials.
            Log::warning('Cleared an unsafe webhook endpoint during migration', [
                'settings_id' => $setting->id,
            ]);
        }
    }

    public function down(): void
    {
        // Cleared secrets cannot be restored safely.
    }
};
