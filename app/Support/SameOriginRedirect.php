<?php

namespace App\Support;

final class SameOriginRedirect
{
    public static function sanitize(?string $candidate): ?string
    {
        $candidate = trim((string) $candidate);

        if ($candidate === '' || self::containsUnsafeCharacters($candidate)) {
            return null;
        }

        $parts = parse_url($candidate);
        if (!is_array($parts)) {
            return null;
        }

        $isAbsolute = isset($parts['scheme']) || isset($parts['host']);
        $path = (string) ($parts['path'] ?? ($isAbsolute ? '/' : ''));
        if (!self::hasSafePath($path)) {
            return null;
        }

        if (!$isAbsolute) {
            if (
                isset($parts['scheme'])
                || isset($parts['host'])
                || isset($parts['port'])
                || isset($parts['user'])
                || isset($parts['pass'])
                || !str_starts_with($path, '/')
            ) {
                return null;
            }

            return self::isWithinApplicationPath($path) ? $candidate : null;
        }

        if (
            empty($parts['scheme'])
            || empty($parts['host'])
            || isset($parts['user'])
            || isset($parts['pass'])
        ) {
            return null;
        }

        $application = parse_url(url('/'));
        if (!is_array($application) || empty($application['scheme']) || empty($application['host'])) {
            return null;
        }

        if (
            strtolower((string) $parts['scheme']) !== strtolower((string) $application['scheme'])
            || strtolower((string) $parts['host']) !== strtolower((string) $application['host'])
            || self::effectivePort($parts) !== self::effectivePort($application)
        ) {
            return null;
        }

        return self::isWithinApplicationPath($path) ? $candidate : null;
    }

    private static function containsUnsafeCharacters(string $candidate): bool
    {
        $decoded = $candidate;

        for ($attempt = 0; $attempt < 3; $attempt++) {
            if (preg_match('/[\x00-\x1F\x7F\\\\]/', $decoded) === 1) {
                return true;
            }

            $next = rawurldecode($decoded);
            if ($next === $decoded) {
                break;
            }

            $decoded = $next;
        }

        return preg_match('/[\x00-\x1F\x7F\\\\]/', $decoded) === 1;
    }

    private static function hasSafePath(string $path): bool
    {
        if ($path === '' || preg_match('/%(?:2f|5c)/i', $path) === 1) {
            return false;
        }

        $decoded = $path;
        for ($attempt = 0; $attempt < 3; $attempt++) {
            $next = rawurldecode($decoded);
            if ($next === $decoded) {
                break;
            }

            $decoded = $next;
        }

        if (
            str_starts_with($decoded, '//')
            || str_contains($decoded, '\\')
            || preg_match('#(^|/)\.{1,2}(/|$)#', $decoded) === 1
        ) {
            return false;
        }

        return str_starts_with($decoded, '/');
    }

    private static function isWithinApplicationPath(string $path): bool
    {
        $applicationPath = rtrim((string) (parse_url(url('/'), PHP_URL_PATH) ?? ''), '/');

        if ($applicationPath === '') {
            return true;
        }

        return $path === $applicationPath || str_starts_with($path, $applicationPath.'/');
    }

    /**
     * @param array<string, mixed> $parts
     */
    private static function effectivePort(array $parts): ?int
    {
        if (isset($parts['port'])) {
            return (int) $parts['port'];
        }

        return match (strtolower((string) ($parts['scheme'] ?? ''))) {
            'http' => 80,
            'https' => 443,
            default => null,
        };
    }
}
