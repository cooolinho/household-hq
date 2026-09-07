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

# create the first user (always created as a regular user)
docker exec -it --user sail personal-home-portal sh -c "php artisan filament:user --name=Admin --email=admin@example.com --password=secret --panel=app"

# promote it to admin so it can reach /admin (and manage other users' roles from there afterwards)
docker exec -it --user sail personal-home-portal sh -c "php artisan tinker --execute=\"App\Models\User::where('email','admin@example.com')->update(['role'=>'ROLE_ADMIN']);\""

### restart container
docker restart personal-home-portal
```

## 3. Open the Portal
There is one central login for both panels; after signing in, admins are redirected to `/admin`, everyone else to `/app`.

http://localhost/app/login
```
E-Mail:  admin@example.com
Password: secret
```

Registration (`/app/register`) is disabled by default - set `AUTH_REGISTRATION_ENABLED=true` in `laravel/.env` to allow
self-signup (new accounts are always regular, unverified users; verify or promote them from the Admin-Panel's "Benutzer"
resource).

## Queue & Horizon
Queued jobs run through [Laravel Horizon](https://laravel.com/docs/13.x/horizon) on the Redis `default` queue. Admins can
reach the dashboard from a navigation item in `/admin`, or directly at `/admin`'s "Horizon" link (`/horizon`, admins only).

## Deploying an update
```bash
./update dev    # or: ./update prod
```
`./update` pulls, rebuilds/recreates the stack, reconciles the supervisor programs (`docker/supervisord.conf`) with
whatever is currently running, installs PHP/JS dependencies, builds both Filament themes, runs migrations, and finally
restarts Horizon so it picks up the new code (`horizon:terminate`; supervisor's `autorestart` relaunches it immediately).

**Before the first deploy of this change to an existing environment:**
1. `laravel/.env` must have `QUEUE_CONNECTION=redis` - `./update` refuses to run otherwise, since Horizon never reads
   from the old `database` queue driver.
2. If that environment's `QUEUE_CONNECTION` was `database` before, drain any pending jobs first so they aren't stranded
   in the `jobs` table:
   ```bash
   docker exec -it --user sail personal-home-portal sh -c "php artisan queue:work database --stop-when-empty"
   ```
   Only then switch `laravel/.env` to `QUEUE_CONNECTION=redis` and run `./update`.

**Existing users keep their access.** The migration that adds `role`/`is_active` to `users` leaves every existing account
on the column default, `ROLE_USER` - nobody is auto-promoted or locked out. If an existing account needs `ROLE_ADMIN`
(to reach `/admin` and `/horizon`), promote it once after the migration has run:
```bash
docker exec -it --user sail personal-home-portal sh -c "php artisan tinker --execute=\"App\Models\User::where('email','you@example.com')->update(['role'=>'ROLE_ADMIN']);\""
```
From then on, further role changes (including demoting/blocking) can be done from the Admin-Panel's "Benutzer" resource.

## Demo data

Run all idempotent demo seeders:

```bash
docker exec -it --user sail personal-home-portal sh -c "php artisan db:seed"
```

Choose a single seeder or a grouped domain such as `Financial` interactively:

```bash
docker exec -it --user sail personal-home-portal sh -c "php artisan app:seed-demo-data"
```

To replace outdated system transaction categories, run the interactive reset command:

```bash
docker exec -it --user sail personal-home-portal sh -c "php artisan app:reset-transaction-categories"
```

The command can delete only system categories or all categories, including user-created categories, and then runs only
the `TransactionCategorySeeder`. Transactions remain, but their deleted category assignments are removed.

## Docs
- [Project Definition](docs/index.md)
- [TODO's](docs/todos.md)
- [Fixed Cost Jobs](docs/fixed-cost-jobs.md)

## References
- [Filament 5](https://filamentphp.com/docs/5.x/)
- [Laravel 13](https://laravel.com/docs/13.x)
- [Laravel Horizon](https://laravel.com/docs/13.x/horizon)
- [Docker](https://www.docker.com/)
- [Docker-Compose](https://docs.docker.com/compose/)
- [MySQL](https://hub.docker.com/r/mysql/mysql-server)
- [Redis](https://hub.docker.com/_/redis)
- [mailpit](https://hub.docker.com/r/axllent/mailpit)
- [spatie/laravel-tags](https://spatie.be/docs/laravel-tags/v4/introduction)
Version: 1.0.0
