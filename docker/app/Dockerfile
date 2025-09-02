# ======================================
# Stage 1: Node build voor frontend-assets
# ======================================
FROM node:20-bullseye AS assets
WORKDIR /app

# 1) Installeer JS-afhankelijkheden
COPY package.json package-lock.json* ./
RUN npm ci || npm install

# 2) Kopieer bronbestanden voor Mix
COPY webpack.mix.js ./
COPY resources/ resources/
COPY public/ public/

# 3) Bouw productiebestanden (CSS/JS)
RUN npm run production

# ======================================
# Stage 2: PHP-FPM runtime (Snipe-IT)
# ======================================
FROM php:8.2-fpm

ENV DEBIAN_FRONTEND=noninteractive

# 4) Installeer systeemlibs voor PHP
RUN apt-get update && apt-get install -y --no-install-recommends \
    git curl zip unzip ca-certificates \
    libjpeg-dev libpng-dev libfreetype6-dev libonig-dev \
 && rm -rf /var/lib/apt/lists/*

# 5) Installeer PHP-extensies + Composer
COPY --from=mlocati/php-extension-installer:2 /usr/bin/install-php-extensions /usr/local/bin/
RUN install-php-extensions @composer pdo_mysql mbstring gd zip bcmath

WORKDIR /var/www/html

# 6) Installeer PHP-dependencies (alleen productie)
COPY composer.json composer.lock* ./
RUN composer install --no-dev --prefer-dist --no-interaction --no-progress || true

# 7) Kopieer applicatiecode
COPY . .

# 8) Maak storage symlink (geen fout bij herhalen)
RUN php artisan storage:link || true

# 9) Kopieer gegenereerde frontend-assets vanuit Node-stage
COPY --from=assets /app/public/ /var/www/html/public/

# 10) Optimaliseer Laravel
RUN php artisan config:clear && php artisan route:clear && php artisan view:clear \
 && php artisan optimize || true
