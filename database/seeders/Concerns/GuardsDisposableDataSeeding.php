<?php

namespace Database\Seeders\Concerns;

use LogicException;

trait GuardsDisposableDataSeeding
{
    protected function assertDisposableDataSeedingAllowed(): void
    {
        if (!app()->environment(['local', 'testing'])) {
            throw new LogicException(sprintf(
                '%s is restricted to local/testing environments.',
                static::class
            ));
        }

        if (config('demo.allow_disposable_data_seeding') !== true) {
            throw new LogicException(sprintf(
                '%s requires SNIPEIT_ALLOW_DISPOSABLE_DATA_SEEDING=true for this disposable environment.',
                static::class
            ));
        }
    }
}
