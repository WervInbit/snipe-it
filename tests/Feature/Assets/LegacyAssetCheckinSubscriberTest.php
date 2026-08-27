<?php

namespace Tests\Feature\Assets;

use App\Events\CheckoutableCheckedIn;
use App\Models\Asset;
use App\Models\AssetModel;
use App\Models\Category;
use App\Models\CheckoutAcceptance;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class LegacyAssetCheckinSubscriberTest extends TestCase
{
    public function testSyntheticAssetCheckinEventIsInertAndAssetCheckinNotificationSourcesAreAbsent(): void
    {
        Mail::fake();
        Notification::fake();

        $this->settings
            ->enableAdminCC('cc@example.com')
            ->enableAdminCCAlways()
            ->enableSlackWebhook();

        $category = Category::factory()->create([
            'checkin_email' => true,
            'require_acceptance' => true,
            'use_default_eula' => false,
        ]);
        $target = User::factory()->create();
        $asset = Asset::factory()
            ->for(AssetModel::factory()->for($category), 'model')
            ->assignedToUser($target)
            ->create();
        $actor = User::factory()->superuser()->create();
        $acceptance = CheckoutAcceptance::factory()->pending()->create([
            'checkoutable_type' => Asset::class,
            'checkoutable_id' => $asset->id,
            'assigned_to_id' => $target->id,
        ]);
        $asset->refresh();

        $actionLogCount = $asset->assetlog()->count();
        $checkinCounter = $asset->getRawOriginal('checkin_counter');

        event(new CheckoutableCheckedIn($asset, $target, $actor, 'must remain inert'));

        $this->assertSame($actionLogCount, $asset->assetlog()->count());
        $this->assertSame($checkinCounter, $asset->fresh()->getRawOriginal('checkin_counter'));
        $this->assertTrue($acceptance->fresh()->isPending());
        Mail::assertNothingSent();
        Notification::assertNothingSent();

        $this->assertFileDoesNotExist(app_path('Mail/CheckinAssetMail.php'));
        $this->assertFileDoesNotExist(app_path('Notifications/CheckinAssetNotification.php'));
        $this->assertFileDoesNotExist(resource_path('views/mail/markdown/checkin-asset.blade.php'));
        $this->assertFalse(view()->exists('mail.markdown.checkin-asset'));
    }
}
