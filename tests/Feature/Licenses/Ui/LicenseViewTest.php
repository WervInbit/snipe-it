<?php

namespace Tests\Feature\Licenses\Ui;

use App\Models\License;
use App\Models\Depreciation;
use App\Models\User;
use Tests\TestCase;

class LicenseViewTest extends TestCase
{
    public function testPermissionRequiredToViewLicense()
    {
        $license = License::factory()->create();
        $this->actingAs(User::factory()->create())
            ->get(route('licenses.show', $license))
            ->assertForbidden();
    }

    public function testPageRenders()
    {
        $this->actingAs(User::factory()->superuser()->create())
            ->get(route('licenses.show', License::factory()->create()->id))
            ->assertOk();
    }

    public function testProductKeyVisibilityUsesTheDedicatedKeyPermission(): void
    {
        $license = License::factory()->create(['serial' => 'SENSITIVE-LICENSE-KEY']);

        $this->actingAs(User::factory()->viewLicenses()->create())
            ->get(route('licenses.show', $license))
            ->assertOk()
            ->assertDontSee('SENSITIVE-LICENSE-KEY');

        $this->actingAs(User::factory()->viewLicenses()->viewKeysLicenses()->create())
            ->get(route('licenses.show', $license))
            ->assertOk()
            ->assertSee('SENSITIVE-LICENSE-KEY');
    }

    public function testLicenseUploadControlsUseTheDedicatedFilePermission(): void
    {
        $license = License::factory()->create();
        $ordinaryEditor = User::factory()->create([
            'permissions' => json_encode([
                'licenses.view' => '1',
                'licenses.edit' => '1',
            ]),
        ]);
        $fileManager = User::factory()->create([
            'permissions' => json_encode([
                'licenses.view' => '1',
                'licenses.files' => '1',
            ]),
        ]);

        $this->actingAs($ordinaryEditor)
            ->get(route('licenses.show', $license))
            ->assertOk()
            ->assertDontSee('data-target="#uploadFileModal"', false)
            ->assertDontSee('id="files"', false);

        $this->actingAs($fileManager)
            ->get(route('licenses.show', $license))
            ->assertOk()
            ->assertSee('data-target="#uploadFileModal"', false)
            ->assertSee('id="files"', false);
    }
    
    public function testLicenseWithPurchaseDateDepreciatesCorrectly()
    {
        $depreciation = Depreciation::factory()->create(['months' => 12]);
        $license = License::factory()->create(['depreciation_id' => $depreciation->id, 'purchase_date' => '2020-01-01']);
        $this->actingAs(User::factory()->superuser()->create())
            ->get(route('licenses.show', $license))
            ->assertOk()
            ->assertSee([
                '2021-01-01'
            ], false);
    }
}
