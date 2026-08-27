<?php

namespace Tests\Feature\CheckoutAcceptances;

use App\Notifications\AcceptanceAssetAcceptedToUserNotification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AcceptanceNotificationStorageTest extends TestCase
{
    public function testAcceptedEulaAttachmentIsReadFromTheConfiguredPrivateDisk(): void
    {
        config(['filesystems.default' => 's3_private']);
        Storage::fake('s3_private');

        $filename = 'accepted-eula.pdf';
        $contents = '%PDF-1.4 stored on the configured disk';
        Storage::disk('s3_private')->put(
            'private_uploads/eula-pdfs/' . $filename,
            $contents
        );

        $mail = (new AcceptanceAssetAcceptedToUserNotification([
            'item_tag' => 'ACC-1',
            'item_model' => 'Mouse',
            'item_serial' => 'SERIAL',
            'item_status' => null,
            'accepted_date' => now()->toDateString(),
            'assigned_to' => 'Example User',
            'note' => null,
            'company_name' => 'Example',
            'file' => $filename,
        ]))->toMail();

        $this->assertCount(1, $mail->rawAttachments);
        $this->assertSame($contents, $mail->rawAttachments[0]['data']);
        $this->assertSame($filename, $mail->rawAttachments[0]['name']);
        $this->assertSame('application/pdf', $mail->rawAttachments[0]['options']['mime']);
    }
}
