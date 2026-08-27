<?php

namespace Tests\Feature\Settings;

use Tests\TestCase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use App\Models\User;
use App\Models\Setting;


class BrandingSettingsTest extends TestCase
{
    public function testSiteNameIsRequired()
    {
        $response = $this->actingAs(User::factory()->superuser()->create())
            ->from(route('settings.branding.index'))
            ->post(route('settings.branding.save', ['site_name' => '']))
            ->assertSessionHasErrors(['site_name'])
            ->assertInvalid(['site_name'])
            ->assertStatus(302)
            ->assertRedirect(route('settings.branding.index'));

        $this->followRedirects($response)->assertSee('alert-danger');
    }

    public function testSiteNameCanBeSaved()
    {
        $response = $this->actingAs(User::factory()->superuser()->create())
            ->post(route('settings.branding.save', ['site_name' => 'My Awesome Site']))
            ->assertStatus(302)
            ->assertValid('site_name')
            ->assertRedirect(route('settings.index'))
            ->assertSessionHasNoErrors();

        $this->followRedirects($response)->assertSee('alert-success');
    }


    public function testLogoCanBeUploaded()
    {
        Storage::fake('public');
        $setting = Setting::getSettings();
        $setting->forceFill(['logo' => null])->save();

        $response = $this->actingAs(User::factory()->superuser()->create())
            ->post(route('settings.branding.save'), [
                'logo' => UploadedFile::fake()->image('test_logo.png'),
            ])
            ->assertValid('logo')
            ->assertStatus(302)
            ->assertRedirect(route('settings.index'))
            ->assertSessionHasNoErrors();

        $setting->refresh();

        $this->assertNotNull($setting->logo);
        Storage::disk('public')->assertExists($setting->logo);
        $this->followRedirects($response)->assertSee('alert-success');
    }


    public function testLogoCanBeDeleted()
    {
        Storage::fake('public');

        $oldLogo = 'new_test_logo.png';
        Storage::disk('public')->put($oldLogo, 'logo contents');
        $setting = Setting::getSettings();
        $setting->forceFill(['logo' => $oldLogo])->save();
        Storage::disk('public')->assertExists($setting->logo);

        $this->assertNotNull($setting->logo);

        $response = $this->actingAs(User::factory()->superuser()->create())
            ->from(route('settings.branding.index'))
            ->post(route('settings.branding.save'), ['clear_logo' => '1'])
            ->assertValid('logo')
            ->assertStatus(302)
            ->assertRedirect(route('settings.index'));

        $this->followRedirects($response)->assertSee(trans('alert-success'));
        $this->assertDatabaseHas('settings', ['logo' => null]);
        Storage::disk('public')->assertMissing($oldLogo);
    }

    public function testEmailLogoCanBeUploaded()
    {
        Storage::fake('public');
        $setting = Setting::getSettings();
        $setting->forceFill(['email_logo' => null])->save();

        $response = $this->actingAs(User::factory()->superuser()->create())
            ->from(route('settings.branding.index'))
            ->post(route('settings.branding.save'), [
                'email_logo' => UploadedFile::fake()->image('new_test_email_logo.png'),
            ])
            ->assertValid('email_logo')
            ->assertStatus(302)
            ->assertRedirect(route('settings.index'));

        $this->followRedirects($response)->assertSee(trans('alert-success'));

        $setting->refresh();
        $this->assertNotNull($setting->email_logo);
        Storage::disk('public')->assertExists($setting->email_logo);
    }

    public function testEmailLogoCanBeDeleted()
    {
        Storage::fake('public');
        Storage::disk('public')->put('new_test_email_logo.png', 'email logo contents');
        $setting = Setting::getSettings();
        $setting->forceFill(['email_logo' => 'new_test_email_logo.png'])->save();
        Storage::disk('public')->assertExists($setting->email_logo);

        $this->assertNotNull($setting->email_logo);

        $response = $this->actingAs(User::factory()->superuser()->create())
            ->from(route('settings.branding.index'))
            ->post(route('settings.branding.save'), ['clear_email_logo' => '1'])
            ->assertValid('email_logo')
            ->assertStatus(302)
            ->assertRedirect(route('settings.index'));

        $setting->refresh();
        $this->followRedirects($response)->assertSee(trans('alert-success'));
        $this->assertDatabaseHas('settings', ['email_logo' => null]);

        Storage::disk('public')->assertMissing('new_test_email_logo.png');

    }


    public function testLabelLogoCanBeUploaded()
    {

        Storage::fake('public');

        $original_file = 'before_test_label_logo.png';
        Storage::disk('public')->put($original_file, 'old label logo contents');

        Storage::disk('public')->assertExists($original_file);
        $setting = Setting::getSettings();
        $setting->forceFill(['label_logo' => $original_file])->save();

        $response = $this->actingAs(User::factory()->superuser()->create())
            ->from(route('settings.branding.index'))
            ->post(route('settings.branding.save'), [
                'label_logo' => UploadedFile::fake()->image('new_test_label_logo.png'),
            ])
            ->assertValid('label_logo')
            ->assertStatus(302)
            ->assertRedirect(route('settings.index'));

        $this->followRedirects($response)->assertSee(trans('alert-success'));

        $setting->refresh();
        $this->assertNotNull($setting->label_logo);
        Storage::disk('public')->assertExists($setting->label_logo);
        Storage::disk('public')->assertMissing($original_file);


    }

