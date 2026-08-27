<?php

namespace Tests\Feature\Settings;

use App\Models\Asset;
use App\Models\Setting;
use App\Models\User;
use Tests\TestCase;

class AlertsSettingTest extends TestCase
{
    public function testPermissionRequiredToViewAlertSettings()
    {
        $this->actingAs(User::factory()->create())
            ->get(route('settings.alerts.index'))
            ->assertForbidden();
    }

    public function testAdminCCEmailArrayCanBeSaved()
    {
        $response = $this->actingAs(User::factory()->superuser()->create())
            ->post(route('settings.alerts.save', [
                'alert_email' => 'me@example.com,you@example.com',
                'admin_cc_always' => '1',
            ]))
            ->assertStatus(302)
            ->assertValid('alert_email')
            ->assertRedirect(route('settings.index'))
            ->assertSessionHasNoErrors();
        $this->followRedirects($response)->assertSee('alert-success');
    }

    public function test_can_update_admin_cc_always_to_true()
    {
        $this->settings->disableAdminCCAlways();

        $this->actingAs(User::factory()->superuser()->create())
            ->post(route('settings.alerts.save', ['admin_cc_always' => '1']));

        $this->assertDatabaseHas('settings', ['admin_cc_always' => '1']);
    }

    public function test_can_update_admin_cc_always_to_false()
    {
        $this->settings->enableAdminCC()->enableAdminCCAlways();

        $this->actingAs(User::factory()->superuser()->create())
            ->post(route('settings.alerts.save', ['admin_cc_always' => '0']));

        $this->assertDatabaseHas('settings', ['admin_cc_always' => '0']);
    }

    public function test_audit_interval_setting_does_not_rewrite_historical_asset_metadata()
    {
        $asset = Asset::factory()->create([
            'next_audit_date' => '2026-08-15',
        ]);
        $settings = Setting::getSettings();
        $settings->audit_interval = 12;
        $settings->save();

        $this->actingAs(User::factory()->superuser()->create())
            ->post(route('settings.alerts.save', [
                'admin_cc_always' => '0',
                'audit_interval' => 6,
            ]))
            ->assertRedirect(route('settings.index'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('settings', ['audit_interval' => 6]);
        $this->assertDatabaseHas('assets', [
            'id' => $asset->id,
            'next_audit_date' => '2026-08-15',
        ]);
    }
}
