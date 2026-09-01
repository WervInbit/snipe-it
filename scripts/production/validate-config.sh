#!/usr/bin/env bash

set -euo pipefail

usage() {
    cat <<'EOF'
Usage: scripts/production/validate-config.sh ENV_FILE [options]

Options:
  --managed-dependencies  Include the repository-managed MariaDB/Redis overlay.
  --edge                 Include the repository-managed public TLS edge overlay.
  --local-registry       Include the loopback-only offline-transfer registry.
  --help                 Show this help text.

The command is read-only. It validates references and resolves the selected
Docker Compose profile without printing secret contents.

Set DOCKER_COMPOSE_BIN to an executable standalone Compose v2 plugin when
`docker compose` is not available in the deployment user's PATH.
EOF
}

if [ "$#" -lt 1 ]; then
    usage >&2
    exit 64
fi

env_file="$1"
shift
managed_dependencies=false
managed_edge=false
local_registry=false

while [ "$#" -gt 0 ]; do
    case "$1" in
        --managed-dependencies)
            managed_dependencies=true
            ;;
        --edge)
            managed_edge=true
            ;;
        --local-registry)
            local_registry=true
            ;;
        --help)
            usage
            exit 0
            ;;
        *)
            echo "Unknown option: $1" >&2
            usage >&2
            exit 64
            ;;
    esac
    shift
done

if [ ! -f "$env_file" ]; then
    echo "Production environment file not found: $env_file" >&2
    exit 1
fi

