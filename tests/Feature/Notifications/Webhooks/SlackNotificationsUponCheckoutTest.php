<?php

namespace Tests\Feature\Notifications\Webhooks;

use App\Models\Category;
use App\Notifications\CheckoutComponentNotification;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use App\Events\CheckoutableCheckedOut;
use App\Models\Accessory;
use App\Models\Asset;
use App\Models\Component;
use App\Models\Consumable;
use App\Models\LicenseSeat;
use App\Models\Location;
use App\Models\User;
use App\Notifications\CheckoutAccessoryNotification;
use App\Notifications\CheckoutConsumableNotification;
use App\Notifications\CheckoutLicenseSeatNotification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

#[Group('notifications')]
class SlackNotificationsUponCheckoutTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
        Mail::fake();
    }

    public static function componentCheckoutTargets(): array
    {
        return [
            'Component checked out to user' => [fn() => User::factory()->create(['email' => null])],
            'Component checked out to asset' => [fn() => Asset::factory()->laptopMbp()->create()],
            'Component checked out to location' => [fn() => Location::factory()->create()],
        ];
    }

    public static function licenseCheckoutTargets(): array
    {
        return [
            'License checked out to user' => [fn() => User::factory()->create(['email' => null])],
            'License checked out to asset' => [fn() => Asset::factory()->laptopMbp()->create()],
        ];
    }

    public function testAccessoryCheckoutSendsSlackNotificationWhenSettingEnabled()
    {
        $this->settings->enableSlackWebhook();

        $this->fireCheckOutEvent(
            Accessory::factory()->create(),
            User::factory()->create(),
        );

        $this->assertSlackNotificationSent(CheckoutAccessoryNotification::class);
    }

    public function testAccessoryCheckoutDoesNotSendSlackNotificationWhenSettingDisabled()
    {
        $this->settings->disableSlackWebhook();

        $this->fireCheckOutEvent(
            Accessory::factory()->create(),
            User::factory()->create(),
        );

        $this->assertNoSlackNotificationSent(CheckoutAccessoryNotification::class);
    }

    #[DataProvider('componentCheckoutTargets')]
    public function testComponentCheckoutSendsSlackNotificationWhenSettingEnabled($checkoutTarget)
    {
        $this->settings->enableSlackWebhook();
        $component = Component::factory()->create([
            'category_id' => Category::factory()->create([
                'require_acceptance' => false,
                'eula_text' => null,
            ]),
        ]);
        $this->fireCheckOutEvent(
            $component,
            $checkoutTarget(),
        );

        $this->assertSlackNotificationSent(CheckoutComponentNotification::class);
    }

    #[DataProvider('componentCheckoutTargets')]
    public function testComponentCheckoutDoesNotSendSlackNotificationWhenSettingDisabled($checkoutTarget)
    {
        $this->settings->disableSlackWebhook();
        $component = Component::factory()->create([
            'category_id' => Category::factory()->create([
                'require_acceptance' => false,
                'eula_text' => null,
            ]),
        ]);
        $this->fireCheckOutEvent(
            $component,
            $checkoutTarget(),
        );

        $this->assertNoSlackNotificationSent(CheckoutComponentNotification::class);
    }
    public function testConsumableCheckoutSendsSlackNotificationWhenSettingEnabled()
    {
        $this->settings->enableSlackWebhook();

        $this->fireCheckOutEvent(
            Consumable::factory()->create(),
            User::factory()->create(),
        );

        $this->assertSlackNotificationSent(CheckoutConsumableNotification::class);
    }

    public function testConsumableCheckoutDoesNotSendSlackNotificationWhenSettingDisabled()
    {
        $this->settings->disableSlackWebhook();

        $this->fireCheckOutEvent(
            Consumable::factory()->create(),
            User::factory()->create(),
        );

        $this->assertNoSlackNotificationSent(CheckoutConsumableNotification::class);
    }

    #[DataProvider('licenseCheckoutTargets')]
    public function testLicenseCheckoutSendsSlackNotificationWhenSettingEnabled($checkoutTarget)
    {
        $this->settings->enableSlackWebhook();

        $this->fireCheckOutEvent(
            LicenseSeat::factory()->create(),
            $checkoutTarget(),
        );

        $this->assertSlackNotificationSent(CheckoutLicenseSeatNotification::class);
    }

    #[DataProvider('licenseCheckoutTargets')]
    public function testLicenseCheckoutDoesNotSendSlackNotificationWhenSettingDisabled($checkoutTarget)
    {
        $this->settings->disableSlackWebhook();

        $this->fireCheckOutEvent(
            LicenseSeat::factory()->create(),
            $checkoutTarget(),
        );

        $this->assertNoSlackNotificationSent(CheckoutLicenseSeatNotification::class);
    }

    private function fireCheckOutEvent(Model $checkoutable, Model $target)
    {
        event(new CheckoutableCheckedOut(
            $checkoutable,
            $target,
            User::factory()->superuser()->create(),
            '',
        ));
    }
}
