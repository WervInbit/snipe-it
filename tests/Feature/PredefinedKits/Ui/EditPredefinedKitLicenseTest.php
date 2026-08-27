<?php

namespace Tests\Feature\PredefinedKits\Ui;

use App\Models\License;
use App\Models\PredefinedKit;
use App\Models\User;
use Tests\TestCase;

class EditPredefinedKitLicenseTest extends TestCase
{
    public function testAttachedLicenseEditPageRendersWithAWorkingUpdateForm(): void
    {
        $kit = PredefinedKit::factory()->create();
        $license = License::factory()->create();
        $kit->licenses()->attach($license->id, ['quantity' => 2]);

        $this->actingAs(User::factory()->superuser()->create())
            ->get(route('kits.licenses.edit', [
                'kit' => $kit,
                'license_id' => $license->id,
            ]))
            ->assertOk()
            ->assertSee(route('kits.licenses.update', [
                'kit' => $kit,
                'license_id' => $license->id,
            ]), false);
    }
}
