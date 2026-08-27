#!/usr/bin/env bash
set -euo pipefail

fail() {
    printf 'ERROR: %s\n' "$*" >&2
    exit 1
}

file_env() {
    local variable="$1"
    local required="${2:-false}"
    local file_variable="${variable}_FILE"
    local value="${!variable:-}"
    local file_path="${!file_variable:-}"

    if [ -n "$value" ] && [ -n "$file_path" ]; then
        fail "Set only ${variable} or ${file_variable}, not both."
    fi

    if [ -n "$file_path" ]; then
        [ -r "$file_path" ] || fail "${file_variable} is not readable."
        value="$(cat "$file_path")"
        printf -v "$variable" '%s' "$value"
        export "$variable"
    fi

    unset "$file_variable"

    if [ "$required" = "true" ] && [ -z "${!variable:-}" ]; then
        fail "${variable} must be provided directly or through ${variable}_FILE."
    fi
}

require_env() {
    local variable="$1"

    [ -n "${!variable:-}" ] || fail "${variable} is required."
}

require_true() {
    local variable="$1"

    case "${!variable:-}" in
        1|true|TRUE|yes|YES|on|ON)
            ;;
        *)
            fail "${variable} must be true in the production profile."
            ;;
    esac
}

materialize_passport_key() {
    local variable="$1"
    local destination="$2"
    local expected_marker="$3"
    local file_variable="${variable}_FILE"
    local value="${!variable:-}"
    local source_path="${!file_variable:-}"

    if [ -n "$value" ] && [ -n "$source_path" ]; then
        fail "Set only ${variable} or ${file_variable}, not both."
    fi

    if [ -n "$source_path" ]; then
        [ -r "$source_path" ] || fail "${file_variable} is not readable."
        install -m 0600 -o www-data -g www-data "$source_path" "$destination"
    elif [ -n "$value" ]; then
        printf '%s\n' "$value" > "$destination"
        chown www-data:www-data "$destination"
        chmod 0600 "$destination"
    else
        fail "${variable} must be provided directly or through ${file_variable}."
    fi

    grep -q "$expected_marker" "$destination" \
        || fail "${variable} does not contain the expected PEM marker."

    unset "$variable" "$file_variable"
}

[ "$(id -u)" = "0" ] \
    || fail "The entrypoint must start as root so it can stage secrets before launching the requested service."

cd /var/www/html

file_env APP_KEY true
file_env DB_PASSWORD true
file_env REDIS_PASSWORD true
file_env MAIL_PASSWORD false
file_env AGENT_API_TOKEN false
file_env PRIVATE_AWS_SECRET_ACCESS_KEY false
file_env PUBLIC_AWS_SECRET_ACCESS_KEY false

[ "${APP_ENV:-}" = "production" ] || fail "APP_ENV must be production."

case "${LDAP_INTEGRATION_ENABLED:-false}" in
    0|false|FALSE|no|NO|off|OFF)
        ;;
    1|true|TRUE|yes|YES|on|ON)
        ;;
    *)
        fail "LDAP_INTEGRATION_ENABLED must be a boolean value."
        ;;
esac

case "${MAIL_ENABLED:-false}" in
    0|false|FALSE|no|NO|off|OFF)
        ;;
    1|true|TRUE|yes|YES|on|ON)
        [ "${MAIL_MAILER:-}" = "smtp" ] \
            || fail "MAIL_MAILER must be smtp when MAIL_ENABLED is true."
        require_env MAIL_HOST
        require_env MAIL_PORT
        require_env MAIL_FROM_ADDR
        require_env MAIL_FROM_NAME
        require_env MAIL_REPLYTO_ADDR
        require_env MAIL_REPLYTO_NAME
        require_true MAIL_TLS_VERIFY_PEER
        if [ -n "${MAIL_USERNAME:-}" ] && [ -z "${MAIL_PASSWORD:-}" ]; then
            fail "MAIL_PASSWORD is required when MAIL_USERNAME is set."
        fi
        if [ -n "${MAIL_PASSWORD:-}" ] && [ -z "${MAIL_USERNAME:-}" ]; then
            fail "MAIL_USERNAME is required when MAIL_PASSWORD is set."
        fi
        ;;
    *)
        fail "MAIL_ENABLED must be a boolean value."
        ;;
esac

case "${APP_DEBUG:-false}" in
    0|false|FALSE|no|NO|off|OFF)
        ;;
    *)
        fail "APP_DEBUG must be false."
        ;;
esac

normalized_app_key="${APP_KEY,,}"
case "$normalized_app_key" in
    *changeme*|*example*|*placeholder*)
        fail "APP_KEY is still a placeholder."
        ;;
esac

if [[ "$APP_KEY" == base64:* ]]; then
    php -r '$key = base64_decode(substr((string) getenv("APP_KEY"), 7), true); exit(is_string($key) && strlen($key) === 32 ? 0 : 1);' \
        || fail "APP_KEY must contain exactly 32 decoded bytes for AES-256-CBC."
elif [ "${#APP_KEY}" -ne 32 ]; then
    fail "APP_KEY must contain exactly 32 bytes for AES-256-CBC."
