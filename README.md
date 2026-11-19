# RIN Laravel CRM

RIN gives ski-school staff one place to manage customer records, reserve places in dated activities, track outstanding payments, and coordinate transport and equipment. Staff use role-specific views for operations, reporting, and administration.

![Season dashboard with synthetic demonstration data](docs/screenshots/dashboard.png)

[Customer list](docs/screenshots/customers.png) · [Mobile navigation](docs/screenshots/mobile-navigation.png)

The screenshots use the isolated synthetic demo.

## Run the demo

From a checkout with Docker Desktop and Docker Compose installed, run:

```sh
docker compose up --build --detach --wait
docker compose exec -T app cat /run/rin/demo-password
```

Open [localhost:8081](http://localhost:8081) and sign in as `demo.super_admin` using the generated password. The same local password works for `demo.admin`, `demo.manager`, `demo.group_leader`, and `demo.driver`. Each role has a different access level.

Compose installs the locked dependencies, compiles the frontend, migrates an isolated MySQL database, and seeds 60 synthetic customers and 16 past, current, and future bookings. Ski school and Mountain hiking have bookable dates on a fresh install. The database and demo credentials persist across `docker compose down`. [Native setup, restart, and isolation instructions](docs/operations.md)

## Features and architecture

- Staff authentication and account administration, with Super Admin, Admin, Manager, Group Leader, and Driver roles.
- Customer search and pagination, multi-date bookings with capacity checks, and payment applications and corrections.
- Separate service-season entries with daily capacity and availability, pickup locations, cards, equipment, and bonus services.
- Seasonal dashboard, role-scoped reports, CSV and print output, staff notifications, and issues with private attachments.

The application uses PHP 8.5, Laravel 12, MySQL 8.4, Fortify, Sanctum, Inertia 2, Vue 3, TypeScript, Tailwind CSS 4, and Vite 7. English, German, and Spanish are available in the staff interface. Form Requests validate input, middleware and policies enforce access, and Eloquent handles persistence. Booking and payment services coordinate database locks and retry handling. Vue pages use typed API clients. [Architecture and trade-offs](docs/architecture.md)

The signed-in staff manual covers role access, bookings, payments, service availability, and reports. Open it from the account controls or choose [English](http://localhost:8081/manual/en), [Deutsch](http://localhost:8081/manual/de), or [Español](http://localhost:8081/manual/es). Dates display as DD.MM.YYYY across interface languages.

## Verify the application

```sh
sh scripts/ci-verify.sh quality
```

This runs the container-based quality gate used by GitHub Actions, including locked dependency audits, PHP and frontend checks, and Chromium against an isolated synthetic demo. GitHub Actions also checks source and Git history for secrets. [Native test, MySQL concurrency, and benchmark commands](docs/operations.md)

The latest 100,000-customer MySQL benchmark measured median repository calls of 3.65 ms for the first page, 20.59 ms for a deep page, and 82.79 ms for a substring match on ARM64. [Performance results, SQLite comparison and measurement boundaries](docs/product-quality-review.md#performance)

The Driver report API restricts confidential fields, and regression tests exercise staff role boundaries, account revocation, and CSRF. Reports that export every matching row assemble paginated results in the browser. The local demo was verified on ARM64. Hosted CI, x86-64 execution, physical passkeys, and full accessibility conformance remain unverified. [Local product acceptance](docs/product-quality-review.md) · [Publication readiness and rights inventory](docs/publication-readiness.md)

## Engineering records

- [Initial Laravel security and architecture audit](docs/initial-audit.md)
- [AI-assisted development, verification, and human approvals](docs/ai-development.md)
- [Legacy migration mapping and integrity results](docs/legacy-data-migration.md)
- [Bootstrap and Normalize license notices](public/css/styles/)
- [Glyphicons Halflings from Bootstrap](https://getbootstrap.com/docs/3.3/components/#glyphicons) · [Glyphicons](https://www.glyphicons.com/)
