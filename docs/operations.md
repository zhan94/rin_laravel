# Local operations

Use the [README](../README.md) to start either Compose app. Docker Desktop provides PHP 8.5, Node 24 during the frontend build, and an isolated MySQL 8.4 instance for each app. Laravel captures demo mail in memory and uses database-backed sessions. The OOP application runs from the supplied read-only source tree copied into its own container.

Compose allocates resources under the project names `rin-laravel-demo` and `rin-oop-demo`. Start another evaluation with a distinct project name and a different `RIN_LARAVEL_PORT` or `RIN_OOP_PORT`.

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
