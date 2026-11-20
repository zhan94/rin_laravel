# RIN Laravel CRM

RIN gives ski-school staff one place to manage customer records, reserve places in dated activities, track outstanding payments, and coordinate transport and equipment. Staff use role-specific views for operations, reporting, and administration.

![Season dashboard with synthetic demonstration data](docs/screenshots/dashboard.png)

[Customer list](docs/screenshots/customers.png) · [Mobile navigation](docs/screenshots/mobile-navigation.png)

Screenshots show the isolated synthetic demo.

## Run the demo

From the repository root, run the following with Docker running, the Compose plugin available, and internet access for image and dependency downloads.

```sh
docker compose up --build --detach --wait
docker compose exec -T app sh -c 'cat /run/rin/demo-password && printf "\n"'
```

Open [localhost:8081](http://localhost:8081) and sign in as `demo.super_admin` using the generated password. The same local password works for `demo.admin`, `demo.manager`, `demo.group_leader`, and `demo.driver`. Each role has a different access level.

Compose supplies PHP and Node, builds the frontend, and initializes its own MySQL database. A fresh demo includes five core services, 40 pickup routes, 60 synthetic customers, and 22 bookings. Compose generates the app key and passwords. The Laravel seeders carry all required configuration. [Core catalog mapping and native installation](docs/operations.md#core-business-seeding)

Use `docker compose ps` to check health, `docker compose logs --tail=100 app` to see startup logs, and `docker compose down` to shut down. The named volumes retain the database, credentials, uploads, and logs for the next start. [Port configuration and local operations](docs/operations.md)

## Run the relational benchmark

Start the optional benchmark in a separate Compose project on port 8082. It seeds 100,000 customers, 100 services, and 150,000 bookings with their dates, payments, and rental relationships.

```sh
RIN_LARAVEL_PORT=8082 docker compose -f compose.yaml -f compose.benchmark.yaml up --build --detach --wait --wait-timeout 300
docker compose -f compose.yaml -f compose.benchmark.yaml exec -T app sh -c 'cat /run/rin/demo-password && printf "\n"'
docker compose -f compose.yaml -f compose.benchmark.yaml exec -T app php scripts/verify-synthetic-dataset.php
```

Open [localhost:8082](http://localhost:8082) and use the same staff usernames with the password generated for the benchmark. For an untouched standard demo, run `docker compose exec -T app php scripts/verify-synthetic-dataset.php` to check its data and measure the same operations. [Dataset counts, timings, and measurement method](docs/operations.md#relational-dataset-verification)

## Features and architecture

- Staff authentication and account administration, with Super Admin, Admin, Manager, Group Leader, and Driver roles.
- Customer search and pagination, multi-date bookings with capacity checks, and payment applications and corrections.
- Separate service-season entries with daily capacity and availability, pickup locations, cards, equipment, and bonus services.
- Seasonal dashboard, role-scoped reports, CSV and print output, a staff notification API, and issues with private attachments.

The application uses PHP 8.5, Laravel 12, MySQL 8.4, Fortify, Sanctum, Inertia 2, Vue 3, TypeScript, Tailwind CSS 4, and Vite 7. Form Requests validate input, and middleware and policies enforce staff access. Booking and payment services own their transaction boundaries. Row locks protect capacity and balances, and request receipts make supported retries safe. Report repositories keep role-aware queries together. Logs and notifications use Laravel's concrete injection and share their row filters across reads and writes. [Architecture and trade-offs](docs/architecture.md) · [Final architectural review](docs/architectural-review.md)

The interface defaults to German. The language switcher and staff manual support [Deutsch](http://localhost:8081/manual/de), [English](http://localhost:8081/manual/en), and [Español](http://localhost:8081/manual/es). Dates display as DD.MM.YYYY.

## Verify the application

```sh
sh scripts/ci-verify.sh quality
```

This runs the GitHub Actions quality gate locally, including locked dependency audits, static analysis, PHP and frontend tests, and Chromium on an isolated synthetic demo. The separate MySQL race harness verifies booking and payment contention using independent processes. GitHub Actions also scans source and Git history for secrets. [Test and benchmark commands](docs/operations.md)

The relational MySQL benchmark measured a 3.7 ms median for the first customer page, 20.2 ms for page 3,900, and 83.9 ms for a customer search on ARM64. Booking search, availability, dashboard, and report timings are recorded in the operations guide. [Full benchmark results](docs/operations.md#relational-dataset-verification)

Deep customer pages use offset pagination, and full report exports assemble server pages in the browser. Docker installation was verified on ARM64 and with AMD64 emulation on Docker Desktop. Native AMD64 hardware, hosted CI, physical passkeys, and full accessibility conformance remain outside those checks. [Local product acceptance](docs/product-quality-review.md) · [Publication readiness and handover](docs/publication-readiness.md)

## AI-assisted development

Codex assisted with code tracing, patches, regression tests, and documentation. Verification combined focused tests, MySQL race checks, and browser evidence. The owner defined the requirements and approved local acceptance. [Engineering decisions and approval record](docs/ai-development.md)

[Bootstrap and Normalize notices](public/css/styles/) · [Glyphicons Halflings credit](https://www.glyphicons.com/)
