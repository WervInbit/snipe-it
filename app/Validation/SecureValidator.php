<?php

namespace App\Validation;

use Illuminate\Validation\Validator;

class SecureValidator extends Validator
{
    /**
     * Backport Laravel's CR/LF rejection from the patched framework line.
     */
    public function validateEmail($attribute, $value, $parameters)
    {
        if (
            (is_string($value) || (is_object($value) && method_exists($value, '__toString')))
            && preg_match('/[\r\n]/', (string) $value) > 0
        ) {
            return false;
        }

        return parent::validateEmail($attribute, $value, $parameters);
    }
}
