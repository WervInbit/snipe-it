<?php

namespace Tests\Unit\Support;

use App\Models\Setting;
use Tests\Support\Settings;
use Tests\TestCase;

class SettingsIsolationTest extends TestCase
{
    public function test_initialization_replaces_stale_rows_and_synchronizes_the_settings_cache(): void
    {
        Setting::factory()->create([
            'site_name' => 'Stale settings row',
            'full_multiple_companies_support' => 0,
        ]);
        Setting::$_cache = Setting::query()->where('site_name', 'Stale settings row')->firstOrFail();

        $settings = Settings::initialize();

        $this->assertSame('Y-m-d', Setting::getSettings()->date_display_format);
        $this->assertSame('h:i A', Setting::getSettings()->time_display_format);

        $settings->set([
            'site_name' => 'Current test settings',
            'full_multiple_companies_support' => 1,
        ]);

        $this->assertSame(1, Setting::query()->count());
        $this->assertSame('Current test settings', Setting::getSettings()->site_name);
        $this->assertTrue((bool) Setting::getSettings()->full_multiple_companies_support);
    }
}
