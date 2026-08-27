<?php

namespace Tests\Feature\Notifications\Email;

use App\Mail\CheckinLicenseMail;
use App\Models\LicenseSeat;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\Group;
use App\Events\CheckoutableCheckedIn;
use App\Models\User;
use Tests\TestCase;

#[Group('notifications')]
class EmailNotificationsToUserUponCheckinTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
    }

    public function test_check_in_email_sent_to_user_if_setting_enabled()
    {
        $user = User::factory()->create();
        $licenseSeat = LicenseSeat::factory()->assignedToUser($user)->create();

        $licenseSeat->license->category->update(['checkin_email' => true]);

        $this->fireCheckInEvent($licenseSeat, $user);

        Mail::assertSent(CheckinLicenseMail::class, function ($mail) use ($user) {
            return $mail->hasTo($user->email);
        });
    }

    public function test_check_in_email_not_sent_to_user_if_setting_disabled()
    {
        $this->settings->disableAdminCC();

        $user = User::factory()->create();
        $licenseSeat = LicenseSeat::factory()->assignedToUser($user)->create();
        $licenseSeat->license->category->update([
            'checkin_email' => false,
            'eula_text' => null,
            'require_acceptance' => false,
        ]);

        $this->fireCheckInEvent($licenseSeat->fresh(['license.category']), $user);

        Mail::assertNotSent(CheckinLicenseMail::class, function ($mail) use ($user) {
            return $mail->hasTo($user->email);
        });
    }

    public function test_handles_user_not_having_email_address_set()
    {
        $user = User::factory()->create(['email' => null]);
        $licenseSeat = LicenseSeat::factory()->assignedToUser($user)->create();

        $licenseSeat->license->category->update(['checkin_email' => true]);

        $this->fireCheckInEvent($licenseSeat, $user);

        Mail::assertNothingSent();
    }

    private function fireCheckInEvent(LicenseSeat $licenseSeat, User $user): void
    {
        event(new CheckoutableCheckedIn(
            $licenseSeat,
            $user,
            User::factory()->checkoutLicenses()->create(),
            ''
        ));
    }
}
