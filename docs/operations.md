# Local operations

Use the [README](../README.md) to start the standalone Laravel demo. The Compose build provides PHP 8.5, Node 24 during the frontend build, and isolated MySQL 8.4. Laravel captures demo mail in memory and uses database-backed sessions. The optional OOP demo uses its own Compose file and requires a separate source checkout.

Compose allocates resources under the project names `rin-laravel-demo` and `rin-oop-demo`. The Laravel demo listens on `http://localhost:8081`. If that port is in use, set `RIN_LARAVEL_PORT` before running Compose and use the same port in the browser. Start another evaluation with a distinct project name and a different `RIN_LARAVEL_PORT` or `RIN_OOP_PORT`.

With fresh volumes, an ARM64 Docker Desktop run reached a healthy stack and served the sign-in page in 98.92 seconds using a fresh BuildKit cache and in 13.05 seconds using the warm cache. Both runs used the same engine image store. Timing started at `docker compose up --build --detach --wait` and excluded Docker and BuildKit setup.

## Review the synthetic booking workflow

On a clean Laravel demo, use `demo.manager` with the generated password. Create a customer from Customers, then open Booking, select that customer by full name, and choose Ski school or Mountain walks. Select a date and the matching pickup point. Ski and snowboard bookings also require a skill level. After saving, the Records table exposes payments, editing and cancellation. Editing can change dates, consent and ski options while preserving payments already registered. Cancellation releases the places and keeps the historical record.

The fresh demo seeds 60 customers, 19 dated bookings across the five services, and three bonus bookings. Dates are relative to the first installation. All five services have bookable places on each of the first 14 calendar days. The initializer preserves records across restarts. Use a new Compose project for a fresh date-relative fixture with its own volumes.

Open **Services** to compare the separate season entries for each service. Use the date filter to find periods covering a date, or search by service or season name. **Availability** shows the scheduled days, places used and places remaining, plus the booking list for the selected day. Cancelled bookings remain visible and release their places. The Booking calendar blocks full dates and still allows new eligible days, which the booking transaction schedules on save.

Each season uses its configured daily capacity and shows the duration prices set on the service. Services without a configured tariff display **Price not configured**. The clean demo seeds a day rate for each of its services.

## Core business seeding

The core seeder provisions the catalog and its schedule before the demo inserts synthetic staff and bookings. The benchmark uses the same core and adds volume around it. On a designated development database, `php artisan migrate:fresh --seed` installs the complete demo. Use `php artisan db:seed --class=CoreBusinessSeeder` for the catalog and schedule alone. A subsequent `php artisan db:seed` adds the synthetic demo to that catalog. Provisioning preserves existing edits on repeat runs and refuses to seed over an unrecognized populated dataset.

The original schema was inspected through a read-only MySQL session. The inspected catalog matched the legacy synthetic fixture generator. The seeders embed the reviewed fixture values and can run with the legacy connection unavailable.

| Legacy configuration | Laravel seed mapping |
| --- | --- |
| Five active services | Ski school, Snowboard school, Swimming lessons, Mountain walks, and Private coaching with capacities of 12, 10, 8, 15, and 1. Snowboard maps from source ID 2 to Laravel's winter service ID 11. Its pickup, price, card, and rental relationships follow that identity. |
| 39 service-specific pickup routes | All 39 fixture endpoints and pickup and return times are retained. A synthetic shared shuttle demonstrates the shared location scope. |
| September 1 to May 31 operating defaults and 208 periods with 728 dates | `season_defaults` retains the operating bounds. `service_slots` and `service_dates` receive past, current, and future demo windows for each service, with the source winter and summer classifications. Generated capacities use the service limits. The historical dates and the bulk generator's placeholder capacity of 200 are excluded. |
| Standard service tariffs | The live schema has no `services_price` table. Laravel seeds synthetic EUR tariffs for one, three, and five days, including the duration discounts. |
| Four card types, four rental types, and three bonus activities | Catalog rows retain their service relationships and have synthetic EUR duration prices. Bonus dates move relative to installation day. Current booking rules keep winter skill fields for ski and snowboard and rental and card selection for Ski school. |
| Five staff roles, three skill levels, and operational classifications | The existing Laravel role seeder and authorization rules provide permissions. `season_type` classifies periods and the booking type distinguishes standard services from bonus activities. The source has no separate service category, equipment category, or payment-method catalog. |

Only synthetic fixture routes and tariffs are included. Actual commercial prices, partner and pickup names, and transport timetables require the owner's approval before public distribution. Staff accounts, customers, bookings, and payment balances are generated independently of the legacy database.

## Relational dataset verification

