<?php

namespace Tests\Feature\CheckoutAcceptances;

use App\Models\Accessory;
use App\Models\Actionlog;
use App\Models\CheckoutAcceptance;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AcceptanceResponseAtomicityTest extends TestCase
{
    public function testAcceptanceAndStoredFilesRollBackWhenItsActionLogCannotBeSaved(): void
    {
        Storage::fake(config('filesystems.default'));
        $this->settings->set(['require_accept_signature' => 1]);

        $acceptance = CheckoutAcceptance::factory()
            ->pending()
            ->for(Accessory::factory()->appleMouse(), 'checkoutable')
            ->create();
        $eventName = 'eloquent.creating: ' . Actionlog::class;

        Event::listen($eventName, fn () => false);

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
        $this->assertDatabaseMissing('action_logs', [
            'action_type' => 'accepted',
            'item_type' => Accessory::class,
            'item_id' => $acceptance->checkoutable_id,
        ]);
        $this->assertSame([], Storage::allFiles('private_uploads/signatures'));
        $this->assertSame([], Storage::allFiles('private_uploads/eula-pdfs'));
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
}
