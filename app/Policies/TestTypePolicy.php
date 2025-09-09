<?php

namespace App\Policies;

class TestTypePolicy extends SnipePermissionsPolicy
{
    protected function columnName()
    {
        return 'test_types';
    }
}
