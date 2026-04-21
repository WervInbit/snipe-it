<?php

namespace App\Console\Commands;

use App\Models\ComponentInstance;
use App\Services\ComponentLifecycleService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class AgeTrayComponents extends Command
{
    protected $signature = 'components:age-tray';

    protected $description = 'Escalate stale tray components into needs-verification status.';

    public function handle(ComponentLifecycleService $lifecycle): int
    {
        $now = Carbon::now();
        $reminderThreshold = (int) config('components.tray.reminder_hours', 2);
        $verificationThreshold = (int) config('components.tray.needs_verification_hours', 24);

        $staleForReminder = ComponentInstance::query()
            ->inTray()
            ->whereNotNull('transfer_started_at')
            ->where('transfer_started_at', '<=', $now->copy()->subHours($reminderThreshold))
            ->count();

        $toEscalate = ComponentInstance::query()
            ->inTray()
            ->whereNotNull('transfer_started_at')
            ->where('transfer_started_at', '<=', $now->copy()->subHours($verificationThreshold))
            ->get();

        foreach ($toEscalate as $instance) {
            $lifecycle->flagNeedsVerification($instance, [
                'performed_by' => null,
                'needs_verification_at' => $now,
                'note' => 'Tray aging threshold exceeded.',
                'payload_json' => [
                    'aged_from_transfer' => true,
                    'transfer_started_at' => optional($instance->transfer_started_at)?->toISOString(),
                ],
            ]);
        }

        if ($staleForReminder > 0) {
            Log::warning('Tray components exceeded reminder threshold.', [
                'count' => $staleForReminder,
                'reminder_hours' => $reminderThreshold,
            ]);
        }

        $this->info(sprintf(
            'Tray aging scan complete. reminder_candidates=%d escalated=%d',
            $staleForReminder,
            $toEscalate->count()
        ));

        return self::SUCCESS;
    }
}
