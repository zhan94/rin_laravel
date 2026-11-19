# Local operations

Use the [README](../README.md) to start either Compose app. Docker Desktop provides PHP 8.5, Node 24 during the frontend build, and an isolated MySQL 8.4 instance for each app. Laravel captures demo mail in memory and uses database-backed sessions. The OOP application runs from the supplied read-only source tree copied into its own container.

Compose allocates resources under the project names `rin-laravel-demo` and `rin-oop-demo`. Start another evaluation with a distinct project name and a different `RIN_LARAVEL_PORT` or `RIN_OOP_PORT`.

## Review the synthetic booking workflow

On a clean Laravel demo, use `demo.manager` with the generated password. Create a customer from Customers, then open Booking, select that customer by full name, and choose Ski school or Mountain hiking. Select a date and the matching pickup point. Ski bookings also require a skill level. After saving, the Records table exposes payments, editing and cancellation. Editing can change dates, consent and ski options while preserving payments already registered. Cancellation releases the places and keeps the historical record.

The fresh demo seeds 60 customers and 16 bookings across three schedule blocks per service. Dates are relative to the first installation. The first 14 calendar days contain bookable places for both services. The initializer preserves records across restarts. Use a new Compose project for a fresh date-relative fixture with its own volumes.

Open **Services** to compare the separate season entries for each service. Use the date filter to find periods covering a date, or search by service or season name. **Availability** shows the scheduled days, places used and places remaining, plus the booking list for the selected day. Cancelled bookings remain visible and release their places. The Booking calendar blocks full dates and still allows new eligible days, which the booking transaction schedules on save.

Each season uses its configured daily capacity and shows the duration prices set on the service. Services without a configured tariff display **Price not configured**. The clean demo seeds a day rate for each of its services.

## Synthetic benchmark

```sh
APP_ENV=testing DB_URL= DB_CONNECTION=sqlite DB_DATABASE=:memory: CACHE_STORE=array \
  php scripts/benchmark-customers.php
```

The script refuses other database targets. It creates a new in-memory database, checks pagination results, and prints latency, query timings, and peak PHP memory.

## MySQL concurrency

Use a disposable MySQL server with a new empty `rin_laravel_test` schema and a dedicated user for it. Supply that connection using `DB_HOST`, `DB_PORT`, `DB_USERNAME`, and `DB_PASSWORD` in the environment.

```sh
APP_ENV=testing DB_URL= DB_CONNECTION=mysql DB_DATABASE=rin_laravel_test \
  CACHE_STORE=array MAIL_MAILER=array QUEUE_CONNECTION=sync php scripts/verify-mysql-concurrency.php
```

The harness checks the connected schema before writing. It starts two PHP processes per scenario, holds a target row lock until both are ready, and verifies that the remaining seat or balance can be used only once.

## Application migration status

The prepared `rin_lara` database tracks the original import work. The new application requires the two additive migrations for mutation receipts and date indexing. They were applied to isolated verification databases. Use a dedicated database for demos and destructive verification.

## Native Laravel setup

Use a fresh local checkout, PHP 8.5 with the extensions in `composer.lock`, Composer 2, and Node 24. The lightweight setup below creates a dedicated SQLite file, one synthetic staff account, and empty business tables. Use Compose for the populated MySQL demonstration.

Run in a fresh shell from the checkout root. Stop if either file guard fails.

```sh
test ! -e .env && test ! -e database/demo.sqlite && cp .env.example .env && touch database/demo.sqlite
```

```sh
export RIN_NATIVE_PORT=8000
export APP_ENV=local APP_DEBUG=false APP_URL="http://localhost:$RIN_NATIVE_PORT"
export DB_URL= DB_CONNECTION=sqlite DB_DATABASE="$PWD/database/demo.sqlite"
export SESSION_DRIVER=database SESSION_CONNECTION=sqlite CACHE_STORE=database QUEUE_CONNECTION=sync
export SANCTUM_STATEFUL_DOMAINS="localhost:$RIN_NATIVE_PORT"

composer install
npm ci
php artisan key:generate
php artisan migrate --seed
npm run build
php artisan tinker --execute='
    $password = Illuminate\Support\Str::password(24);
    $role = App\Models\Role::query()->where("role_key", "super_admin")->firstOrFail();
    App\Models\User::factory()->create([
        "username" => "demo.native_admin",
        "email" => "native-admin@example.test",
        "role_id" => $role->id,
        "is_active" => true,
        "password" => $password,
    ]);
    echo $password.PHP_EOL;
'
php artisan serve --host=localhost --port="$RIN_NATIVE_PORT"
```

Use `http://localhost:8000` and sign in as `demo.native_admin` with the locally printed password. Set `RIN_NATIVE_PORT` before exporting the two URL settings if port 8000 is occupied. Keep the shell exports for subsequent starts so the database and session settings continue to point at the same isolated file.

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

The Compose demo keeps MySQL, generated credentials, uploads, and logs in named volumes. `docker compose restart app` reruns the migrations and checks the seeding marker before starting HTTP. For a new evaluation use a new project name and port. For local browser checks against a running Compose demo use:

```sh
npx playwright install chromium
RIN_BROWSER_PASSWORD="$(docker compose exec -T app cat /run/rin/demo-password)" npm run test:browser
```

The previously verified OOP coexistence setup uses the separate `compose.oop.yaml` from this repository with `RIN_OOP_SOURCE` pointing at the existing OOP checkout. It uses port 8080, a separate network and database, and a read-only mount. [Coexistence results](final-acceptance.md#docker-installation)