fi

require_env APP_URL
case "$APP_URL" in
    https://*)
        ;;
    *)
        fail "APP_URL must use https://."
        ;;
esac
php -r '$url = parse_url((string) getenv("APP_URL")); exit(is_array($url) && isset($url["host"]) && !isset($url["user"]) && !isset($url["pass"]) ? 0 : 1);' \
    || fail "APP_URL must contain a valid host and no embedded credentials."

require_true APP_FORCE_TLS
require_true SECURE_COOKIES
require_true ENABLE_HSTS
require_true ENABLE_CSP

case "${APP_ALLOW_INSECURE_HOSTS:-false}" in
    0|false|FALSE|no|NO|off|OFF|'')
        ;;
    *)
        fail "APP_ALLOW_INSECURE_HOSTS must be false."
        ;;
esac

require_env APP_TRUSTED_PROXIES
php -r '
    $value = (string) getenv("APP_TRUSTED_PROXIES");
    foreach (explode(",", $value) as $rawProxy) {
        $proxy = trim($rawProxy);
        if ($proxy === "" || $proxy !== $rawProxy || in_array($proxy, ["*", "**"], true)) {
            exit(1);
        }

        $parts = explode("/", $proxy);
        if (count($parts) > 2) {
            exit(1);
        }

        $address = $parts[0];
        $packed = @inet_pton($address);
        if ($packed === false || in_array($address, ["0.0.0.0", "::"], true)) {
            exit(1);
        }

        if (isset($parts[1])) {
            $maximumPrefix = strlen($packed) === 4 ? 32 : 128;
            if (
                $parts[1] === ""
                || !ctype_digit($parts[1])
                || (int) $parts[1] < 1
                || (int) $parts[1] > $maximumPrefix
            ) {
                exit(1);
            }
        }
    }
' || fail "APP_TRUSTED_PROXIES must contain only comma-separated literal IP addresses or non-zero CIDRs for the actual reverse proxy."

require_env DB_CONNECTION
require_env DB_HOST
require_env DB_PORT
require_env DB_DATABASE
require_env DB_USERNAME
[ "${DB_CONNECTION}" = "mysql" ] \
    || fail "DB_CONNECTION must be mysql for the declared V1 MariaDB/MySQL support matrix."
require_env REDIS_HOST
require_env REDIS_PORT

install -d -m 0770 -o www-data -g www-data \
    bootstrap/cache \
    storage/framework/cache \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs
install -d -m 0755 -o www-data -g www-data public/uploads
install -d -m 0750 -o www-data -g www-data \
    storage/app/backup-temp \
    storage/app/backups \
    storage/private_uploads \
    storage/runtime-secrets

# A named volume can be initialized by the web container before the app
# container. Restore only immutable default artwork and never overwrite uploads.
cp -an /opt/snipeit-default-uploads/. public/uploads/
chown -R www-data:www-data public/uploads

materialize_passport_key \
    PASSPORT_PRIVATE_KEY \
    storage/runtime-secrets/oauth-private.key \
    'BEGIN .*PRIVATE KEY'
materialize_passport_key \
    PASSPORT_PUBLIC_KEY \
    storage/runtime-secrets/oauth-public.key \
    'BEGIN PUBLIC KEY'

php -r '
    $private = openssl_pkey_get_private(file_get_contents($argv[1]));
    $public = openssl_pkey_get_public(file_get_contents($argv[2]));
    $privateDetails = $private ? openssl_pkey_get_details($private) : false;
    $publicDetails = $public ? openssl_pkey_get_details($public) : false;
    exit(
        is_array($privateDetails)
        && is_array($publicDetails)
        && ($privateDetails["type"] ?? null) === OPENSSL_KEYTYPE_RSA
        && ($publicDetails["type"] ?? null) === OPENSSL_KEYTYPE_RSA
        && isset($privateDetails["rsa"]["n"], $privateDetails["rsa"]["e"])
        && isset($publicDetails["rsa"]["n"], $publicDetails["rsa"]["e"])
        && ($privateDetails["rsa"]["n"] ?? null) === ($publicDetails["rsa"]["n"] ?? null)
        && ($privateDetails["rsa"]["e"] ?? null) === ($publicDetails["rsa"]["e"] ?? null)
        ? 0
        : 1
    );
' storage/runtime-secrets/oauth-private.key storage/runtime-secrets/oauth-public.key \
    || fail "Passport private/public keys are invalid or do not form a pair."

gosu www-data php artisan package:discover --no-ansi
gosu www-data php artisan config:cache --no-ansi
gosu www-data php artisan view:cache --no-ansi

# Database migrations, key generation, dependency installation, and seeding are
# deliberately deployment operations and never happen during container startup.
# PHP-FPM's master must retain root so it can reopen the official image's
# stderr/access-log file descriptors; its configured request workers still run
# as www-data. All Artisan services and one-shot commands drop privileges here.
if [ "${1:-}" = "php-fpm" ]; then
    exec "$@"
fi

exec gosu www-data "$@"
