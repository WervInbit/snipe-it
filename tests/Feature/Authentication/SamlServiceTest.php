<?php

namespace Tests\Feature\Authentication;

use App\Models\Setting;
use App\Services\Saml;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class SamlServiceTest extends TestCase
{
    public function test_service_is_disabled_when_application_settings_do_not_exist(): void
    {
        Setting::query()->delete();
        Setting::$_cache = null;

        $this->assertFalse((new Saml())->isEnabled());
    }

    public function test_route_inventory_can_be_built_before_application_settings_exist(): void
    {
        Setting::query()->delete();
        Setting::$_cache = null;

        $this->assertSame(0, Artisan::call('route:list', ['--json' => true]));
    }
}
