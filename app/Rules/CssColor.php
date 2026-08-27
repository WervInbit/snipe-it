<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class CssColor implements ValidationRule
{
    public static function sanitize(?string $value, string $default): string
    {
        if ($value !== null && preg_match(self::pattern(), trim($value)) === 1) {
            return trim($value);
        }

        return $default;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || preg_match(self::pattern(), trim($value)) !== 1) {
            $fail(trans('validation.valid_css_color'));
        }
    }

    private static function pattern(): string
    {
        $number = '\s*[\d.]+\s*';
        $percentage = '\s*[\d.]+%\s*';
        $alpha = '(?:,\s*[\d.]+\s*)?';
        $hex = '#[0-9a-fA-F]{3,8}';
        $rgb = "rgba?\({$number},{$number},{$number}{$alpha}\)";
        $hsl = "hsla?\({$number},{$percentage},{$percentage}{$alpha}\)";

        return "/^(?:{$hex}|{$rgb}|{$hsl})$/i";
    }
}
