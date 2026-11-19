# RIN Laravel CRM

RIN gives ski-school staff one place to manage customer records, reserve places in dated activities, track outstanding payments, and coordinate transport and equipment. Staff use role-specific views for operations, reporting, and administration.

![Season dashboard with synthetic demonstration data](docs/screenshots/dashboard.png)

[Customer list](docs/screenshots/customers.png) · [Mobile navigation](docs/screenshots/mobile-navigation.png)

Screenshots show the isolated synthetic demo.

## Run the demo

Clone the repository. From its root, with Docker Desktop and Docker Compose running, use:

```sh
docker compose up --build --detach --wait
docker compose exec -T app cat /run/rin/demo-password
```

Open [localhost:8081](http://localhost:8081) and sign in as `demo.super_admin` using the generated password. The same local password works for `demo.admin`, `demo.manager`, `demo.group_leader`, and `demo.driver`. Each role has a different access level.

Compose installs locked dependencies, builds the frontend, migrates its own MySQL database, and seeds 60 synthetic customers and 16 bookings. The checkout supplies the schema and seeders for the standalone Laravel demo. The demo database and generated credentials persist across `docker compose down`. [Native setup, restart, and isolation instructions](docs/operations.md)

## Features and architecture

- Staff authentication and account administration, with Super Admin, Admin, Manager, Group Leader, and Driver roles.
- Customer search and pagination, multi-date bookings with capacity checks, and payment applications and corrections.
- Separate service-season entries with daily capacity and availability, pickup locations, cards, equipment, and bonus services.
- Seasonal dashboard, role-scoped reports, CSV and print output, a staff notification API, and issues with private attachments.

The application uses PHP 8.5, Laravel 12, MySQL 8.4, Fortify, Sanctum, Inertia 2, Vue 3, TypeScript, Tailwind CSS 4, and Vite 7. Form Requests validate input, and middleware and policies enforce staff access. Booking and payment services own their transaction boundaries. Row locks protect capacity and balances, and request receipts make supported retries safe. Report repositories keep role-aware queries together. Logs and notifications use Laravel's concrete injection and share their row filters across reads and writes. [Architecture and trade-offs](docs/architecture.md) · [Final architectural review](docs/architectural-review.md)

The interface and signed-in staff manual support [English](http://localhost:8081/manual/en), [Deutsch](http://localhost:8081/manual/de), and [Español](http://localhost:8081/manual/es). Dates display as DD.MM.YYYY.

## Verify the application

```sh
sh scripts/ci-verify.sh quality
```

This runs the GitHub Actions quality gate locally, including locked dependency audits, static analysis, PHP and frontend tests, and Chromium on an isolated synthetic demo. The separate MySQL race harness verifies booking and payment contention using independent processes. GitHub Actions also scans source and Git history for secrets. [Test and benchmark commands](docs/operations.md)

On ARM64 with 100,000 synthetic customers, MySQL median repository calls took 3.65 ms for the first page, 20.59 ms for page 3,900, and 82.79 ms for a substring match. Each median uses seven samples after a warm-up. The benchmark excludes HTTP, browser rendering, and concurrent workloads. [Performance results and SQLite comparison](docs/product-quality-review.md#performance)

Deep customer pages use offset pagination, and full report exports assemble server pages in the browser. The local demo was verified on ARM64. Hosted CI, x86-64 execution, physical passkeys, and full accessibility conformance remain open. [Local product acceptance](docs/product-quality-review.md) · [Publication readiness and handover](docs/publication-readiness.md)

## AI-assisted development

Codex assisted with code tracing, patches, regression tests, and documentation. Verification combined focused tests, MySQL race checks, and browser evidence. The owner defined the requirements and approved local acceptance. [Engineering decisions and approval record](docs/ai-development.md)

[Bootstrap and Normalize notices](public/css/styles/) · [Glyphicons Halflings credit](https://www.glyphicons.com/)
