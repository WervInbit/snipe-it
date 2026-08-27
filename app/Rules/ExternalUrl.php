<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Restrict outbound webhook targets to public HTTP(S) addresses.
 */
class ExternalUrl implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $message = trans('validation.external_url', [
            'attribute' => str_replace('_', ' ', $attribute),
        ]);

        if (! is_string($value) || $value === '') {
            $fail($message);

            return;
        }

        $parts = parse_url($value);

        if (
            $parts === false
            || empty($parts['scheme'])
            || empty($parts['host'])
            || isset($parts['user'])
            || isset($parts['pass'])
        ) {
            $fail($message);

            return;
        }

        if (! in_array(strtolower($parts['scheme']), ['http', 'https'], true)) {
            $fail($message);

            return;
        }

        if (config('app.webhook_allow_internal_targets')) {
            return;
        }

        $host = strtolower($parts['host']);

        if (str_starts_with($host, '[') && str_ends_with($host, ']')) {
            $host = substr($host, 1, -1);
        }

        $ips = filter_var($host, FILTER_VALIDATE_IP) ? [$host] : $this->resolveHost($host);

        if ($ips === []) {
            $fail($message);

            return;
        }

        foreach ($ips as $ip) {
            if (! $this->isPublicIp($ip)) {
                $fail($message);

                return;
            }
        }
    }

    private function isPublicIp(string $ip): bool
    {
        if (stripos($ip, '::ffff:') === 0) {
            $mappedIpv4 = substr($ip, 7);

            if (filter_var($mappedIpv4, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                $ip = $mappedIpv4;
            }
        }

        return (bool) filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        );
    }

    private function resolveHost(string $host): array
    {
        $ips = [];
        $ipv4Records = @gethostbynamel($host);

        if (is_array($ipv4Records)) {
            $ips = array_merge($ips, $ipv4Records);
        }

        $ipv6Records = @dns_get_record($host, DNS_AAAA);

        if (is_array($ipv6Records)) {
            foreach ($ipv6Records as $record) {
                if (! empty($record['ipv6'])) {
                    $ips[] = $record['ipv6'];
                }
            }
        }

        return array_values(array_unique($ips));
    }
}
