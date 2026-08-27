<?php

namespace App\Support;

final class RouteLink
{
    public static function to(string $routeName, mixed $label, mixed $parameters = []): string
    {
        return sprintf(
            '<a href="%s">%s</a>',
            e(route($routeName, $parameters)),
            e((string) $label),
        );
    }
}
