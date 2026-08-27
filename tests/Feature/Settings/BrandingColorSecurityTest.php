<?php

namespace Tests\Feature\Settings;

use App\Models\Setting;
use App\Models\User;
use Tests\TestCase;

class BrandingColorSecurityTest extends TestCase
{
    public function testBrandingRejectsAHeaderColorDeclarationInjection(): void
    {
        $original = Setting::getSettings()->getRawOriginal('header_color');

        $this->actingAs(User::factory()->superuser()->create())
            ->from(route('settings.branding.index'))
            ->post(route('settings.branding.save'), [
                'header_color' => '#fff; background: url(https://example.invalid/steal)',
            ])
            ->assertInvalid('header_color')
            ->assertRedirect(route('settings.branding.index'));

        $this->assertSame(
            $original,
            Setting::getSettings()->refresh()->getRawOriginal('header_color')
        );
    }

    public function testBrandingAcceptsAValidHeaderColor(): void
    {
        $this->actingAs(User::factory()->superuser()->create())
            ->post(route('settings.branding.save'), [
                'header_color' => '#1a2b3c',
            ])
            ->assertValid('header_color');

        $this->assertDatabaseHas('settings', [
            'header_color' => '#1a2b3c',
        ]);
    }

    public function testSettingModelFailsClosedForAStoredUnsafeHeaderColor(): void
    {
        $setting = Setting::getSettings();
        $setting->setRawAttributes([
            ...$setting->getAttributes(),
            'header_color' => '#fff; background: url(https://example.invalid/steal)',
        ]);

        $this->assertSame('#3c8dbc', $setting->header_color);
    }
}
