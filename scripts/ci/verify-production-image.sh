#!/usr/bin/env bash

set -euo pipefail

if [[ $# -ne 1 || -z "$1" ]]; then
    echo "Usage: $0 <image-tag>" >&2
    exit 64
fi

image_tag="$1"

docker image inspect "$image_tag" >/dev/null

docker run --rm --entrypoint /bin/sh "$image_tag" -s <<'CONTAINER_CHECK'
set -eu

root=/var/www/html

fail()
{
    echo "Production image verification failed: $*" >&2
    exit 1
}

command -v git >/dev/null 2>&1 && fail "git is installed in the runtime image"

git_metadata="$(find "$root" -name .git -print -quit 2>/dev/null || true)"
[ -z "$git_metadata" ] || fail "Git metadata is present: $git_metadata"

for forbidden_path in \
    "$root/prodbak" \
    "$root/codexlog" \
    "$root/docker" \
    "$root/docs" \
    "$root/output" \
    "$root/test-results" \
    "$root/tests" \
    "$root/tmp" \
    "$root/vendor/laravelcollective"
do
    [ ! -e "$forbidden_path" ] || fail "forbidden runtime path is present: $forbidden_path"
done

local_root_artifacts="$(
    find "$root" -maxdepth 1 -type f \( \
        -name '*.code-workspace' -o \
        -name '*.jpg' -o \
        -name '*.jpeg' -o \
        -name '*.png' -o \
        -name '*.pdf' -o \
        -name '*.tar' -o \
        -name '*.tar.*' -o \
        -name 'Corefile' -o \
        -name 'TEMPFILE' -o \
        -name 'dont_commit*' -o \
        -name 'hw-inventory.*' \
    \) -print 2>/dev/null || true
)"
[ -z "$local_root_artifacts" ] \
    || fail "local debug, inventory, or generated artifacts are present: $local_root_artifacts"

secret_files="$(
    find "$root" \
        -path "$root/vendor" -prune -o \
        -type f \( \
            -name '.env' -o \
            -name '.env.*' -o \
            -name '*.bak' -o \
            -name '*.dump' -o \
            -name '*.sql' -o \
            -name '*.sqlite' -o \
            -name '*.sqlite-*' -o \
            -name '*.db' -o \
            -name '*.db-*' -o \
            -name '*.db3' -o \
            -name '*.s3db' -o \
            -name '*.rdb' -o \
            -name '*.mdb' -o \
            -name '*.accdb' -o \
            -name '*.key' -o \
            -name '*.pem' -o \
            -name '*.crt' -o \
            -name '*.cer' -o \
            -name '*.der' -o \
            -name '*.csr' -o \
            -name '*.p12' -o \
            -name '*.pfx' -o \
            -name '*.ppk' -o \
            -name '*.jks' -o \
            -name '*.keystore' -o \
            -name 'id_rsa' -o \
            -name 'id_dsa' -o \
            -name 'id_ecdsa' -o \
            -name 'id_ed25519' \
        \) -print 2>/dev/null || true
)"
[ -z "$secret_files" ] || fail "secret, backup, dump, database, or key files are present: $secret_files"

if [ -f "$root/artisan" ]; then
    [ -f "$root/vendor/laravel/framework/src/Illuminate/Mail/Mailables/Address.php" ] \
        || fail "Laravel mail address implementation is missing"
    grep -F "Email addresses may not contain line break characters." \
        "$root/vendor/laravel/framework/src/Illuminate/Mail/Mailables/Address.php" >/dev/null \
        || fail "the CRLF email security backport is missing"
    grep -F "strtr(rawurlencode(" \
        "$root/vendor/laravel/framework/src/Illuminate/Filesystem/LocalFilesystemAdapter.php" >/dev/null \
        || fail "the local temporary-URL security backport is missing"

    upload_files="$(
        find "$root/public/uploads" -type f 2>/dev/null \
            | sed "s#^$root/public/uploads/##" \
            | while IFS= read -r path; do
                case "$path" in
                    .gitkeep|*/.gitkeep|default.png|snipe-logo.png|snipe-logo-lg.png|barcodes/invalid_barcode.gif|companies/company-image-test.png)
                        ;;
                    *)
                        printf '%s\n' "$path"
                        ;;
                esac
            done
    )"
    [ -z "$upload_files" ] || fail "unexpected public upload files are present: $upload_files"

    for empty_path in \
        "$root/storage/app/backup-temp" \
        "$root/storage/app/backups" \
        "$root/storage/private_uploads"
    do
        [ -d "$empty_path" ] || fail "required empty runtime directory is missing: $empty_path"
        contents="$(find "$empty_path" -mindepth 1 -print -quit 2>/dev/null || true)"
        [ -z "$contents" ] || fail "runtime data was baked into $empty_path: $contents"
    done

    [ -L "$root/storage/oauth-private.key" ] \
        || fail "Passport private key path is not a runtime-secret symlink"
    [ -L "$root/storage/oauth-public.key" ] \
        || fail "Passport public key path is not a runtime-secret symlink"
else
    [ ! -e "$root/vendor" ] || fail "the web image unexpectedly contains PHP dependencies"
    web_uploads="$(find "$root/public/uploads" -mindepth 1 -print -quit 2>/dev/null || true)"
    [ -z "$web_uploads" ] || fail "the web image contains baked-in runtime uploads: $web_uploads"
fi

echo "Production image content verified."
CONTAINER_CHECK
