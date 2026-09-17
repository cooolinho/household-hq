#!/usr/bin/env bash
# All-in-One-Image: PID 1. Bereitet /data vor, generiert fehlende Secrets,
# initialisiert das MySQL-Datadir einmalig und uebergibt dann per exec an
# supervisord, das App, Worker, Scheduler sowie MySQL/Redis startet.
set -euo pipefail

log() {
    printf '[entrypoint] %s\n' "$*"
}

fail() {
    printf '[entrypoint] FEHLER: %s\n' "$*" >&2
    exit 1
}

print_help() {
    cat <<'EOF'
Personal Home Portal - All-in-One Docker Image

Usage:
  docker run [DOCKER OPTIONS] ghcr.io/cooolinho/personal-home-portal[:TAG] [OPTIONS]

Options:
  --demo, --seed-demo   Seed demo users, accounts, insurances and transactions
                         on startup (same as -e DEMO_DATA=true). The seeders
                         are idempotent, so this is safe to pass on every start.
  -h, --help             Show this help and exit.

Without options the container just starts normally (app + workers + scheduler
+ database + cache). A first argument that does not start with "-" replaces
the normal startup entirely and is executed as-is, e.g.:

  docker run ... ghcr.io/cooolinho/personal-home-portal bash
  docker run ... ghcr.io/cooolinho/personal-home-portal php artisan tinker

See docs/docker-image.md in the repository for the full environment variable
reference.
EOF
}

# -------------------------------------------------------------------------- #
# Argumente
# -------------------------------------------------------------------------- #
while [ $# -gt 0 ]; do
    case "$1" in
        --demo | --seed-demo)
            export DEMO_DATA=true
            shift
            ;;
        -h | --help)
            print_help
            exit 0
            ;;
        -*)
            fail "Unbekannte Option: $1 (--help fuer Hilfe)"
            ;;
        *)
            # Nicht-Options-Argument: kompletter Ersatz fuer den normalen
            # Start, z.B. `docker run ... bash` fuer eine Debug-Shell.
            exec "$@"
            ;;
    esac
done

# -------------------------------------------------------------------------- #
# Verzeichnisse unter /data (Volume) und /run anlegen
# -------------------------------------------------------------------------- #
mkdir -p \
    /data/mysql \
    /data/redis \
    /data/storage/app/public \
    /data/storage/app/private \
    /data/logs

mkdir -p /run/mysqld /run/redis /run/php

chown mysql:mysql /data/mysql /run/mysqld
chown redis:redis /data/redis /run/redis
chown www-data:www-data \
    /data/storage \
    /data/storage/app \
    /data/storage/app/public \
    /data/storage/app/private \
    /data/logs \
    /run/php

# -------------------------------------------------------------------------- #
# Secrets: APP_KEY/DB_PASSWORD einmalig erzeugen und in /data persistieren,
# falls nicht per -e gesetzt. Laravels Dotenv ueberschreibt eine bereits
# vorhandene echte ENV-Variable nicht - ein per -e gesetzter Wert hat also
# immer Vorrang und landet nie in dieser Datei.
# -------------------------------------------------------------------------- #
SECRETS_FILE=/data/app.env
touch "$SECRETS_FILE"

if [ -z "${APP_KEY:-}" ] && ! grep -q '^APP_KEY=' "$SECRETS_FILE"; then
    log "Erzeuge APP_KEY (einmalig, gespeichert in ${SECRETS_FILE})"
    printf 'APP_KEY=base64:%s\n' "$(openssl rand -base64 32)" >>"$SECRETS_FILE"
fi

if [ -z "${DB_PASSWORD:-}" ] && ! grep -q '^DB_PASSWORD=' "$SECRETS_FILE"; then
    log "Erzeuge DB_PASSWORD (einmalig, gespeichert in ${SECRETS_FILE})"
    printf 'DB_PASSWORD=%s\n' "$(openssl rand -hex 24)" >>"$SECRETS_FILE"
fi

chown root:www-data "$SECRETS_FILE"
chmod 640 "$SECRETS_FILE"

# In die eigene (und damit an supervisord und dessen Kindprozesse vererbte)
# Umgebung laden. Nur Variablen, die oben tatsaechlich in die Datei
# geschrieben wurden, tauchen hier ueberhaupt auf - ein per -e gesetzter Wert
# wird also nie durch diese Zeile ueberschrieben.
set -a
# shellcheck disable=SC1090,SC1091
. "$SECRETS_FILE"
set +a

# /var/www/html/.env ist ein Symlink hierher, damit auch `docker exec ... php
# artisan ...` (ohne die per -e gesetzten Variablen dieses Prozesses) dieselben
# generierten Secrets sieht.
ln -sf "$SECRETS_FILE" /var/www/html/.env

# -------------------------------------------------------------------------- #
# MySQL-Datadir einmalig initialisieren
# -------------------------------------------------------------------------- #
if [ ! -d /data/mysql/mysql ]; then
    log "Initialisiere MySQL-Datadir in /data/mysql"
    su mysql -s /bin/sh -c "mysqld --initialize-insecure --datadir=/data/mysql" \
        || fail "MySQL-Datadir konnte nicht initialisiert werden."
fi

log "Starte supervisord (PID 1)"
exec /usr/bin/supervisord -n -c /etc/supervisor/conf.d/supervisord.conf
