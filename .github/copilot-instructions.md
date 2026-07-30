# Copilot Instructions

## ✅ Stack

- PHP 8.5
- MySQL 8
- Yarn
- Redis (for Caching)
- Laravel 13 (https://laravel.com/docs/13.x/)
- Filament 5 (https://filamentphp.com/docs/5.x/)
- Docker & Docker-Compose (https://docs.docker.com/)

## ✅ Docker Architecture

- Laravel App Container (container_name: personal-home-portal, image: ubuntu:24.04)
    - running the Laravel application
- MySQL Container (container_name: personal-home-portal-db, image: mysql/mysql-server:8.0)
    - running the MySQL database
- Redis Container (container_name: personal-home-portal-cache, image: redis:alpine)
    - running the Redis server for caching
- Mailpit Container (container_name: personal-home-portal-mailpit, image: axllent/mailpit:latest)
    - running the Mailpit server for email testing

## ✅ Additional Copilot Behavior Preferences

- always run commands inside the docker container, for example:

```bash
docker exec -it --user sail personal-home-portal sh -c "php artisan migrate"
```

- use yarn instead of npm for frontend dependencies, for example:

```bash
docker exec -it --user sail personal-home-portal sh -c "yarn build"
```

```bash
docker exec -it --user sail personal-home-portal sh -c "yarn add package-name"
```

- always use maker commands for generating Laravel code, for example:

```bash
docker exec -it --user sail personal-home-portal sh -c "php artisan make:model ModelName --m"
```

```bash
docker exec -it --user sail personal-home-portal sh -c "php artisan make:filament-resource ModelName --simple --no-interaction"
```

```bash
docker exec -it --user sail personal-home-portal sh -c "php artisan make:filament-page PageName --silent"
```

```bash
docker exec -it --user sail personal-home-portal sh -c "php artisan make:filament-widget WidgetName"
```

Tests should be run inside the docker container, for example:

```bash
docker exec -it --user sail personal-home-portal sh -c "cd /var/www/html && php artisan test --filter=TransactionsCSVReaderServiceTest"
```
