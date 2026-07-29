# Personal Home Portal

## 1. clone repository
```bash
# main branch
git clone git@github.com:cooolinho/personal-home-portal.git

# specific branch
git clone -b 1.0.0 git@github.com:cooolinho/personal-home-portal.git
```

## 2. Installation
### create .env file for docker-compose.yaml
```bash
cp .env.example .env
```
### set your own LARAVEL_CONTAINER_NAME in .env file (optional)
```bash
LARAVEL_CONTAINER_NAME=personal-home-portal
```

### Run the following commands in terminal
```bash
### build and run docker containers
docker-compose build
docker-compose up -d

## Laravel initialization
docker exec -it personal-home-portal bash -c "chmod -R 777 /var/www/html"
docker exec -it personal-home-portal bash -c "chown -R sail:sail /var/www/html"
docker exec -it --user sail personal-home-portal sh -c "sh init.sh"

# create admin user
docker exec -it --user sail personal-home-portal sh -c "php artisan filament:user --name=Admin --email=admin@example.com --password=secret --panel=admin"

### restart container
docker restart personal-home-portal
```

## 3. Open Admin Dashboard
http://localhost/admin/login
```
E-Mail:  admin@example.com
Password: secret
```

## Docs
- [Project Definition](docs/index.md)
- [TODO's](docs/todos.md)
- [Fixed Cost Jobs](docs/fixed-cost-jobs.md)

## References
- [Filament 5](https://filamentphp.com/docs/5.x/)
- [Laravel 13](https://laravel.com/docs/13.x)
- [Docker](https://www.docker.com/)
- [Docker-Compose](https://docs.docker.com/compose/)
- [MySQL](https://hub.docker.com/r/mysql/mysql-server)
- [Redis](https://hub.docker.com/_/redis)
- [mailpit](https://hub.docker.com/r/axllent/mailpit)
- [spatie/laravel-tags](https://spatie.be/docs/laravel-tags/v4/introduction)
Version: 1.0.0
