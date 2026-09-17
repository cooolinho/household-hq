# syntax=docker/dockerfile:1

# All-in-One-Image von Personal Home Portal: App (nginx + php-fpm), Horizon,
# Scheduler, MySQL und Redis in einem einzigen Container. Siehe
# docs/docker-image.md fuer Nutzung, ENV-Referenz und Betriebshinweise.
#
# Build-Kontext ist das Repo-Root (die Laravel-App liegt unter laravel/),
# damit dieses Dockerfile parallel zum unveraenderten Dev-Setup
# (docker-compose.yml + docker/Dockerfile) bestehen kann.

# ---------------------------------------------------------------------------
# Stage 1: PHP-Abhaengigkeiten (production-only, kein Netzwerkzugriff mehr im
# runtime-Stage noetig)
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
# Stage 2: Frontend-Assets (Filament-Themes via Vite/Sass/Tailwind)
# ---------------------------------------------------------------------------
FROM --platform=$BUILDPLATFORM node:24-bookworm-slim AS assets
WORKDIR /app

COPY laravel/package.json laravel/yarn.lock laravel/.yarnrc.yml ./
RUN corepack enable && yarn install --frozen-lockfile

COPY laravel/ ./
# resources/scss/filament/_shared/vendors/_filament-theme.scss importiert
# vendor/filament/filament/resources/css/theme.css direkt aus dem Composer-
# Paket - der Theme-Build braucht daher denselben vendor/-Stand wie die App.
COPY --from=vendor /app/vendor ./vendor
RUN yarn build

# ---------------------------------------------------------------------------
# Stage 3: Laufzeit-Image
# ---------------------------------------------------------------------------
FROM ubuntu:24.04 AS runtime

ARG APP_VERSION=dev
ENV APP_VERSION=${APP_VERSION} \
    DEBIAN_FRONTEND=noninteractive \
    TZ=UTC

LABEL org.opencontainers.image.title="Personal Home Portal" \
      org.opencontainers.image.description="Self-hosted portal for insurances, accounts, contracts and inventory (Laravel + Filament)" \
      org.opencontainers.image.source="https://github.com/cooolinho/personal-home-portal" \
      org.opencontainers.image.licenses="MIT"

RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ >/etc/timezone

# ondrej/php liefert PHP 8.5 fuer Ubuntu 24.04 (noble) - dieselbe PPA wie im
# Dev-Image unter docker/Dockerfile, damit sich beide Umgebungen so wenig wie
# moeglich unterscheiden.
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
        mysql-server \
        redis-server \
        supervisor \
        openssl \
    && update-alternatives --set php /usr/bin/php8.5 \
    && apt-get purge -y gnupg \
    && apt-get autoremove -y \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

# www-data kommt ohne Login-Shell und mit einem root-eigenen $HOME
# (/var/www) - reicht fuer php-fpm, aber `docker exec ... php artisan
# tinker` (PsySH) und ein interaktives `bash` scheitern dann am Schreiben
# nach ~/.config. Eigenes, www-data-eigenes Home dafuer, statt /var/www/html
# selbst beschreibbar zu machen (App-Code bleibt sonst read-only fuer www-data).
RUN mkdir -p /var/www/.home \
    && chown www-data:www-data /var/www/.home \
    && usermod --home /var/www/.home --shell /bin/bash www-data

# Composer-Binary aus Stage 1 uebernehmen: fuer den Plattform-Check unten und
# fuer alle spaeteren `docker exec ... composer ...`-Aufrufe.
COPY --from=vendor /usr/bin/composer /usr/local/bin/composer

COPY docker/all-in-one/php.ini /etc/php/8.5/cli/conf.d/99-portal.ini
COPY docker/all-in-one/php.ini /etc/php/8.5/fpm/conf.d/99-portal.ini
COPY docker/all-in-one/php-fpm-www.conf /etc/php/8.5/fpm/pool.d/www.conf
COPY docker/all-in-one/nginx.conf /etc/nginx/sites-available/default
COPY docker/all-in-one/mysql.cnf /etc/mysql/mysql.conf.d/zz-portal.cnf
COPY docker/all-in-one/redis.conf /etc/redis/redis-portal.conf
COPY docker/all-in-one/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/all-in-one/entrypoint.sh /usr/local/bin/entrypoint
COPY docker/all-in-one/bootstrap.sh /usr/local/bin/bootstrap
RUN chmod +x /usr/local/bin/entrypoint /usr/local/bin/bootstrap

WORKDIR /var/www/html

COPY laravel/ ./
COPY --from=vendor /app/vendor ./vendor
COPY --from=assets /app/public/build ./public/build

# storage/ und bootstrap/cache/ muessen fuer www-data beschreibbar sein (Views,
# Sessions-Fallback, Framework-Caches). storage/app und storage/logs werden
# unten durch Symlinks ins /data-Volume ersetzt, storage/framework/* und
# bootstrap/cache/ bleiben ganz normal im Image (kein Nutzerinhalt).
RUN chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R u+rwX,g+rX storage bootstrap/cache

# /data ist das einzige Volume: MySQL-/Redis-Daten, hochgeladene Dateien und
# Logs ueberleben damit Neustarts und Image-Updates.
RUN rm -rf storage/app storage/logs \
    && ln -s /data/storage/app storage/app \
    && ln -s /data/logs storage/logs \
    && ln -sf ../storage/app/public public/storage

# Composer-Autoloader neu einbinden (die App-Konstanten/Klassen aus Stage 1
# muessen zur gerade kopierten vendor/-Struktur passen) und Filament-Assets
# veroeffentlichen. routes/console.php fragt bei jedem Artisan-Aufruf
# Schema::hasTable('settings') ab - eine In-Memory-SQLite-DB laesst diesen
# Check ohne echte Datenbank einfach "nein" beantworten, statt beim Build
# gegen die (hier nicht existierende) MySQL-Verbindung zu laufen.
RUN composer check-platform-reqs --no-dev --lock \
    && DB_CONNECTION=sqlite DB_DATABASE=:memory: php artisan package:discover --ansi \
    && DB_CONNECTION=sqlite DB_DATABASE=:memory: php artisan filament:assets --ansi

# Default-ENV: per `docker run -e ...` ueberschreibbar. Vollstaendige
# Referenz in docs/docker-image.md.
ENV APP_NAME="Personal Home Portal" \
    APP_ENV=production \
    APP_DEBUG=false \
    APP_URL=http://localhost:8080 \
    APP_TIMEZONE=Europe/Berlin \
    APP_LOCALE=de \
    APP_FALLBACK_LOCALE=de \
    APP_FAKER_LOCALE=de_DE \
    LOG_CHANNEL=stack \
    LOG_STACK=daily \
    LOG_LEVEL=info \
    DB_CONNECTION=mysql \
    DB_HOST=127.0.0.1 \
    DB_PORT=3306 \
    DB_DATABASE=portal \
    DB_USERNAME=portal \
    SESSION_DRIVER=database \
    CACHE_STORE=redis \
    QUEUE_CONNECTION=redis \
    REDIS_CLIENT=phpredis \
    REDIS_HOST=127.0.0.1 \
    REDIS_PORT=6379 \
    FILESYSTEM_DISK=local \
    MAIL_MAILER=log \
    AUTH_REGISTRATION_ENABLED=false

VOLUME ["/data"]
EXPOSE 80

HEALTHCHECK --interval=30s --timeout=5s --start-period=180s --retries=5 \
    CMD curl -fsS http://127.0.0.1/up || exit 1

ENTRYPOINT ["entrypoint"]
