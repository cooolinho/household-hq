# AGENTS.md – personal-home-portal

## Stack

- **PHP 8.5 / Laravel 13** – backend (`laravel/`)
- **Filament 5** – admin UI
- **MySQL 8** – primary database
- **Redis** – caching
- **Mailpit** – dev email testing (dashboard: `http://localhost:8025`)
- **Yarn** – frontend build tool (NOT npm)

## All Commands Run Inside Docker

The container name defaults to `laravel` but is set to `portal` in this project via `.env`:

```bash
docker exec -it --user sail personal-home-portal sh -c "php artisan migrate"
docker exec -it --user sail personal-home-portal sh -c "yarn build"
docker exec -it --user sail personal-home-portal sh -c "yarn add <package>"
```

## Code Generation – Always Use Artisan Makers

```bash
# Model with migration
docker exec -it --user sail personal-home-portal sh -c "php artisan make:model Department --m"

# Filament resource (simple = modal-based, no separate page)
docker exec -it --user sail personal-home-portal sh -c "php artisan make:filament-resource Department --simple"

# Filament page / widget
docker exec -it --user sail personal-home-portal sh -c "php artisan make:filament-page ReportsPage"
docker exec -it --user sail personal-home-portal sh -c "php artisan make:filament-widget StatsOverview"
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

## Two Filament Panels

| Panel       | Path     | Provider             |
|-------------|----------|----------------------|
| Admin-Panel | `/admin` | `AdminPanelProvider` |

Panel providers live in `app/Providers/Filament/`. Resources auto-discovered from `app/Filament/Admin/Resources`, pages
from `app/Filament/Admin/Pages`, widgets from `app/Filament/Admin/Widgets`.

## Key Files

| File                                                    | Purpose                                                                             |
|---------------------------------------------------------|-------------------------------------------------------------------------------------|
| `laravel/init.sh`                                       | First-run setup: composer, yarn, key:generate, migrate, filament:assets, yarn build |
| `laravel/app/Providers/Filament/AdminPanelProvider.php` | Admin-Panel config (theme, colors, middleware)                                      |
| `docs/index.md`                                         | Full domain documentation (roles, billing formula, workflow)                        |
| `docs/todo.md`                                          | Phased implementation plan with exact resource/page names                           |
| `docker-compose.yml`                                    | Service definitions (laravel, mysql, redis, mailpit)                                |

## First-Run Setup

```bash
docker-compose build && docker-compose up -d
docker exec -it personal-home-portal bash -c "chmod -R 777 /var/www/html"
docker exec -it --user sail personal-home-portal sh -c "sh init.sh"
docker exec -it --user sail personal-home-portal sh -c "php artisan filament:user --name=Admin --email=admin@example.com --password=secret --panel=admin"
docker restart portal
# Admin UI: http://localhost:8080/admin/login
```
