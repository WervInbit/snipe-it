<?php

namespace Database\Seeders;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Schema;

class SettingsSeeder extends Seeder
{
    public function run()
    {
        Schema::disableForeignKeyConstraints();
        Setting::truncate();
        Schema::enableForeignKeyConstraints();
        $settings = new Setting;
        $settings->per_page = 20;
        $settings->site_name = 'Snipe-IT Demo';
        $settings->auto_increment_assets = 1;
        $settings->logo = 'snipe-logo.png';
        $settings->alert_email = 'service@snipe-it.io';
        $settings->header_color = null;
        $settings->label2_2d_type = 'QRCODE';
        $settings->default_currency = 'USD';
        $settings->brand = 3;
        $settings->ldap_enabled = 0;
        $settings->full_multiple_companies_support = 0;
        // Disable location scoping by company for testing/demo
        $settings->scope_locations_fmcs = 0;
        $settings->label2_1d_type = 'C128';
        // Enable QR code generation and display in UI
        $settings->qr_code = 1;
        $settings->qr_formats = 'png,pdf';
        $settings->qr_text_redundancy = 1;
        $settings->skin = '';
        $settings->email_domain = 'example.org';
        $settings->email_format = 'filastname';
        $settings->username_format = 'filastname';
        $settings->date_display_format = 'D M d, Y';
        $settings->time_display_format = 'g:iA';
        $settings->thumbnail_max_h = '30';
        $settings->locale = 'nl-NL';
        $settings->version_footer = 'on';
        $settings->support_footer = 'on';
        $settings->pwd_secure_min = '8';
        $settings->qr_code = 1;
        $settings->qr_text_redundancy = 0;
        $settings->qr_formats = 'png,pdf';
        $settings->default_avatar = 'default.png';
        $settings->save();

        if ($user = User::where('username', '=', 'admin')->first()) {
            $user->locale = 'nl-NL';
            $user->save();
        }

        // Copy the logos from the img/demo directory
        Storage::disk('local_public')->put('snipe-logo.png', file_get_contents(public_path('img/demo/snipe-logo.png')));
        Storage::disk('local_public')->put('snipe-logo-lg.png', file_get_contents(public_path('img/demo/snipe-logo-lg.png')));
    }
}
