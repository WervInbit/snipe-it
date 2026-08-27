# syntax=docker/dockerfile:1.7@sha256:a57df69d0ea827fb7266491f2813635de6f17269be881f696fbfdf2d83dda33e

# Multi-platform image indexes resolved 2026-08-13. Keep the readable tags and
# update the digest deliberately when refreshing the V1 build toolchain.
FROM node:20-bookworm-slim@sha256:2cf067cfed83d5ea958367df9f966191a942351a2df77d6f0193e162b5febfc0 AS frontend

WORKDIR /build

COPY package.json package-lock.json ./
COPY packages/brace-expansion-compat/ packages/brace-expansion-compat/
COPY scripts/patch-bootstrap3-security.cjs scripts/patch-bootstrap3-security.cjs
RUN --mount=type=cache,target=/root/.npm \
    npm ci --no-audit --no-fund

COPY webpack.mix.js ./
COPY resources/ resources/
COPY public/ public/

ENV NODE_ENV=production
RUN npm run production


FROM php:8.2-fpm-bookworm@sha256:5623a1f394cfc9ec9710efc975db6d746618f1c3e047649232d5432a0b2f942c AS php-base

ENV DEBIAN_FRONTEND=noninteractive

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        ca-certificates \
        curl \
        default-mysql-client \
        gosu \
    && rm -rf /var/lib/apt/lists/*

COPY --from=mlocati/php-extension-installer:2@sha256:b6d3fa381b9ba5cf051117c1c601d6a523b590e534bf3d56eb4fbe352949c138 /usr/bin/install-php-extensions /usr/local/bin/
# The official PHP image marks versioned compiler and header dependencies as
# manual packages, so purging PHPIZE_DEPS alone leaves build tools in runtime.
RUN install-php-extensions \
        bcmath \
        curl \
        exif \
        gd \
        ldap \
        mbstring \
        opcache \
        pcntl \
        pdo_mysql \
        redis \
        zip \
    && apt-get purge -y --auto-remove \
        ${PHPIZE_DEPS} \
        '*-dev' \
        cpp \
        cpp-12 \
        g++-12 \
        gcc-12 \
        pkgconf \
        pkgconf-bin \
    && rm -rf /var/lib/apt/lists/* \
    && rm -f /usr/local/bin/install-php-extensions

COPY docker/production/php.ini /usr/local/etc/php/conf.d/zz-snipeit-production.ini
COPY docker/production/php-fpm.conf /usr/local/etc/php-fpm.d/zz-snipeit-production.conf

WORKDIR /var/www/html


FROM php-base AS application-build

RUN apt-get update \
    && apt-get install -y --no-install-recommends git \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2.8@sha256:5248900ab8b5f7f880c2d62180e40960cd87f60149ec9a1abfd62ac72a02577c /usr/bin/composer /usr/local/bin/composer
COPY composer.json composer.lock patches.lock.json ./
COPY patches/ patches/

RUN --mount=type=cache,target=/tmp/composer-cache \
    COMPOSER_CACHE_DIR=/tmp/composer-cache composer install \
        --no-dev \
        --no-interaction \
        --no-progress \
        --no-scripts \
        --no-autoloader \
        --prefer-dist

COPY . .
COPY --from=frontend /build/public/ /var/www/html/public/

RUN composer dump-autoload \
        --no-dev \
        --no-interaction \
        --no-scripts \
        --classmap-authoritative \
    && php artisan package:discover --ansi \
    && php artisan vendor:publish --force --tag=livewire:assets --ansi \
    && rm -rf \
        .git \
        .github \
        ansible \
        docker \
        docs \
        heroku \
        node_modules \
        sample_csvs \
        scripts \
        tests \
        storage/logs/* \
        storage/framework/cache/* \
        storage/framework/sessions/* \
        storage/framework/views/* \
    && rm -f \
        .env \
        .env.* \
        Dockerfile* \
        docker-compose*.yml \
        package.json \
        package-lock.json \
        storage/oauth-private.key \
        storage/oauth-public.key \
        webpack.mix.js \
    && mkdir -p \
        bootstrap/cache \
        storage/app/backup-temp \
        public/uploads \
        storage/app/backups \
        storage/framework/cache \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs \
        storage/private_uploads \
        storage/runtime-secrets \
    && ln -s runtime-secrets/oauth-private.key storage/oauth-private.key \
    && ln -s runtime-secrets/oauth-public.key storage/oauth-public.key \
    && chown -R www-data:www-data \
        bootstrap/cache \
        public/uploads \
        storage


FROM php-base AS app

COPY --from=application-build --chown=www-data:www-data /var/www/html /var/www/html
COPY --from=application-build --chown=www-data:www-data /var/www/html/public/uploads/ /opt/snipeit-default-uploads/
COPY docker/production/entrypoint.sh /usr/local/bin/snipeit-production-entrypoint

RUN chmod 0755 /usr/local/bin/snipeit-production-entrypoint

ENTRYPOINT ["/usr/local/bin/snipeit-production-entrypoint"]
CMD ["php-fpm", "--nodaemonize"]

EXPOSE 9000


FROM nginxinc/nginx-unprivileged:1.30-alpine@sha256:44e36330f74d4f3a1d4e222acca9e23b401fb87811a7597024502bb759c4dd49 AS web

USER root

COPY docker/production/nginx.conf /etc/nginx/conf.d/default.conf
COPY --from=application-build /var/www/html/public/ /var/www/html/public/

RUN rm -rf /var/www/html/public/uploads \
    && mkdir -p /var/www/html/public/uploads \
    && chown -R nginx:nginx /var/www/html/public

USER nginx

EXPOSE 8080
