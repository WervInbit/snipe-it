<?php

namespace Tests\Feature\CheckoutAcceptances\Ui;

use App\Events\CheckoutAccepted;
use App\Events\CheckoutDeclined;
use App\Models\Accessory;
use App\Models\CheckoutAcceptance;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

class AcceptanceSignatureSecurityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(config('filesystems.default'));
        Event::fake([CheckoutAccepted::class, CheckoutDeclined::class]);
        Notification::fake();
        $this->settings->set(['require_accept_signature' => 1]);
    }

    public function testInvalidBase64SignatureIsRejectedWithoutCompletingAcceptance(): void
    {
        $acceptance = CheckoutAcceptance::factory()
            ->pending()
            ->for(Accessory::factory()->appleMouse(), 'checkoutable')
            ->create();

        $this->actingAs($acceptance->assignedTo)
            ->post(route('account.store-acceptance', $acceptance), [
                'asset_acceptance' => 'accepted',
                'signature_output' => 'data:image/png;base64,not-valid-***',
            ])
            ->assertSessionHasErrors('signature_output');

        $this->assertTrue($acceptance->fresh()->isPending());
        $this->assertSame([], Storage::allFiles('private_uploads/signatures'));
        Event::assertNotDispatched(CheckoutAccepted::class);
    }

    public function testNonPngImageIsRejectedEvenWithPngDataUriLabel(): void
    {
        $acceptance = CheckoutAcceptance::factory()
            ->pending()
            ->for(Accessory::factory()->appleMouse(), 'checkoutable')
            ->create();

        $this->actingAs($acceptance->assignedTo)
            ->post(route('account.store-acceptance', $acceptance), [
                'asset_acceptance' => 'declined',
                'signature_output' => 'data:image/png;base64,' . base64_encode($this->jpeg()),
            ])
            ->assertSessionHasErrors('signature_output');

        $this->assertTrue($acceptance->fresh()->isPending());
        $this->assertSame([], Storage::allFiles('private_uploads/signatures'));
        Event::assertNotDispatched(CheckoutDeclined::class);
    }

    public function testStoredSignatureAndPdfAreRemovedWhenAcceptancePersistenceFails(): void
    {
        $acceptance = CheckoutAcceptance::factory()
            ->pending()
            ->for(Accessory::factory()->appleMouse(), 'checkoutable')
            ->create();
        $eventName = 'eloquent.saving: ' . CheckoutAcceptance::class;

        Event::listen($eventName, function (CheckoutAcceptance $saving) use ($acceptance): void {
            if ($saving->is($acceptance) && $saving->accepted_at !== null) {
                throw new RuntimeException('Simulated acceptance persistence failure.');
            }
        });

        try {
            $this->actingAs($acceptance->assignedTo)
                ->post(route('account.store-acceptance', $acceptance), [
                    'asset_acceptance' => 'accepted',
                    'signature_output' => 'data:image/png;base64,' . base64_encode($this->png()),
                ])
                ->assertStatus(500);
        } finally {
            Event::forget($eventName);
        }

        $this->assertTrue($acceptance->fresh()->isPending());
        $this->assertSame([], Storage::allFiles('private_uploads/signatures'));
        $this->assertSame([], Storage::allFiles('private_uploads/eula-pdfs'));
        Event::assertNotDispatched(CheckoutAccepted::class);
    }

    private function png(): string
    {
        $image = imagecreatetruecolor(2, 2);
        imagefill($image, 0, 0, imagecolorallocate($image, 30, 80, 140));

        ob_start();
        imagepng($image);
        $contents = ob_get_clean();
        imagedestroy($image);

        return $contents;
    }

    private function jpeg(): string
    {
        $image = imagecreatetruecolor(2, 2);
        imagefill($image, 0, 0, imagecolorallocate($image, 30, 80, 140));

        ob_start();
        imagejpeg($image);
        $contents = ob_get_clean();
        imagedestroy($image);

        return $contents;
    }
}
