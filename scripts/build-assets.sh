#!/usr/bin/env bash
set -euo pipefail

# Build frontend assets once using the existing node service image/volumes.
# Usage: bash scripts/build-assets.sh

if ! command -v docker >/dev/null 2>&1; then
  echo "docker is required" >&2
  exit 1
fi

# Run npm install/build inside a disposable container so compose services stay lightweight.
docker compose run --rm \
  node \
  sh -lc "npm ci --no-audit --no-fund && npm run production"