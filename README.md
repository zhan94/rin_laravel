# RIN Laravel CRM

RIN coordinates ski school and activity bookings, customer records, transport locations, equipment, cards, payments, and reports. The application uses PHP 8.5, Laravel 12, MySQL 8.4, Vue 3, and Inertia. The staff interface supports English, German, and Spanish.

This checkout is prepared for private engineering evaluation using generated demonstration data. Application redistribution and licensing require confirmation from the rights holder. Third-party dependencies retain their own licenses.

## Start the Laravel demo

Install Docker Desktop with Docker Compose. From the project directory, run:

```sh
docker compose up --build --detach --wait
docker compose exec -T app cat /run/rin/demo-password
```

Open [the local Laravel demo](http://localhost:8081) and sign in as `demo.super_admin`. Other demonstration accounts are `demo.admin`, `demo.manager`, `demo.group_leader`, and `demo.driver`. The first startup generates the shared demo-only password and stores it in the private Compose volume.

Compose installs the locked dependencies, builds the frontend, migrates a private MySQL database, and seeds 60 synthetic customers and their sample bookings. It binds HTTP to loopback. The demo database and application keys persist across `docker compose down`. To run a clean isolated installation, use a new Compose project name and a free `RIN_LARAVEL_PORT`. Use `localhost` consistently for browser sessions and passkeys.

To start the framework-free OOP application alongside Laravel, use its existing source checkout with the separate Compose file:

```sh
RIN_OOP_SOURCE=/absolute/path/to/rin docker compose -f compose.oop.yaml up --build --detach --wait
docker compose -f compose.oop.yaml exec -T app cat /run/rin/demo-password
```

Open [the local OOP demo](http://localhost:8080) and sign in as `demo.superadmin`. Compose mounts the OOP checkout read-only and gives each app its own MySQL volume, Docker network, credentials, and HTTP port. Both Compose apps support Apple Silicon and keep database ports internal.

![Season dashboard with synthetic demonstration data](docs/screenshots/dashboard.png)

## Run checks locally

Use PHP 8.5 with the extensions in `composer.lock`, Composer 2, and Node 24. Tests use isolated SQLite memory databases.

```sh
composer install
npm ci
npm run build
test -f .env.testing || cp .env.testing.example .env.testing
vendor/bin/pest
vendor/bin/phpstan analyse --debug --no-progress --memory-limit=512M
vendor/bin/pint --test
composer audit --locked
npm audit
npm run lint
npm run format:check
npm run typecheck
npm run test:unit
```

Run the Playwright suite against a running synthetic Compose demo:

```sh
npx playwright install chromium
RIN_BROWSER_PASSWORD="$(docker compose exec -T app cat /run/rin/demo-password)" npm run test:browser
```

The MySQL race harness requires a new database named `rin_laravel_test` on a disposable MySQL instance. See [local operations](docs/operations.md) for the exact setup. GitLab CI and GitHub Actions run the same quality checks, dependency audits, and browser suite. Both perform a redacted worktree and reachable-history secret scan from a full checkout. GitLab quality jobs require a trusted, privileged Docker-in-Docker runner with a shared `/certs/client` volume. The local equivalent is `sh scripts/ci-verify.sh quality`. [GitLab runner configuration](https://docs.gitlab.com/ci/docker/docker_in_docker/)

## Migration and verification

- [Legacy database mapping and original migration results](docs/legacy-data-migration.md)
- [Architecture and business invariants](docs/architecture.md)
- [Local operations and concurrency verification](docs/operations.md)
- [AI-assisted development and human decisions](docs/ai-development.md)
- [Final acceptance and sharing readiness](docs/final-acceptance.md)
