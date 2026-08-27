<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
            if ($settings?->getAttribute('scope_locations_fmcs') == 1) {
                static::addGlobalScope(new CompanyableScope);
            }
        }
    }

    /**
     * Determine whether this item may be checked out to a target under the
     * fork's current full-multiple-company rules.
     */
    public function canCheckoutTo(Model $target): bool
    {
        $settings = Setting::getSettings();

        if (! $settings || ! $settings->getAttribute('full_multiple_companies_support')) {
            return true;
        }

        // Unassigned inventory remains available across companies because
        // this fork does not expose the newer upstream floater setting.
        $companyId = $this->getAttribute('company_id');
        if (! $companyId) {
            return true;
        }

        // Locations are intentionally unscoped unless the corresponding
        // FMCS setting is enabled.
        if ($target instanceof Location && ! $settings->getAttribute('scope_locations_fmcs')) {
            return true;
        }

        $targetCompanyId = $target->getAttribute('company_id');

        return $targetCompanyId !== null
            && (int) $targetCompanyId === (int) $companyId;
    }
}