Use the [README commands](../README.md#run-the-relational-benchmark) to start the benchmark. Its Compose override uses a separate project, schema, network, volumes, credentials, and session cookie. Both seeders run inside a transaction and record completion in the database cache. A restart preserves existing records. If the completion marker is missing from a populated database, initialization stops and asks for a separate project.

`service_slots` stores service-specific season ranges. `service_dates` stores the individual bookable days in those ranges. The benchmark applies 40 date blocks across the service catalog and skips blocks that would overlap the five core calendars. Each added booking links to three consecutive days. Staggered starts leave capacity for manual testing. Each booking has a balance mirror in `booking_payments`, including unpaid bookings. Equipment records are catalog items. The seeders avoid assigning the same rental item to overlapping active bookings. Per-unit stock tracking is outside the application schema.

The datasets include all five staff roles, the active service and bonus catalogs, historic and future dates, cancellations, and unpaid, partial, and settled balances. They also include support issues, comments, notifications, and activity logs. Staff create tokens, passkeys, and private attachments through their normal workflows. The separate legacy payment ledger is populated by the legacy import.

Measured on ARM64 with PHP 8.5.13 and MySQL 8.4.13.

| Measure | Standard demo | Relational benchmark |
| --- | ---: | ---: |
| Customers with at least one booking / total | 22 / 60 | 100,000 / 100,000 |
| Locations | 40 | 40 |
| Services | 5 | 100 |
| Service periods | 15 | 3,965 |
| Daily service dates | 280 | 30,385 |
| Bookings | 22 | 150,000 |
| Booking date links | 33 | 449,967 |
| Payment mirrors / bookings with a positive paid amount | 22 / 17 | 150,000 / 119,999 |
| Equipment items / bookings with equipment | 4 / 3 | 3,000 / 363 |
| Seed phase | 0.67 s | 8.23 s |
| MySQL schema directory on disk | 5.75 MiB | 174.18 MiB |

Seed time runs from Laravel's seeding message to the HTTP server start. Database size uses `du -sk` on each schema directory and excludes MySQL-wide files such as redo logs. The small demo's size was recorded after the browser suite added test records.

| Operation | Standard median | Benchmark median |
| --- | ---: | ---: |
| Customer first page | 0.40 ms | 3.73 ms |
| Customer deeper page (2 / 3,900) | 0.31 ms | 20.17 ms |
| Customer email search | 0.26 ms | 83.85 ms |
| Booking search by customer email | 4.75 ms | 523.77 ms |
| Availability for one service date | 0.70 ms | 103.97 ms |
| Dashboard and Inertia payload | 3.41 ms | 688.83 ms |
| 45-day report, first page | 1.71 ms | 682.27 ms |

The verifier checks capacity, date and service relationships, duplicate bookings, balance agreement, and rental collisions. It then measures seven runs of each operation after one warm-up. Times cover Laravel services or controllers and their MySQL queries. HTTP, browser rendering, and concurrent workloads are outside the timings. The 45-day benchmark report matched 22,306 bookings.

## Targeted customer benchmark

```sh
APP_ENV=testing DB_URL= DB_CONNECTION=sqlite DB_DATABASE=:memory: CACHE_STORE=array \
  php scripts/benchmark-customers.php
```

The script refuses other database targets. It creates a new in-memory database, checks pagination results, and prints latency, query timings, and peak PHP memory.

## MySQL concurrency

Use a disposable MySQL server with a new empty `rin_laravel_test` schema and a dedicated user for it. Supply that connection using `DB_HOST`, `DB_PORT`, `DB_USERNAME`, and `DB_PASSWORD` in the environment.

```sh
APP_ENV=testing DB_URL= DB_CONNECTION=mysql DB_DATABASE=rin_laravel_test DB_DEVELOPMENT_DATABASE=rin_laravel_test \
  CACHE_STORE=array MAIL_MAILER=array QUEUE_CONNECTION=sync php scripts/verify-mysql-concurrency.php
```

The harness checks the connected schema before writing. It starts two PHP processes per scenario, holds a target row lock until both are ready, and verifies that the remaining seat or balance can be used only once.

## Application migration status

The prepared `rin_lara` database tracks the original import work. The mutation receipt and report date index migrations were applied to isolated verification databases. Use a dedicated database for demos and destructive verification.

Use `php artisan migrate:status` to inspect the migration ledger for the database selected by your local `.env`. The local application can keep `DB_DATABASE=rin_lara` with `DB_DEVELOPMENT_DATABASE` unset. This allows application migrations while keeping seeding and resets locked.

For a disposable development database, set `DB_DEVELOPMENT_DATABASE` to the exact configured database name or SQLite path in a `local` or `testing` environment. For example, select an empty MySQL database in a separate shell using `DB_DATABASE=rin_dev` and `DB_DEVELOPMENT_DATABASE=rin_dev`. The standard `php artisan migrate` and `php artisan db:seed` commands work there. `php artisan migrate:fresh --seed` is available for an intentional reset and drops the selected database's tables. The `legacy` connection and the schema named in `LEGACY_DB_DATABASE` stay protected. Docker designates its own demo and benchmark schemas. PHPUnit forces an in-memory SQLite database.

## EUR cutover

EUR is the application currency. Booking totals and paid balances are stored to two decimal places. Booking, report, season, and customer responses use EUR for their existing amount fields. Payment corrections and booking edits that set a paid balance require the previously loaded amount in `expected_paid_eur`.

Before upgrading a populated database, pause application writes and queue workers and take a restorable database backup. Apply the two EUR migrations with `php artisan migrate --force` and deploy the matching frontend before resuming traffic. The data migration converts each stored amount at the fixed rate of 1.95583 per euro and rounds to cents. Booking totals and their payment mirrors use the same rule. Use the backup to roll back the cutover. [ECB conversion rate](https://www.ecb.europa.eu/press/pr/date/2025/html/ecb.pr250708~b9676a9fa8.en.html)

Request keys recorded before the cutover still block a duplicate write. A retry whose old payload hash differs from the EUR request returns HTTP 409. Reload the booking to verify a payment submitted before the cutover. Legacy imports convert monetary fields from the original source currency into EUR at import time.

## Native Laravel setup

Use a fresh local checkout, PHP 8.5 with the extensions in `composer.lock`, Composer 2, and Node 24. The setup below creates a dedicated SQLite file and populates the core catalog and synthetic demo.

Run in a fresh shell from the checkout root. Stop if either file guard fails.

```sh
test ! -e .env && test ! -e database/demo.sqlite && cp .env.example .env && touch database/demo.sqlite
```

```sh
export RIN_NATIVE_PORT=8000
export APP_ENV=local APP_DEBUG=false APP_URL="http://localhost:$RIN_NATIVE_PORT"
export DB_URL= DB_CONNECTION=sqlite DB_DATABASE="$PWD/database/demo.sqlite"
export DB_DEVELOPMENT_DATABASE="$DB_DATABASE"
export SESSION_DRIVER=database SESSION_CONNECTION=sqlite CACHE_STORE=database QUEUE_CONNECTION=sync
export SANCTUM_STATEFUL_DOMAINS="localhost:$RIN_NATIVE_PORT"

composer install
npm ci
php artisan key:generate
php artisan migrate:fresh --seed
npm run build
php artisan serve --host=localhost --port="$RIN_NATIVE_PORT"
```

Use `http://localhost:8000` and sign in as `demo.super_admin` with the password printed on the initial seed. Set `RIN_NATIVE_PORT` before exporting the two URL settings if port 8000 is occupied. Keep the shell exports for subsequent starts so the database and session settings continue to point at the same isolated file.

## Native and container verification

After installing dependencies, use the following for the normal isolated PHP and frontend checks:

```sh
test -f .env.testing || cp .env.testing.example .env.testing
vendor/bin/pest
vendor/bin/phpstan analyse --debug --no-progress --memory-limit=512M
vendor/bin/pint --test
find app bootstrap config database lang resources routes scripts tests -name '*.php' -type f -exec php -l {} +
npm run lint
npm run format:check
npm run typecheck
npm run test:unit
npm run build
composer audit --locked
npm audit --audit-level=low
```

The Compose demo keeps MySQL, generated credentials, uploads, and application logs in named volumes. `docker compose restart app` reruns the migrations and checks the seeding marker before starting HTTP. `docker compose down` removes the containers and retains those volumes. Use `docker compose logs --tail=100 app` for application startup messages. MySQL's first initialization records its generated root password in its own container log.

For local browser checks against a running Compose demo, use the following commands after installing Node dependencies. For a custom port, set `RIN_LARAVEL_PORT` in the same shell.

```sh
npx playwright install chromium
PLAYWRIGHT_BASE_URL="http://localhost:${RIN_LARAVEL_PORT:-8081}" \
  RIN_BROWSER_PASSWORD="$(docker compose exec -T app cat /run/rin/demo-password)" npm run test:browser
```

The previously verified OOP coexistence setup uses the separate `compose.oop.yaml` from this repository with `RIN_OOP_SOURCE` pointing at the existing OOP checkout. It uses port 8080, a separate network and database, and a read-only mount. [Coexistence results](final-acceptance.md#docker-installation)
