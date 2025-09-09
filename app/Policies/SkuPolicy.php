<?php

namespace App\Policies;

class SkuPolicy extends SnipePermissionsPolicy
{
    protected function columnName()
    {
        return 'skus';
    }
}