    public function testLabelLogoCanBeDeleted()
    {

        Storage::fake('public');

        Storage::disk('public')->put('new_test_label_logo.png', 'label logo contents');
        $setting = Setting::getSettings();
        $setting->forceFill(['label_logo' => 'new_test_label_logo.png'])->save();
        Storage::disk('public')->assertExists($setting->label_logo);

        $this->assertNotNull($setting->label_logo);

        $response = $this->actingAs(User::factory()->superuser()->create())
            ->from(route('settings.branding.index'))
            ->post(route('settings.branding.save'), ['clear_label_logo' => '1'])
            ->assertValid('label_logo')
            ->assertStatus(302)
            ->assertRedirect(route('settings.index'));

        $setting->refresh();
        $this->followRedirects($response)->assertSee(trans('alert-success'));
        $this->assertNull($setting->label_logo);
        Storage::disk('public')->assertMissing('new_test_label_logo.png');

    }

    public function testDefaultAvatarCanBeUploaded()
    {
        Storage::fake('public');
        $setting = Setting::getSettings();
        $setting->forceFill(['default_avatar' => null])->save();

        $response = $this->actingAs(User::factory()->superuser()->create())
            ->from(route('settings.branding.index'))
            ->post(route('settings.branding.save'), [
                'default_avatar' => UploadedFile::fake()->image('default_avatar.png'),
            ])
            ->assertValid('default_avatar')
            ->assertStatus(302)
            ->assertRedirect(route('settings.index'))
            ->assertSessionHasNoErrors();

        $this->followRedirects($response)->assertSee(trans('alert-success'));

        $setting->refresh();
        $this->assertNotNull($setting->default_avatar);
        Storage::disk('public')->assertExists('avatars/'.$setting->default_avatar);
    }

    public function testDefaultAvatarCanBeDeleted()
    {
        Storage::fake('public');

        $setting = Setting::getSettings();
        $setting->forceFill(['default_avatar' => 'custom-avatar.png'])->save();
        $original_file = 'avatars/custom-avatar.png';
        Storage::disk('public')->put($original_file, 'avatar contents');
        Storage::disk('public')->assertExists($original_file);

        $this->assertNotNull($setting->default_avatar);

        $response = $this->actingAs(User::factory()->superuser()->create())
            ->from(route('settings.branding.index'))
            ->post(route('settings.branding.save'), ['clear_default_avatar' => '1'])
            ->assertValid('default_avatar')
            ->assertStatus(302)
            ->assertRedirect(route('settings.index'));

        $setting->refresh();
        $this->followRedirects($response)->assertSee(trans('alert-success'));
        $this->assertNull($setting->default_avatar);
        Storage::disk('public')->assertMissing($original_file);
    }

    public function testSnipeDefaultAvatarCanBeDeleted()
    {

        $setting = Setting::getSettings();
        Storage::fake('public');

        $setting->forceFill(['default_avatar' => 'default.png'])->save();
        Storage::disk('public')->put('avatars/default.png', 'bundled default avatar contents');
        Storage::disk('public')->assertExists('avatars/default.png');


        $this->actingAs(User::factory()->superuser()->create())
            ->post(route('settings.branding.save'), ['clear_default_avatar' => '1']);

        $this->assertNull($setting->refresh()->default_avatar);
        $this->assertDatabaseHas('settings', ['default_avatar' => null]);
        Storage::disk('public')->assertExists('avatars/default.png');

    }

    public function testFaviconCanBeUploaded()
    {
        Storage::fake('public');
        $setting = Setting::getSettings();
        $setting->forceFill(['favicon' => null])->save();

        $response = $this->actingAs(User::factory()->superuser()->create())
            ->from(route('settings.branding.index'))
            ->post(route('settings.branding.save'), [
                'favicon' => UploadedFile::fake()->image('favicon.png', 32, 32),
            ])
            ->assertValid('favicon')
            ->assertStatus(302)
            ->assertRedirect(route('settings.index'));

        $this->followRedirects($response)->assertSee(trans('alert-success'));

        $setting->refresh();

        $this->assertNotNull($setting->favicon);
        Storage::disk('public')->assertExists($setting->favicon);
    }

    public function testFaviconCanBeDeleted()
    {
        Storage::fake('public');

        $setting = Setting::getSettings();
        $setting->forceFill(['favicon' => 'favicon.png'])->save();
        Storage::disk('public')->put('favicon.png', 'favicon contents');
        Storage::disk('public')->assertExists('favicon.png');

        $this->assertNotNull($setting->favicon);

        $response = $this->actingAs(User::factory()->superuser()->create())
            ->from(route('settings.branding.index'))
            ->post(route('settings.branding.save'), ['clear_favicon' => '1'])
            ->assertValid('favicon')
            ->assertStatus(302)
            ->assertRedirect(route('settings.index'));

        $setting->refresh();
        $this->followRedirects($response)->assertSee(trans('alert-success'));

        $this->assertNull($setting->favicon);
        Storage::disk('public')->assertMissing('favicon.png');
    }



}