env_mode="$(stat -c '%a' "$env_file" 2>/dev/null || true)"
if [ -n "$env_mode" ] && [ $((8#$env_mode & 8#077)) -ne 0 ]; then
    echo 'Production environment file must not be accessible by group or others.' >&2
    exit 1
fi

repo_root="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
env_file="$(cd "$(dirname "$env_file")" && pwd)/$(basename "$env_file")"

read_env_value() {
    local key="$1"
    sed -n "s/^${key}=//p" "$env_file" | tail -n 1
}

require_env_reference() {
    local key="$1"
    local value
    value="$(read_env_value "$key")"
    if [ -z "$value" ]; then
        echo "Missing required environment reference: $key" >&2
        exit 1
    fi
}

require_file_reference() {
    local key="$1"
    local value
    value="$(read_env_value "$key")"
    if [ -z "$value" ] || [ ! -f "$value" ]; then
        echo "Required file reference is missing or unreadable: $key" >&2
        exit 1
    fi

    if command -v stat >/dev/null 2>&1; then
        local mode
        mode="$(stat -c '%a' "$value" 2>/dev/null || true)"
        if [ -n "$mode" ] && [ $((8#$mode & 8#077)) -ne 0 ]; then
            echo "Referenced secret/key must not be accessible by group or others: $key" >&2
            exit 1
        fi
    fi
}

require_readable_file_reference() {
    local key="$1"
    local value
    value="$(read_env_value "$key")"
    if [ -z "$value" ] || [ ! -f "$value" ]; then
        echo "Required file reference is missing or unreadable: $key" >&2
        exit 1
    fi
}

for key in \
    SNIPEIT_APP_IMAGE \
    SNIPEIT_APP_IMAGE_DIGEST \
    SNIPEIT_WEB_IMAGE \
    SNIPEIT_WEB_IMAGE_DIGEST \
    APP_URL \
    APP_TRUSTED_PROXIES \
    DB_DATABASE \
    DB_USERNAME; do
    require_env_reference "$key"
done

for key in \
    APP_KEY_FILE \
    DB_PASSWORD_FILE \
    REDIS_PASSWORD_FILE \
    AGENT_API_TOKEN_FILE \
    PASSPORT_PRIVATE_KEY_FILE \
    PASSPORT_PUBLIC_KEY_FILE; do
    require_file_reference "$key"
done

for key in SNIPEIT_APP_IMAGE_DIGEST SNIPEIT_WEB_IMAGE_DIGEST; do
    digest="$(read_env_value "$key")"
    if [[ ! "$digest" =~ ^sha256:[0-9a-f]{64}$ ]] || [[ "$digest" =~ ^sha256:0{64}$ ]]; then
        echo "Invalid or placeholder image digest: $key" >&2
        exit 1
    fi
done

app_url="$(read_env_value APP_URL)"
if [[ ! "$app_url" =~ ^https://[^/[:space:]]+/?$ ]]; then
    echo 'APP_URL must be an HTTPS origin without a path.' >&2
    exit 1
fi

trusted_proxies="$(read_env_value APP_TRUSTED_PROXIES)"
IFS=',' read -r -a proxy_entries <<< "$trusted_proxies"
for proxy_entry in "${proxy_entries[@]}"; do
    proxy_entry="${proxy_entry//[[:space:]]/}"
    case "$proxy_entry" in
        ''|'*'|'**'|'0.0.0.0/0'|'::/0')
            echo 'APP_TRUSTED_PROXIES must contain only narrow addresses or CIDRs.' >&2
            exit 1
            ;;
    esac
done

if [ "$managed_edge" = true ]; then
    require_env_reference SNIPEIT_NETWORK_SUBNET
    network_subnet="$(read_env_value SNIPEIT_NETWORK_SUBNET)"
    network_subnet="${network_subnet//[[:space:]]/}"
    proxy_subnet_found=false
    for proxy_entry in "${proxy_entries[@]}"; do
        proxy_entry="${proxy_entry//[[:space:]]/}"
        if [ "$proxy_entry" = "$network_subnet" ]; then
            proxy_subnet_found=true
            break
        fi
    done
    if [ "$proxy_subnet_found" != true ]; then
        echo 'APP_TRUSTED_PROXIES must include SNIPEIT_NETWORK_SUBNET when the managed edge is selected.' >&2
        exit 1
    fi
fi

if [ -n "${DOCKER_COMPOSE_BIN:-}" ]; then
    if [ ! -x "$DOCKER_COMPOSE_BIN" ]; then
        echo 'DOCKER_COMPOSE_BIN is not executable.' >&2
        exit 1
    fi
    compose_command=( "$DOCKER_COMPOSE_BIN" )
elif docker compose version >/dev/null 2>&1; then
    compose_command=( docker compose )
elif command -v docker-compose >/dev/null 2>&1; then
    compose_command=( docker-compose )
else
    echo 'Docker Compose v2 is not available; set DOCKER_COMPOSE_BIN.' >&2
    exit 1
fi

compose_version="$("${compose_command[@]}" version --short 2>/dev/null || true)"
case "$compose_version" in
    2.*|v2.*)
        ;;
    *)
        echo 'Docker Compose v2 is required.' >&2
        exit 1
        ;;
esac

compose=(
    "${compose_command[@]}"
    --env-file "$env_file"
    -f "$repo_root/docker-compose.production.yml"
)

if [ "$managed_dependencies" = true ]; then
    require_file_reference DB_ROOT_PASSWORD_FILE
    compose+=( -f "$repo_root/docker-compose.production.dependencies.yml" )
fi

if [ "$managed_edge" = true ]; then
    require_readable_file_reference TLS_CERTIFICATE_FILE
    require_file_reference TLS_PRIVATE_KEY_FILE
    compose+=( -f "$repo_root/docker-compose.production.edge.yml" )
fi

profiles=( --profile production )
if [ "$local_registry" = true ]; then
    compose+=( -f "$repo_root/docker-compose.production.registry.yml" )
    profiles+=( --profile registry )
fi

"${compose[@]}" "${profiles[@]}" config --quiet

echo 'Production configuration is valid.'
echo "Managed dependencies: $managed_dependencies"
echo "Managed TLS edge: $managed_edge"
echo "Loopback registry: $local_registry"
echo 'Resolved services:'
"${compose[@]}" "${profiles[@]}" config --services
