<?php

namespace Tests\Feature;

use App\Models\Accessory;
use App\Models\Asset;
use App\Models\ComponentInstance;
use App\Models\Consumable;
use App\Models\License;
use App\Models\Setting;
use App\Models\User;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    public function testUsersWithoutAdminAccessCanViewDashboard()
    {
        $this->actingAs(User::factory()->create())
            ->get(route('home'))
            ->assertOk()
            ->assertViewIs('dashboard');
    }

    public function testCountsAreLoadedCorrectlyForAdmins()
    {
        Asset::factory()->count(2)->create();
        Accessory::factory()->count(2)->create();
        License::factory()->count(2)->create();
        Consumable::factory()->count(2)->create();
        ComponentInstance::factory()->count(2)->create();

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('home'))
            ->assertViewIs('dashboard')
            ->assertViewHas('counts', function ($value) {
                $accessoryCount = Accessory::count();
                $assetCount = Asset::count();
                $componentCount = ComponentInstance::count();
                $consumableCount = Consumable::count();
                $licenseCount = License::assetcount();
                $userCount = User::count();

                $this->assertEquals($value['accessory'], $accessoryCount, 'Accessory count incorrect.');
                $this->assertEquals($value['asset'], $assetCount, 'Asset count incorrect.');
                $this->assertEquals($value['license'], $licenseCount, 'License count incorrect.');
                $this->assertEquals($value['consumable'], $consumableCount, 'Consumable count incorrect.');
                $this->assertEquals($value['component'], $componentCount, 'Component count incorrect.');
                $this->assertEquals($value['user'], $userCount, 'User count incorrect.');
                $this->assertEquals(
                    $value['grand_total'],
                    $accessoryCount + $assetCount + $consumableCount + $licenseCount + $componentCount,
                    'Grand total count incorrect.'
                );

                return true;
            });
    }

    public function testDashboardShowsScanCardWhenUserHasScanningPermission()
    {
        $user = User::factory()->create([
            'permissions' => json_encode([
                'admin' => '1',
                'assets.view' => '1',
                'scanning' => '1',
            ]),
        ]);

        $this->actingAs($user)
            ->get(route('home'))
            ->assertOk()
            ->assertSee('data-testid="dashboard-scan-card"', false)
            ->assertSee(route('scan'));
    }

    public function testDashboardHidesScanCardWhenUserLacksScanningPermission()
    {
        $user = User::factory()->create([
            'permissions' => json_encode([
                'admin' => '1',
                'assets.view' => '1',
            ]),
        ]);

        $this->actingAs($user)
            ->get(route('home'))
            ->assertOk()
            ->assertDontSee('data-testid="dashboard-scan-card"', false);
    }

    public function testCombinedBrandMarkupCanHideOnlyTheNameAtTheNarrowDesktopBreakpoint(): void
    {
        Setting::getSettings()->forceFill([
            'brand' => 3,
            'logo' => 'combined-brand.png',
            'site_name' => 'Snipe-IT',
        ])->save();

        $this->actingAs(User::factory()->admin()->viewAssets()->create())
            ->get(route('home'))
            ->assertOk()
            ->assertSee('class="navbar-brand-img"', false)
            ->assertSee('<span class="navbar-brand-name">Snipe-IT</span>', false)
            ->assertSee('id="tagSearch"', false)
            ->assertSee('id="topSearchButton"', false);

        $responsiveStyles = file_get_contents(resource_path('assets/less/overrides.less'));

        $this->assertIsString($responsiveStyles);
        $this->assertStringContainsString(
            '@media (min-width: 768px) and (max-width: 899px)',
            $responsiveStyles
        );
        $this->assertMatchesRegularExpression(
            '/@media \(min-width: 768px\) and \(max-width: 899px\).*?\.navbar-brand-name\s*\{\s*display:\s*none;.*?#tagSearch,\s*#topSearchButton\s*\{.*?height:\s*34px;/s',
            $responsiveStyles
        );
    }
}
