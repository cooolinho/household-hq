# syntax=docker/dockerfile:1

# ---------------------------------------------------------------------------
# Stage 1: PHP Dependencies
# ---------------------------------------------------------------------------
FROM --platform=$BUILDPLATFORM composer:2 AS vendor
WORKDIR /app

COPY laravel/composer.json laravel/composer.lock ./
RUN composer install \
        --no-dev \
        --no-scripts \
        --no-autoloader \
        --prefer-dist \
        --no-interaction \
        --ignore-platform-reqs

COPY laravel/ ./
RUN composer dump-autoload --no-dev --optimize --no-scripts --classmap-authoritative

# ---------------------------------------------------------------------------
# Stage 2: Frontend Assets
# ---------------------------------------------------------------------------
FROM --platform=$BUILDPLATFORM node:24-bookworm-slim AS assets
WORKDIR /app

COPY laravel/package.json laravel/yarn.lock laravel/.yarnrc.yml ./
RUN corepack enable && yarn install --frozen-lockfile

COPY laravel/ ./
COPY --from=vendor /app/vendor ./vendor
RUN yarn build

# ---------------------------------------------------------------------------
# Stage 3: Runtime Image (App + Nginx + PHP-FPM)
# ---------------------------------------------------------------------------
FROM ubuntu:24.04 AS runtime

ARG APP_VERSION=dev
ENV APP_VERSION=${APP_VERSION} \
    DEBIAN_FRONTEND=noninteractive \
    TZ=UTC

LABEL org.opencontainers.image.title="Household HQ (Production App)" \
      org.opencontainers.image.description="Standalone production app image for Household HQ (Laravel + Filament)" \
      org.opencontainers.image.source="https://github.com/cooolinho/household-hq" \
      org.opencontainers.image.licenses="MIT"

RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ >/etc/timezone

RUN apt-get update \
    && apt-get install -y --no-install-recommends gnupg curl ca-certificates \
    && mkdir -p /etc/apt/keyrings \
    && curl -sS 'https://keyserver.ubuntu.com/pks/lookup?op=get&search=0xb8dc7e53946656efbce4c1dd71daeaab4ad4cab6' \
        | gpg --dearmor | tee /etc/apt/keyrings/ppa_ondrej_php.gpg >/dev/null \
    && echo "deb [signed-by=/etc/apt/keyrings/ppa_ondrej_php.gpg] https://ppa.launchpadcontent.net/ondrej/php/ubuntu noble main" \
        >/etc/apt/sources.list.d/ppa_ondrej_php.list \
    && apt-get update \
    && apt-get install -y --no-install-recommends \
        php8.5-cli \
        php8.5-fpm \
        php8.5-mysql \
        php8.5-redis \
        php8.5-gd \
        php8.5-curl \
        php8.5-imap \
        php8.5-intl \
        php8.5-mbstring \
        php8.5-xml \
        php8.5-zip \
        php8.5-bcmath \
        php8.5-readline \
        php8.5-sqlite3 \
        nginx \
        supervisor \
        openssl \
    && update-alternatives --set php /usr/bin/php8.5 \
    && apt-get purge -y gnupg \
    && apt-get autoremove -y \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

RUN mkdir -p /var/www/.home \
    && chown www-data:www-data /var/www/.home \
    && usermod --home /var/www/.home --shell /bin/bash www-data

COPY --from=vendor /usr/bin/composer /usr/local/bin/composer

COPY docker/all-in-one/php.ini /etc/php/8.5/cli/conf.d/99-portal.ini
COPY docker/all-in-one/php.ini /etc/php/8.5/fpm/conf.d/99-portal.ini
COPY docker/all-in-one/php-fpm-www.conf /etc/php/8.5/fpm/pool.d/www.conf
COPY docker/all-in-one/nginx.conf /etc/nginx/sites-available/default
COPY docker/production/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

WORKDIR /var/www/html

COPY laravel/ ./
COPY --from=vendor /app/vendor ./vendor
COPY --from=assets /app/public/build ./public/build

RUN chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R u+rwX,g+rX storage bootstrap/cache

RUN composer check-platform-reqs --no-dev --lock \
    && DB_CONNECTION=sqlite DB_DATABASE=:memory: php artisan package:discover --ansi \
    && DB_CONNECTION=sqlite DB_DATABASE=:memory: php artisan filament:assets --ansi

EXPOSE 80

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
