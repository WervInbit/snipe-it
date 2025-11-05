#!/usr/bin/env bash
set -euo pipefail

# Simple writeability check for Laravel cache directories.
check_dir() {
    local dir="$1"
    if [[ ! -d "$dir" ]]; then
        echo "WARN  Directory not found: $dir"
        return 1
    fi

    local probe="$dir/.perm_check.$$"
    if touch "$probe" 2>/dev/null; then
        rm -f "$probe"
        echo "OK    $dir is writable"
        return 0
    fi

    echo "FAIL  $dir is not writable"
    return 1
}

main() {
    local -a targets=(
        "storage/framework/cache"
        "storage/framework/sessions"
        "storage/framework/views"
        "storage/logs"
        "bootstrap/cache"
    )

    local status=0
    for dir in "${targets[@]}"; do
        if ! check_dir "$dir"; then
            status=1
        fi
    done

    if [[ $status -ne 0 ]]; then
        cat <<'EOF'

One or more directories are not writable. Fix with:
  docker compose exec --user root app chown -R www-data:www-data storage bootstrap/cache
  docker compose exec --user root app chmod -R ug+rwX storage bootstrap/cache
EOF
    fi

    return "$status"
}

main "$@"
