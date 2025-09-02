# ======================================
# Stage 1: Build frontend assets (Node)
# ======================================
FROM node:20-bullseye AS assets
WORKDIR /app

# JS deps (lockfile if present)
COPY package.json package-lock.json* ./
RUN npm ci || npm install

# Mix config + sources
COPY webpack.mix.js ./
COPY resources/ resources/
# Copy entire public tree; .dockerignore MUST exclude public/storage
COPY public/ public/

# Make Mix resolve the correct public root (prevents /css/... ENOENT)
ENV MIX_PUBLIC_PATH=/app/public

RUN npm run production

# ======================================
# Stage 2: PHP-FPM runtime (Laravel/Snipe-IT)
# ======================================
FROM php:8.2-fpm

ENV DEBIAN_FRONTEND=noninteractive

# System libs for PHP extensions and composer runtime
RUN apt-get update && apt-get install -y --no-install-recommends \
    git curl zip unzip ca-certificates \
    libjpeg-dev libpng-dev libfreetype6-dev libonig-dev \
 && rm -rf /var/lib/apt/lists/*

# PHP extensions + Composer
COPY --from=mlocati/php-extension-installer:2 /usr/bin/install-php-extensions /usr/local/bin/
RUN install-php-extensions @composer pdo_mysql mbstring gd zip bcmath

WORKDIR /var/www/html

# Composer dep layer (kept for cache hinting; final install happens at runtime due to volumes)
COPY composer.json composer.lock* ./

# Application code (vendor/storage are volume-mounted at runtime)
COPY . .

# Inject built frontend assets
COPY --from=assets /app/public/ /var/www/html/public/

# Entrypoint ensures runtime init on every container start
COPY docker/app/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

USER www-data
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["php-fpm"]
