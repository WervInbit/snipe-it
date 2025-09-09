<?php

namespace App\Models;

use App\Models\CompanyableScope;

trait CompanyableTrait
{
    /**
     * This trait is used to scope models to the current company. To use this scope on companyable models,
     * we use the "use Companyable;" statement at the top of the mode.
     *
     * @see    \App\Models\Company\Company::scopeCompanyables()
     * @return void
     */
    public static function bootCompanyableTrait()
    {
        // In Version 7.0 and before locations weren't scoped by companies, so add a check for the backward compatibility setting
        if (__CLASS__ != 'App\\Models\\Location') {
            static::addGlobalScope(new CompanyableScope);
        } else {
            $settings = Setting::getSettings();
            if ($settings && $settings->scope_locations_fmcs == 1) {
                static::addGlobalScope(new CompanyableScope);
            }
        }
    }
}

