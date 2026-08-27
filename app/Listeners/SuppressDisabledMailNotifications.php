<?php

namespace App\Listeners;

use Illuminate\Notifications\Events\NotificationSending;

class SuppressDisabledMailNotifications
{
    public function handle(NotificationSending $event): bool
    {
        if ($event->channel === 'mail' && ! config('mail.enabled', true)) {
            return false;
        }

        return true;
    }
}
