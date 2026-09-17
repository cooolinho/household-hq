# AGENTS.md – household-hq

## Stack

- **PHP 8.5 / Laravel 13** – backend (`laravel/`)
- **Filament 5** – two panels, App (`/app`) and Admin (`/admin`)
- **MySQL 8** – primary database
- **Redis** – caching and queue (via Laravel Horizon)
- **Mailpit** – dev email testing (dashboard: `http://localhost:8025`)
- **Yarn** – frontend build tool (NOT npm)

## All Commands Run Inside Docker

The app container is `household-hq` (see `docker-compose.yml`):

```bash
docker exec -it --user sail household-hq sh -c "php artisan migrate"
docker exec -it --user sail household-hq sh -c "php artisan test"
docker exec -it --user sail household-hq sh -c "yarn build"
docker exec -it --user sail household-hq sh -c "yarn add <package>"
```

## Code Generation – Always Use Artisan Makers

```bash
# Model with migration
docker exec -it --user sail household-hq sh -c "php artisan make:model Department --m"

# Filament resource (simple = modal-based, no separate page)
docker exec -it --user sail household-hq sh -c "php artisan make:filament-resource Department --simple"

# Filament page / widget
docker exec -it --user sail household-hq sh -c "php artisan make:filament-page ReportsPage"
docker exec -it --user sail household-hq sh -c "php artisan make:filament-widget StatsOverview"
```

## Model Convention

All column names are declared as class constants on the model:

```php
// app/Models/User.php
class User extends Authenticatable {
    const id = 'id';
    const name = 'name';
    const email = 'email';
    // ...
}
```

Reference constants instead of raw strings in queries and Filament forms.

## Filament Panels

| Panel     | Path     | Provider            | Who                                                    |
|-----------|----------|----------------------|--------------------------------------------------------|
| App-Panel | `/app`   | `AppPanelProvider`   | every active user (`ROLE_USER`, `ROLE_ADMIN`) - default panel, holds the central login |
| Admin-Panel | `/admin` | `AdminPanelProvider` | `ROLE_ADMIN` only (`User::canAccessPanel()`)          |

Panel providers live in `app/Providers/Filament/`. App-Panel resources/pages/widgets/clusters are auto-discovered from
`app/Filament/App/{Resources,Pages,Widgets,Clusters}`; Admin-Panel ones from `app/Filament/Admin/{Resources,Pages,Widgets,Clusters}`
(currently just the `UserResource` for user management).

There is no separate login route per panel: `/login` and unauthenticated `/admin`/`/horizon` requests all redirect to
`/app/login`. After login, `App\Http\Responses\LoginResponse` sends `ROLE_ADMIN` to `/admin` and everyone else to `/app`
(see `App\Support\PanelRouter`).

Registration (`/app/register`) only ever exists on the App-Panel and is toggled via `AUTH_REGISTRATION_ENABLED`
(`config('auth.registration.enabled')`, default `false`). New self-registered users are unverified `ROLE_USER`s.

### Roles

`App\Enums\Role` (`ROLE_USER` / `ROLE_ADMIN`) backs the `users.role` column; `users.is_active` gates login and panel
access independently of role (`User::canAccessPanel()`). Admins manage both, plus manual verification, via the
Admin-Panel's `UserResource`.

### Themes

Each panel has its own 7-1 SASS tree under `resources/scss/filament/{app,admin}/`, sharing common variables/mixins/the
Filament vendor import via `resources/scss/filament/_shared/`. See the `abstracts/_variables.scss` `@forward` pattern in
either tree before adding a third panel.

## Key Files

| File                                                    | Purpose                                                                             |
|---------------------------------------------------------|-------------------------------------------------------------------------------------|
| `laravel/init.sh`                                       | First-run setup: composer, yarn, key:generate, migrate, filament:assets, yarn build |
| `laravel/app/Providers/Filament/AppPanelProvider.php`   | App-Panel config (theme, colors, login/registration/email verification, middleware) |
| `laravel/app/Providers/Filament/AdminPanelProvider.php` | Admin-Panel config (theme, colors, middleware, Horizon nav item)                    |
| `laravel/app/Providers/HorizonServiceProvider.php`      | Horizon's `viewHorizon` gate (active admins only, no `local`-env bypass)            |
| `laravel/app/Support/PanelRouter.php`                   | Maps a user to their panel id/URL (login redirect, `/` redirect)                    |
| `update`                                                | `./update dev\|prod` deploy script: pull, rebuild, reconcile supervisor programs, composer/yarn, migrate, restart Horizon |
| `supervisor.sh`                                         | Interactive control for Supervisor processes (`php`, `horizon`, `scheduler`)        |
| `docker/supervisord.conf`                               | Runtime process definitions for app server, Horizon, and scheduler (dev image)      |
| `docs/index.md`                                         | Full domain documentation (roles, billing formula, workflow)                        |
| `docs/todos.md`                                         | Phased implementation plan with exact resource/page names                           |
| `docker-compose.yml`                                    | Service definitions (laravel, mysql, redis, mailpit)                                |
| `docker-compose.prod.yml`                               | Production setup with Traefik labels and persistent DB/Redis volumes                |
| `Dockerfile`                                            | All-in-one production image (app + Horizon + scheduler + MySQL + Redis, one container), published to `ghcr.io/cooolinho/household-hq` |
| `docker/all-in-one/`                                    | Config backing the all-in-one image: `entrypoint.sh`/`bootstrap.sh`, supervisord/nginx/php-fpm/mysql/redis configs |
| `.github/workflows/docker-image.yml`                    | CI: builds, smoke-tests and publishes the all-in-one image (tag push or manual dispatch) |
| `docs/docker-image.md`                                  | All-in-one image reference: usage, environment variables, backup/updates            |
| `laravel/app/Console/Commands/CreateAdminUserCommand.php` | `php artisan app:create-admin-user` - creates one admin, leaves an existing account with that e-mail untouched |

## First-Run Setup

```bash
docker-compose build && docker-compose up -d
docker exec -it household-hq bash -c "chmod -R 777 /var/www/html"
docker exec -it household-hq bash -c "chown -R sail:sail /var/www/html"
docker exec -it --user sail household-hq sh -c "sh init.sh"
docker exec -it --user sail household-hq sh -c "php artisan filament:user --name=Admin --email=admin@example.com --password=secret --panel=app"
# filament:user always creates a ROLE_USER; promote it to ROLE_ADMIN to reach /admin:
docker exec -it --user sail household-hq sh -c "php artisan tinker --execute=\"App\\Models\\User::where('email','admin@example.com')->update(['role'=>'ROLE_ADMIN']);\""
docker restart household-hq
# Login (all roles): http://localhost/app/login - admins are redirected to /admin after signing in
```
