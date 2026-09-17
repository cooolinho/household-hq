# Docker Image

Household HQ ships as a single, self-contained Docker image: the app, Horizon, the scheduler, MySQL and
Redis all run inside **one container**. There is no compose file for it and none is needed - a single `docker run`
is enough, optionally seeded with demo data. This complements, but does not replace, the local development setup
described in the [main README](../README.md) (`docker-compose.yml`), which stays a separate, bind-mounted, hot-reloading
environment for working on the code.

## What's inside

A single [supervisord](http://supervisord.org/) process tree, started in this order (and stopped in reverse, so
MySQL always shuts down last):

| Process | Runs as | Purpose |
|---|---|---|
| `mysql` | `mysql` | MySQL 8.0, listening on `127.0.0.1:3306` only |
| `redis` | `redis` | Redis 7, listening on `127.0.0.1:6379` only, AOF persistence |
| `bootstrap` | `root` | One-shot: waits for MySQL/Redis, creates the DB/user, migrates, seeds, then starts everything below |
| `php-fpm` | `www-data` | PHP 8.5-FPM, serves the app via a Unix socket |
| `nginx` | `www-data` | Web server on port 80 |
| `horizon` | `www-data` | Queue worker ([Laravel Horizon](https://laravel.com/docs/13.x/horizon)) |
| `scheduler` | `www-data` | `artisan schedule:work` (fixed-cost jobs, reminders, IMAP import, ...) |

Everything that needs to persist lives under **`/data`**, which is the image's only volume: the MySQL and Redis
data directories, uploaded files (`storage/app`), logs, and a small `app.env` file holding the generated `APP_KEY`
and `DB_PASSWORD` (see [Secrets](#secrets)).

## Tags

Images are published to [GHCR](https://ghcr.io) as `ghcr.io/cooolinho/household-hq`:

- `YYYY.M.D` (e.g. `2026.9.17`) - a specific release, built the day it was cut. A second release on the same day
  gets a `.N` suffix (e.g. `2026.9.17.2`).
- `latest` - always the most recently published release.

Built for `linux/amd64` and `linux/arm64`.

## Quick start (with demo data)

```bash
docker run -d \
  --name household-hq \
  -p 8080:80 \
  -v household-hq-data:/data \
  ghcr.io/cooolinho/household-hq:latest \
  --demo
```

The `--demo` argument (equivalent to `-e DEMO_DATA=true`) seeds demo users, bank accounts, transactions,
insurances, fixed costs and more on first start. Open <http://localhost:8080/app/login>:

| Panel | E-Mail | Password |
|---|---|---|
| App (`/app`, owns the demo data) | `user@example.com` | `secret` |
| Admin (`/admin`, `/horizon`) | `admin@example.com` | `secret` |

The container takes up to a minute or two on its very first start (MySQL initializes its data directory, then
migrations and seeders run) - `docker logs -f household-hq` shows progress, and `docker ps` shows `healthy`
once `/up` responds.

## Production use (no demo data)

```bash
docker run -d \
  --name household-hq \
  -p 8080:80 \
  -v household-hq-data:/data \
  -e APP_URL=https://portal.example.com \
  -e ADMIN_EMAIL=admin@example.com \
  -e ADMIN_PASSWORD='change-me-immediately' \
  --stop-timeout 60 \
  ghcr.io/cooolinho/household-hq:latest
```

Without `--demo`/`DEMO_DATA`, only the global system data is seeded (insurance, fixed-cost and transaction
categories) - no demo users, accounts or transactions. `ADMIN_EMAIL`/`ADMIN_PASSWORD` create exactly one
administrator on first start; re-running the container with the same values later is a no-op (the account is left
untouched, see [`app:create-admin-user`](#useful-commands)). `--stop-timeout 60` gives Horizon time to finish
in-flight jobs and MySQL time to shut down cleanly (see [`docker stop`](https://docs.docker.com/reference/cli/docker/container/stop/)).

Without `ADMIN_EMAIL`/`ADMIN_PASSWORD` and without `--demo`, the container starts with no user at all - create one
afterwards with:

```bash
docker exec -it household-hq php artisan app:create-admin-user
```

## Environment variables

All have sane defaults baked into the image; override with `-e` as needed.

| Variable | Default | Notes |
|---|---|---|
| `APP_NAME` | `Household HQ` | |
| `APP_ENV` | `production` | |
| `APP_DEBUG` | `false` | Never enable on a public deployment |
| `APP_URL` | `http://localhost:8080` | Set to the externally reachable URL |
| `APP_KEY` | *(generated)* | Auto-generated on first start and persisted to `/data/app.env` if not set |
| `APP_TIMEZONE` | `Europe/Berlin` | |
| `APP_LOCALE` / `APP_FALLBACK_LOCALE` | `de` | |
| `DB_DATABASE` / `DB_USERNAME` | `portal` | Created on first start |
| `DB_PASSWORD` | *(generated)* | Auto-generated on first start and persisted to `/data/app.env` if not set |
| `MAIL_MAILER` | `log` | Point at a real SMTP server (`MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_FROM_ADDRESS`, ...) for outgoing mail (reminders, move notifications) |
| `AUTH_REGISTRATION_ENABLED` | `false` | Set `true` to allow self-signup on `/app/register` |
| `DEMO_DATA` | *(unset)* | `true` has the same effect as the `--demo` argument |
| `ADMIN_EMAIL` / `ADMIN_PASSWORD` / `ADMIN_NAME` | *(unset)* | Create one administrator on first start (`ADMIN_NAME` defaults to `Administrator`) |
| `TRUSTED_PROXIES` | *(unset)* | e.g. `*` or a comma-separated IP list - required behind a TLS-terminating reverse proxy, see [Reverse proxy](#reverse-proxy) |
| `SESSION_SECURE_COOKIE` | *(unset)* | Set `true` once the app is only ever served over HTTPS |

`DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `REDIS_HOST`, `REDIS_PORT`, `CACHE_STORE`, `QUEUE_CONNECTION`,
`SESSION_DRIVER` point at the embedded MySQL/Redis and generally don't need to change.

## Secrets

If `APP_KEY` and/or `DB_PASSWORD` are not passed in with `-e`, the entrypoint generates them once on the very first
start and stores them in `/data/app.env` (mode `640`, owned by `root:www-data`). `/var/www/html/.env` inside the
container is a symlink to that file, so `docker exec ... php artisan ...` always sees the same values the running
app uses. A value passed via `-e` always takes precedence and is never written to that file - keep it somewhere
safe yourself in that case (losing `APP_KEY` invalidates existing sessions and any encrypted data).

## Persistence and updates

Everything that must survive a restart or an image update lives under the `/data` volume: MySQL's and Redis's data
directories, `storage/app` (uploaded documents, avatars, inventory images, ...), logs, and the generated secrets.
Updating is pulling the new image and recreating the container with the **same volume**:

```bash
docker pull ghcr.io/cooolinho/household-hq:latest
docker stop --timeout 60 household-hq
docker rm household-hq
docker run -d --name household-hq -p 8080:80 -v household-hq-data:/data \
  ghcr.io/cooolinho/household-hq:latest
```

Migrations (including the [spatie/laravel-settings](https://spatie.be/docs/laravel-settings/v3/introduction) ones)
run automatically on every start, before the app is reachable. Pin an exact version tag (e.g. `2026.9.17`) instead
of `latest` for a deployment where you want to control exactly when updates happen.

## Backup and restore

```bash
# Database
docker exec household-hq sh -c \
  'mysqldump --single-transaction -uroot --socket=/run/mysqld/mysqld.sock "$DB_DATABASE"' > backup.sql

# Everything (database dump + uploaded files, logs, secrets)
docker run --rm -v household-hq-data:/data -v "$PWD":/backup alpine \
  tar czf /backup/household-hq-data.tar.gz -C /data .
```

Restore the volume by extracting the tarball back into a fresh, empty volume before starting the container, or
replay `backup.sql` with `docker exec -i household-hq mysql --socket=/run/mysqld/mysqld.sock -uroot "$DB_DATABASE" < backup.sql`.

## Useful commands

```bash
# Follow logs
docker logs -f household-hq

# Run an artisan command
docker exec -it household-hq php artisan tinker

# Create (or re-check) the administrator account
docker exec -it household-hq php artisan app:create-admin-user

# Seed demo data into an already-running container (idempotent, safe to repeat)
docker exec -it household-hq php artisan db:seed

# Check what supervisord is running
docker exec household-hq supervisorctl status

# Open a shell instead of the normal startup
docker run --rm -it ghcr.io/cooolinho/household-hq:latest bash
```

## Reverse proxy

Behind a TLS-terminating reverse proxy (Traefik, nginx, Caddy, ...), set `TRUSTED_PROXIES` so Laravel trusts the
`X-Forwarded-*` headers - otherwise it generates `http://` asset/redirect URLs behind an HTTPS proxy:

```bash
docker run -d \
  -e APP_URL=https://portal.example.com \
  -e TRUSTED_PROXIES='*' \
  -e SESSION_SECURE_COOKIE=true \
  --label 'traefik.enable=true' \
  --label 'traefik.http.routers.portal.rule=Host(`portal.example.com`)' \
  --label 'traefik.http.services.portal.loadbalancer.server.port=80' \
  ... ghcr.io/cooolinho/household-hq:latest
```

`TRUSTED_PROXIES='*'` trusts any proxy IP; use a comma-separated list of the proxy's actual IPs instead when
possible.

## Building locally

```bash
docker build -t household-hq .
docker buildx build --platform linux/amd64,linux/arm64 -t household-hq .   # multi-arch
```

The build context is the repository root (the [`Dockerfile`](../Dockerfile) copies from `laravel/`), so it must be
run from there. There's no separate build argument required for a plain build; `APP_VERSION` (used only for the
`org.opencontainers.image.version` label and the startup banner) defaults to `dev`.

## Releases

Handled by [`.github/workflows/docker-image.yml`](../.github/workflows/docker-image.yml), which builds, smoke-tests
(boots the image with `--demo` and checks it becomes healthy) and pushes both the versioned tag and `latest`:

- **Manually:** Actions -> *Docker Image* -> *Run workflow*. Leave the version field empty to use today's date
  (UTC) as `YYYY.M.D`, or set it explicitly (e.g. for a second release the same day: `2026.9.17.2`).
- **By pushing a tag:**
  ```bash
  git tag 2026.9.17
  git push origin 2026.9.17
  ```

Either way, a GitHub release is created (or reused, if it already exists) pointing at that commit.

## Troubleshooting

| Symptom | Likely cause |
|---|---|
| Container never becomes `healthy` | Check `docker logs`; the `bootstrap` step logs each stage (waiting for MySQL/Redis, migrating, seeding) and shuts the container down with a clear message on failure |
| "MySQL war nach 60s nicht erreichbar" | Very slow storage backing the `/data` volume, or a corrupted data directory from an interrupted first start - inspect `/data/mysql` inside a debug shell (`docker run --rm -it -v <volume>:/data ... bash`) |
| Assets/redirects use `http://` behind HTTPS | Set `TRUSTED_PROXIES`, see [Reverse proxy](#reverse-proxy) |
| Lost access after removing `-e APP_KEY=...` | An explicitly-passed `APP_KEY` is never written to `/data/app.env`; removing it without persisting it elsewhere makes the container generate a new one, invalidating existing sessions/encrypted data |
| Uploads fail above ~100 MB | `client_max_body_size`/`upload_max_filesize`/`post_max_size` are all set to `100M`; larger needs image changes (`docker/all-in-one/nginx.conf`, `php.ini`) |

## Security notes

- `--demo` seeds well-known credentials (`user@example.com` / `admin@example.com`, both password `secret`) -
  never use it on a deployment reachable from the internet.
- MySQL and Redis only listen on `127.0.0.1` inside the container and are not exposed by the image; there is
  intentionally no `-p` mapping for them in any example above.
- `APP_DEBUG` defaults to `false`; don't override it to `true` on anything but a local, trusted debugging session.
