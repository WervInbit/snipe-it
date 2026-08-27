<?php

namespace App\Console\Commands;


use App\Models\Setting;
use App\Models\User;
use Illuminate\Console\Command;

class ResetDemoSettings extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'snipeit:demo-settings';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reset the fork demo settings to safe defaults.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {

        $this->info('Resetting the demo settings.');
        $settings = Setting::first();
        $settings->per_page = 20;
        $settings->site_name = config('app.name') . ' Demo';
        $settings->auto_increment_assets = 1;
        $settings->logo = null;
        $settings->alert_email = 'demo@example.invalid';
        $settings->login_note = 'Use `admin` / `password` to login to the demo.';
        $settings->header_color = null;
        $settings->label2_2d_type = 'QRCODE';
        $settings->default_currency = 'USD';
        $settings->brand = 1;
        $settings->ldap_enabled = 0;
        $settings->full_multiple_companies_support = 0;
        $settings->label2_1d_type = 'C128';
        $settings->skin = '';
        $settings->email_domain = 'example.invalid';
        $settings->email_format = 'filastname';
        $settings->username_format = 'filastname';
        $settings->date_display_format = 'D M d, Y';
        $settings->time_display_format = 'g:iA';
        $settings->thumbnail_max_h = '30';
        $settings->locale = 'en-US';
        $settings->version_footer = 'on';
        $settings->support_footer = 'on';
        $settings->saml_enabled = '0';
        $settings->saml_sp_x509cert = null;
        $settings->saml_idp_metadata = null;
        $settings->saml_attr_mapping_username = null;
        $settings->saml_forcelogin = '0';
        $settings->saml_slo = 0;
        $settings->saml_custom_settings = null;
        $settings->default_avatar = 'default.png';


        $settings->save();

        if ($user = User::where('username', '=', 'admin')->first()) {
            $user->locale = 'en-US';
            $user->save();
        }

    }

}
