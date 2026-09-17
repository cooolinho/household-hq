#!/usr/bin/env bash
# Von supervisord als [program:bootstrap] gestartet (priority=30, nach MySQL
# und Redis, vor php-fpm/nginx/horizon/scheduler - siehe supervisord.conf).
# Einmal-Lauf: wartet auf MySQL/Redis, legt DB/Benutzer an, migriert, seedet
# System-Stammdaten (immer beim ersten Start) und Demo-Daten (nur mit
# DEMO_DATA=true), und schaltet danach App, Horizon und Scheduler frei.
set -euo pipefail

log() {
    printf '[bootstrap] %s\n' "$*"
}

# Bei jedem Fehler den Container sichtbar stoppen, statt mit halb
# hochgefahrener DB/Cache und ohne laufende App unbemerkt haengen zu bleiben.
# fail() deckt gezielte Abbrueche ab (z.B. Timeout in wait_for), der
# ERR-Trap alles andere (z.B. ein fehlschlagendes "artisan migrate"); beide
# rufen denselben Shutdown auf, ein doppelter Aufruf davon ist harmlos.
shutdown_supervisor() {
    supervisorctl shutdown >/dev/null 2>&1 || true
}

fail() {
    printf '[bootstrap] FEHLER: %s\n' "$*" >&2
    shutdown_supervisor
    exit 1
}

on_error() {
    local exit_code=$?
    printf '[bootstrap] Start abgebrochen (exit %s), siehe Log oben.\n' "$exit_code" >&2
    shutdown_supervisor
    exit "$exit_code"
}
trap on_error ERR

APP_DIR=/var/www/html
INIT_MARKER=/data/.initialized
MYSQL_SOCKET=/run/mysqld/mysqld.sock

# Fuehrt einen Befehl als www-data aus. su selbst interpretiert jedes weitere
# Argument, das wie eine Option aussieht (z.B. "--force"), als eigene Option
# statt es an die Zielshell weiterzugeben - deshalb hier alles ueber %q zu
# EINEM einzigen -c-String zusammensetzen, statt Argumente einzeln an su
# durchzureichen. %q/@Q escaped jedes Argument shell-sicher, auch bei
# Leerzeichen, Anfuehrungszeichen oder Backslashes (Klassennamen, Passwoerter).
run_as_app() {
    local cmd
    printf -v cmd '%q ' "$@"
    su -s /bin/bash www-data -c "cd ${APP_DIR@Q} && ${cmd}"
}

wait_for() {
    local description="$1"
    shift

    local attempt
    for attempt in $(seq 1 60); do
        if "$@" >/dev/null 2>&1; then
            return 0
        fi
        sleep 1
    done

    fail "${description} war nach 60s nicht erreichbar."
}

: "${DB_DATABASE:?DB_DATABASE ist nicht gesetzt}"
: "${DB_USERNAME:?DB_USERNAME ist nicht gesetzt}"
: "${DB_PASSWORD:?DB_PASSWORD ist nicht gesetzt}"

log "Warte auf MySQL..."
wait_for "MySQL" mysqladmin --socket="$MYSQL_SOCKET" -uroot ping

log "Warte auf Redis..."
wait_for "Redis" redis-cli -h 127.0.0.1 ping

# -------------------------------------------------------------------------- #
# Datenbank und Anwendungsbenutzer anlegen (idempotent)
# -------------------------------------------------------------------------- #
log "Lege Datenbank/Benutzer an (falls noch nicht vorhanden)"
mysql --socket="$MYSQL_SOCKET" -uroot <<SQL
CREATE DATABASE IF NOT EXISTS \`${DB_DATABASE}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '${DB_USERNAME}'@'127.0.0.1' IDENTIFIED BY '${DB_PASSWORD}';
ALTER USER '${DB_USERNAME}'@'127.0.0.1' IDENTIFIED BY '${DB_PASSWORD}';
GRANT ALL PRIVILEGES ON \`${DB_DATABASE}\`.* TO '${DB_USERNAME}'@'127.0.0.1';
FLUSH PRIVILEGES;
SQL

# -------------------------------------------------------------------------- #
# Laravel: Caches leeren, migrieren (inkl. der spatie/laravel-settings-
# Migrationen unter database/settings)
# -------------------------------------------------------------------------- #
log "artisan optimize:clear"
run_as_app php artisan optimize:clear

log "artisan migrate --force"
run_as_app php artisan migrate --force

# -------------------------------------------------------------------------- #
# System-Stammdaten nur beim allerersten Start seeden - ohne sie gaebe es in
# einer frischen Installation ohne --demo keine einzige Kategorie zur Auswahl.
# Alle drei Seeder sind global (kein user_id) und unabhaengig voneinander.
# -------------------------------------------------------------------------- #
if [ ! -f "$INIT_MARKER" ]; then
    log "Erster Start: seede System-Stammkategorien"
    run_as_app php artisan db:seed --force --class="Database\Seeders\Financial\InsuranceCategorySeeder"
    run_as_app php artisan db:seed --force --class="Database\Seeders\Financial\FixedCostCategorySeeder"
    run_as_app php artisan db:seed --force --class="Database\Seeders\Financial\TransactionCategorySeeder"
    touch "$INIT_MARKER"
fi

# -------------------------------------------------------------------------- #
# Administrator-Account (optional). Legt nichts an/aendert nichts, wenn die
# E-Mail bereits existiert - siehe CreateAdminUserCommand.
# -------------------------------------------------------------------------- #
if [ -n "${ADMIN_EMAIL:-}" ] && [ -n "${ADMIN_PASSWORD:-}" ]; then
    log "Lege Administrator-Account an (falls noch nicht vorhanden)"
    run_as_app php artisan app:create-admin-user --no-interaction \
        --name="${ADMIN_NAME:-Administrator}" \
        --email="${ADMIN_EMAIL}" \
        --password="${ADMIN_PASSWORD}"
elif [ -n "${ADMIN_EMAIL:-}" ] || [ -n "${ADMIN_PASSWORD:-}" ]; then
    log "ADMIN_EMAIL/ADMIN_PASSWORD nur teilweise gesetzt - ueberspringe Admin-Anlage."
fi

# -------------------------------------------------------------------------- #
# Demo-Daten (optional). Die Seeder sind idempotent (siehe
# DatabaseSeederIdempotencyTest), ein wiederholter Lauf ist unkritisch.
# -------------------------------------------------------------------------- #
if [ "${DEMO_DATA:-false}" = "true" ]; then
    log "DEMO_DATA=true: seede Demo-Daten"
    run_as_app php artisan db:seed --force
fi

log "artisan optimize"
run_as_app php artisan optimize

# -------------------------------------------------------------------------- #
# App, Horizon und Scheduler freigeben
# -------------------------------------------------------------------------- #
log "Starte php-fpm, nginx, horizon, scheduler"
supervisorctl start php-fpm nginx horizon scheduler >/dev/null

APP_URL_DISPLAY="${APP_URL:-http://localhost}"
log "Fertig - Household HQ ist erreichbar unter ${APP_URL_DISPLAY}"

if [ "${DEMO_DATA:-false}" = "true" ]; then
    log "Demo-Login (App-Panel):   user@example.com / secret"
    log "Demo-Login (Admin-Panel): admin@example.com / secret"
elif [ -n "${ADMIN_EMAIL:-}" ]; then
    log "Admin-Login: ${ADMIN_EMAIL}"
else
    log "Kein Administrator angelegt. Nachtraeglich anlegen mit:"
    log "  docker exec -it <container> php artisan app:create-admin-user"
fi
