# AGENTS Guide: personal-home-portal

## Agent Quickstart
- 5 Kernbefehle (vom Projekt-Root aus):
  - `docker-compose build`
  - `docker-compose up -d`
  - `docker exec -it --user sail personal-home-portal sh -c "sh init.sh"`
  - `docker exec -it --user sail personal-home-portal sh -c "php artisan filament:user --name=Admin --email=admin@example.com --password=secret --panel=admin"`
  - `docker exec -it --user sail personal-home-portal sh -c "php artisan queue:work"`
- 3 typische Agent-Tasks:
  - Neue Admin-Funktion: Model/Enum erweitern, dann Filament Resource (`laravel/app/Filament/Admin/Resources/*`) inkl. `Schema` + `Table` anpassen.
  - Tenant-Sicherheit pruefen: Query-Scope + `canView/canEdit` auf `auth()->id()` in der betroffenen Resource ergaenzen.
  - Fixkosten-Flow aendern: Rechnerlogik in `laravel/app/Services/FixedCostNextBookingDateCalculator.php`, Scheduler in `laravel/routes/console.php`, Konfig in `laravel/config/fixed_costs.php` synchron halten.

## Mini Runbook (Debugging & Logs)
- Laravel-Logs live/kurz pruefen:
  - `tail -n 200 logs/laravel/laravel.log`
- Supervisor-/PHP-Prozesslogs pruefen:
  - `tail -n 200 logs/supervisord/php.out.log`
  - `tail -n 200 logs/supervisord/php.err.log`
  - `tail -n 200 logs/supervisord/supervisord.log`
- Scheduler/Queue manuell anstossen (im Container als `sail`):
  - `docker exec -it --user sail personal-home-portal sh -c "php artisan schedule:run"`
  - `docker exec -it --user sail personal-home-portal sh -c "php artisan queue:work --tries=1"`
- Nuezliche Quellen bei Fixkosten-Problemen:
  - Scheduler-Definition: `laravel/routes/console.php`
  - Rechenlogik: `laravel/app/Services/FixedCostNextBookingDateCalculator.php`
  - Job-Orchestrierung: `laravel/app/Jobs/FixedCostJob.php`, `laravel/app/Jobs/SendUpcomingFixedCostsReminderJob.php`
  - Konfig: `laravel/config/fixed_costs.php`, `laravel/.env`

## Definition of Done (fuer Agent-Changes)
- Scope-Check: Nur betroffene Dateien aendern; bei user-gebundenen Daten Query + `canView/canEdit` auf `auth()->id()` abgesichert.
- Konsistenz-Check: Neue Felder als Model-Konstanten angelegt und in `fillable`/`casts`/Filament-Schema einheitlich verwendet.
- Runtime-Check: Bei Job/Scheduler-Aenderungen sowohl `laravel/routes/console.php` als auch `laravel/config/*.php`/`.env`-Schluessel abgeglichen.
- Smoke-Check lokal: Mindestens relevanten Test oder manuellen Trigger ausfuehren (z. B. `php artisan schedule:run`, `php artisan queue:work --tries=1`).
- Log-Check: Nach dem Run `logs/laravel/laravel.log` und bei Prozessfehlern `logs/supervisord/php.err.log` auf neue Errors pruefen.

## Projektbild in 60 Sekunden
- Stack: Laravel 13 + Filament 5 Admin UI + MySQL + Redis + Mailpit in Docker (`docker-compose.yml`, `laravel/composer.json`).
- Der Haupt-Entry ist das Filament-Adminpanel unter `/admin`; `/` leitet auf `/admin` um (`laravel/routes/web.php`).
- Fachbereiche liegen primar unter `laravel/app/Models/Financial`, `laravel/app/Filament/Admin/Resources`, `laravel/app/Services`.
- Geplante Prozesse: Fixkosten-Update + Reminder laufen als queued Jobs via Scheduler (`laravel/routes/console.php`).

## Architektur- und Datenflussmuster
- Filament-Resource Pattern: `Resource` + `Pages` + `Schemas` + `Tables` (z. B. `laravel/app/Filament/Admin/Resources/Documents/*`).
- Multi-user Isolation wird pro Resource explizit ueber Query-Filter und `canView/canEdit` erzwungen (z. B. `DocumentResource`, `FixedCostResource`, `TagResource`).
- Model-Konstanten werden als Single Source fuer Spalten- und Relationsnamen genutzt (z. B. `FixedCost::user_id`, `Transaction::amount`). Neue Queries/Formfelder sollten dieselben Konstanten nutzen.
- Dokumente laufen ueber eigenes Storage-Disk-Key `Document::STORAGE_DISK` mit Download/View-Controllern (`DocumentDownloadController`, `DocumentViewController`).
- Tagging basiert auf `spatie/laravel-tags`; viele Models nutzen `HasTags`, Form-Komponenten kommen zentral aus `TagResource::getMorphToManySelect(...)`.

## Wichtige Workflows (lokal)
- Erstsetup laeuft ueber Docker + `init.sh` im Container (Composer, Yarn, Key, Migrations, Filament Assets, Build): siehe `README.md` und `laravel/init.sh`.
- Start:
  - `docker-compose build`
  - `docker-compose up -d`
  - dann im Container `sh init.sh` als User `sail`.
- Admin-User wird per Filament-Command erstellt (`php artisan filament:user ... --panel=admin`) laut `README.md`.
- Queue/Scheduler fuer Job-Tests manuell triggern:
  - `php artisan queue:work`
  - `php artisan schedule:run` (`docs/fixed-cost-jobs.md`).

## Job- und Reminder-Domainwissen
- `FixedCostJob` aktualisiert `next_booking_date` fuer alle Fixkosten via `FixedCostNextBookingDateUpdater`.
- `SendUpcomingFixedCostsReminderJob` aktualisiert zuerst Due Dates und sendet dann pro User eine Mail fuer day/week/month Fenster.
- Reminder filtert nur ausgehende Fixkosten (`amount < 0`) und nutzt `FIXED_COST_*` ENV-Werte (`laravel/config/fixed_costs.php`).
- Rechenlogik fuer Intervalle/Endmodus steckt in `FixedCostNextBookingDateCalculator`; zugehoerige Unit-Tests in `laravel/tests/Unit/FixedCostNextBookingDateCalculatorTest.php`.

## Konventionen, die bei Aenderungen wichtig sind
- Neue Felder/Beziehungen zuerst als Model-Konstanten definieren und dann in Fillable/Casts/Filament-Schemas referenzieren.
- Filament-Queries immer auf `auth()->id()` begrenzen, falls Daten user-gebunden sind (siehe bestehende Resources).
- Fuer Datei-Uploads bestehende Disk-Konfigurationen in `laravel/config/filesystems.php` wiederverwenden statt neue Pfade ad-hoc zu bauen.
- Fuer zeitgesteuerte Features Scheduler-Eintrag in `laravel/routes/console.php` + ENV-Konfig in `laravel/config/*.php` kombinieren.

## Externe Integrationen und Betriebsdetails
- Infrastruktur-Abhaengigkeiten: MySQL, Redis, Mailpit (`docker-compose.yml`).
- Standard-Queue ist `database` (`laravel/config/queue.php`), Mail default `log` mit SMTP-Option Richtung Mailpit (`laravel/.env.example`, `laravel/config/mail.php`).
- Laufzeit/Logs in bind mounts: Laravel-Logs unter `logs/laravel`, Supervisor-Logs unter `logs/supervisord`.

