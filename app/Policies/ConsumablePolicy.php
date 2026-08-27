<?php

namespace App\Policies;

class ConsumablePolicy extends CheckoutablePermissionsPolicy
{
    protected function columnName()
    {
        return 'consumables';
    }

    protected function usesDedicatedFilePermission(): bool
    {
        return true;
    }
}
