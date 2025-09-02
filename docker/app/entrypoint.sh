#!/usr/bin/env bash
set -euo pipefail

# Switch to app root
cd /var/www/html

# Ensure required directories exist with correct perms (volumes start empty)
ensure_dirs() {
  # Need root to set ownership the first time
  if [ "$(id -u)" != "0" ]; then
    exec /usr/bin/env bash -lc "sudo -n true 2>/dev/null || true; /usr/local/bin/entrypoint.sh"  # fallback if not root
  fi
}

# If running as www-data (default), temporarily escalate for mkdir/chown via busybox install if available
if command -v install >/dev/null 2>&1; then
  :
else
  # Alpine/nginx images sometimes, but we’re on debian; safe no-op
  :
fi

# Create framework/cache dirs if missing; set ownership to www-data
if [ "$(id -u)" = "0" ]; then
  install -d -m 775 -o www-data -g www-data bootstrap/cache
  install -d -m 775 -o www-data -g www-data storage
  install -d -m 775 -o www-data -g www-data storage/framework \
                               storage/framework/cache \
                               storage/framework/sessions \
                               storage/framework/views \
                               storage/logs
else
  # Try creating without root; if it fails, ignore—container may already have them
  mkdir -p bootstrap/cache storage/framework/{cache,sessions,views} storage/logs || true
fi

# Ensure .env exists
if [ ! -f .env ]; then
  cp .env.example .env 2>/dev/null || true
fi

# If vendor is a mounted empty volume, install dependencies
if [ ! -f vendor/autoload.php ]; then
  # Composer install (no-dev) into the mounted volume
  composer install --no-dev --prefer-dist --no-interaction --no-progress
fi

# Generate APP_KEY if missing
if ! grep -q '^APP_KEY=' .env || grep -q '^APP_KEY=\s*$' .env; then
  php artisan key:generate --force || true
fi

# Create storage symlink (idempotent)
php artisan storage:link || true

# Clear/warm caches (idempotent; don’t fail container on errors)
php artisan config:clear || true
php artisan route:clear  || true
php artisan view:clear   || true
php artisan optimize     || true

# Hand off to CMD
exec "$@"
