# Final acceptance

The [final product quality review](product-quality-review.md) records the most recent local release decision and verification.

**Evaluation date** 19 November 2026. This review builds on the owner's prior acceptance of the local remediation and approval of the repository baseline. The recruitment review added the localhost session fix, distinct administrator labels, Driver report privacy checks, and cross-database report search.

| Decision | Status |
| --- | --- |
| Local technical evaluation | **GO** for the synthetic ARM64 demo and documented verification scope. |
| Repository publication | **NO-GO**. The [publication readiness report](publication-readiness.md) tracks the remaining rights, GitHub access, and approval requirements. |

## Booking acceptance follow-up

The isolated baseline reproduced the reported HTTP 422 in Chrome. Selecting a service between configured periods could disable pickup selection because the support GET attempted to create a period for today. Clicking Add for another gap day also returned `booking_slot_overlap`. The gap calculation ignored Eloquent's date objects, so the proposed period extended into its neighbors.

Support requests now read the configured periods. Booking writes use the existing transaction and service lock to choose a containing period first and bound new periods against both adjacent ranges. Explicitly selected periods cannot expand across a neighbor. Cancellation frees places and customer date availability while retaining booking history. The booking list uses the existing update and cancellation endpoints to edit dates, consent and ski options and preserve payments already registered. The form no longer brings a prior booking's payment into a new one. Chrome also exposed an inline payment failure because Vue supplies numeric input while the parser expected text. The parser now accepts both. Each booking retains the existing single-service model.

The clean synthetic MySQL demo seeded 60 customers, two services, two locations, two cards, one equipment set, six schedule periods, 112 dated slots and 16 bookings with 28 date links. The verified split was six past, six current and four future bookings with six fully paid, six partial and four unpaid. Both services had bookable places on each of the next 14 days, with at least nine for Ski school and thirteen for Mountain hiking on the busiest day. Checks found zero orphans across 27 foreign-key references and zero overlaps, overbookings, duplicate active booking dates, date or catalog mismatches, or payment mirror discrepancies.

The revised demo installed from a clean ARM64 Git clone with fresh MySQL volumes. The fixture and availability checks matched after app restart. Installed Chrome passed five tests, including the end-to-end booking scenario as `demo.manager`. That scenario created a customer, booked Ski school across a configured date and a gap date, registered a payment, added a pass and rental, removed a date, updated consent, checked the dashboard and generated a report, then cancelled the ski booking and created a Mountain hiking booking. The opt-in screenshot test was skipped. The follow-up suite covers 194 PHP tests and 81 frontend tests. The previous timings and test totals below document the prior recruitment review.

The test databases were isolated Compose projects. The original prepared database remains outside this rollout. The missing `crm_mutation_receipts` table previously reported in `rin_lara` requires its separately authorized migration before that installation can accept booking writes.

## Service-season availability follow-up

The Services view shows each persisted service period separately, with its date range, daily capacity, status and the service's configured duration prices. Daily availability and booking validation count active bookings on the same service dates. The date detail retains cancelled bookings in its history. The booking calendar blocks full dates and still lets the transaction schedule an eligible date with no existing slot. A booking edit now normalizes stored dates before matching the existing date links, which prevents duplicate link inserts when SQLite returns a midnight timestamp.

A clean checkout on Docker with a fresh synthetic MySQL database passed seven Google Chrome scenarios. The opt-in screenshot scenario was skipped. Chrome verified the six initial service-season entries, price and status displays, search and sorting, and mobile availability with keyboard focus restoration. A manager created a booking on a configured day and in a calendar gap, moved the gap day to another date, and cancelled the booking. A Group Leader session independently confirmed each change in the daily counts and booking history. The full PHP suite passed 197 tests, the frontend suite passed 81 tests, and PHPStan, Pint, ESLint, Prettier, type checking and the frontend build passed. The disposable MySQL races allowed one successful write each and rejected the competing last-place booking and overpayment.

## Security and functional coverage

The table revisits the [original Laravel audit](initial-audit.md). The [engineering record](ai-development.md) distinguishes owner decisions, agent-authored work, and automated evidence.

| Audit or workflow area | Observed evidence |
| --- | --- |
| Public staff enrollment and elevated account changes | The registration route is closed. Policy and transaction checks protect both the target and the assigned role. Security and user API tests cover direct requests and self-change constraints. |
| Stored credentials, recovery and revocation | Fortify rejected stored-hash input. Feature tests cover password verification, recovery, TOTP, account disablement, role eligibility, session stamps and scoped token revocation. Recovery responses hide account existence. |
| Web/API role parity and CSRF | The role matrix covers page entry and direct API calls. Native Sanctum CSRF tests reject missing or untrusted browser state. The port-specific localhost configuration was verified with a real synthetic login and protected API call. |
| Driver reporting and sensitive data | The earlier code redacted skill and equipment, while the API still sent information the Driver interface hid. The new regression covers guardian email and birth date, notes, finance fields and totals, plus private email search and hidden-field sort. The existing route, permitted transport contact and outstanding-record count stay available. |
| SQL injection and mass assignment | The customer and report search review traced parameter bindings and constrained sorts. Report sort fields use an allowlist, and the new cross-engine name query interpolates a fixed dialect expression and binds the search value. Form Requests, DTOs and targeted Eloquent persistence use validated attributes. The privilege and report regressions exercise the relevant security boundaries. |
| XSS, printing and CSV | Vue text interpolation and print text nodes keep report data in text context. The CSV helper quotes and neutralizes formula-looking values. Chromium downloaded a real CSV and opened the print popup. The Fortify QR SVG is generated by the server. |
| Uploads and audit events | The issue tests cover private attachments, rollback cleanup and authorization. Staff security events use a private redacted channel with correlation IDs. Framing, referrer and MIME protections are applied by middleware. Obsolete Bootstrap JS was removed during remediation. |
| Seed safety and migration integrity | Compose checks the connected isolated schema before migrations and demo initialization. The import retains its explicit mapping and read-only source boundary. The historical report contains the verified business counts and relationship checks. [Legacy import report](legacy-data-migration.md) |
| Concurrent bookings and payments | Independent MySQL processes competed for the last seat and outstanding balance. Both scenarios produced one successful write. The rejected operation was a capacity or remaining-balance conflict, and the payment mirror stayed consistent. Booking and payment tests also cover retry receipts, stale corrections, and preservation of booked dates. |
| Dashboard, CRUD and notifications | The PHP suite covers the customer lifecycle, bookings and payments, equipment, cards, locations, bonus catalogs, users, issues, logs, seasons, slots and notifications. Dashboard tests cover season boundaries and once-per-booking aggregation. The browser suite loads 12 CRM modules, checks the German profile, and exercises mobile menu focus and report output. |
| Dependency and static checks | Both current lockfile audits returned zero advisories. PHPStan remains at level 8. Pint, PHP syntax, ESLint, TypeScript and Prettier checks remain enabled. The expanded test initially exposed an older SQLite version without `CONCAT`; the query now selects an equivalent expression for SQLite. |
| Presentation and performance | Chrome review covered a 1440 × 960 dashboard, 820 × 1024 booking form and list, and 390 × 844 German report. The existing design was retained. The customer list and search benchmark was repeated. Whole-result workloads and the accessibility scope are recorded below. |

## Verification results

| Gate | Result |
| --- | --- |
| Runtime and dependencies | PHP 8.5.3 on macOS ARM64 and PHP 8.5.12 in the ARM64 Docker image. Laravel 12.73.0, Fortify 1.34.1, Sanctum 4.5.0, Vue 3.5.45, TypeScript 5.9.3, Tailwind CSS 4.1.14, Vite 7.3.8. Both lockfiles were retained. Composer validation and platform checks passed with an unspecified root-license warning. |
| PHP | 190 Pest tests and 1,067 assertions. PHPStan level 8, zero errors. Pint passed 412 files. Syntax passed for 414 PHP files. |
| Frontend | 80 Vitest tests across 24 files. ESLint, Prettier, Vue type checking and Vite production compilation passed. The deferred background asset resolved in Chrome. |
| Current dependency audits | Composer and npm both reported zero vulnerabilities on 19 November. npm install emitted deprecation notices for transitive packages. |
| Chrome and Playwright | Four Chromium checks passed in 5.2 seconds in the clean Docker quality run. The opt-in screenshot test was skipped in CI and run separately on the synthetic demo. A separate Chrome run exercised customer creation, validation, details, update, filtering, deletion, notifications, and the empty state on disposable MySQL. It also verified Driver report masking, permitted full-name search, and denial of direct customer API access. |
| MySQL | Both concurrency races passed on disposable MySQL 8.4. Demo integrity checks verified relationship counts, payment mirrors and encoding. |
| Native startup | A clean Git clone installed via Composer and npm under PHP 8.5.3 and Node 24.22.3, migrated and seeded an isolated SQLite file, compiled Vue, and signed in through Chrome. The customers API succeeded and rendered the empty-list state. |
| CI configurations at this review | GitHub Actions passed actionlint 1.7.12. The then-present GitLab configuration passed local YAML and job-structure checks. The [current workflow and hosted status](publication-readiness.md#github-actions) are documented separately. |

## Architecture assessment

The Laravel request lifecycle, service container, Eloquent relationships, typed DTOs, Vue composables, and API Resources fit the installed versions. The review retained shared query and transaction boundaries where there is measurable behavior to protect. One-implementation repository interfaces and forwarding methods add navigation cost, and the largest booking and report files have several distinct responsibilities. The review found no concrete reason to reorganize those paths as a condition of this release. Bookings eager-load their related records, reports use paginated IDs with batched details and set-based totals, and the dashboard uses grouped aggregates. The legacy service discriminator and global location sentinel are deliberate constraints on relational normalization. [Source paths and trade-offs](architecture.md)

## 100,000-customer benchmark

PHP 8.5.3 and SQLite 3.51.2 ran on a MacBook Pro with Apple M4 Pro and 24 GB RAM. The script seeded 100,000 synthetic rows in **0.305 s**, recorded **36.5 MiB** peak allocated PHP memory, and measured repository-call latency using one warm-up and seven measured calls per case. The median call and SQL query counts follow.

| Customer operation | Median | SQL queries |
| --- | ---: | ---: |
| First 25 rows | 0.12 ms | 2 |
| Page 3,900 | 0.48 ms | 2 |
| Unique substring match | 25.54 ms | 2 |
| Substring miss | 13.30 ms | 1 |

Substring search scans candidate text columns. The result measures SQLite repository calls. Full-range reports, MySQL production latency and browser memory at that dataset size were outside the benchmark. [Reproduction command](operations.md#synthetic-benchmark)

## Docker installation

Independent `git clone --no-local` checkouts of the runtime revision `fe38d05` ran on the ARM64 Mac. The disposable Docker daemon reported Engine 29.10.0 and Compose 5.7.1. Timing started before `docker compose up --build --detach --wait` and stopped after the public `/up` response and rendered Inertia sign-in page were confirmed at the canonical browser origin.

| Run | Docker and database state | Elapsed |
| --- | --- | ---: |
| Cold | Empty engine image store, empty build cache, fresh MySQL and app volumes | **66.57 s** |
| Warm | Same engine, retained images and build cache, fresh MySQL and app volumes | **11.27 s** |

The preliminary timing attempts exposed a host redirect and an escaped Inertia marker. Those probes were corrected before recording the times above. Both completed runs were under five minutes. The Docker Desktop installation and x86-64 execution were outside the timing.

The previous verified ARM64 clean-clone run measured 167.35 seconds cold and 11.19 seconds warm on 18 November. That run also started independent Laravel and OOP demos on separate ports, networks and volumes, then signed into both in the same browser to verify cookie isolation. OOP and the procedural legacy application were outside the change scope of the recruitment review.

The isolated MySQL 8.4.13 demo started with five staff, 60 customers, two services, two locations, two cards, eight bookings, 16 date links and eight payment mirrors. The checks covered 27 foreign-key references with validation enabled and found zero orphaned rows, payment disagreements, invalid booking dates or balances, missing customer fields, duplicate email groups, or non-utf8mb4 text columns. A multilingual string containing a four-byte character round-tripped through the connection. Counts and checks matched after restarting the application. The prepared `rin_lara` records were outside the verification workflow. Earlier import counts, orphan checks and migration ledger evidence remain in the [original audit](initial-audit.md#read-only-database-verification) and [migration report](legacy-data-migration.md).

## Git history and confidentiality

- The original `ski2.0` workspace and two source archives contain no Git metadata. The neighboring `ski` checkout has 66 commits, no merges or tags, and a remote at `4146933` on `master`. Its tip lacks the Laravel CRM customer page, booking payment service and legacy importer. A neighboring final-review repository belongs to the OOP application.
- The owner provided [VV-jan/rin_lara](https://gitlab.com/VV-jan/rin_lara). A read-only Git and GitLab project check showed an empty public project with a configured `main` default. After the owner approved a new local history, `42f6172` recorded the verified application baseline. `b3ed179` added the shared CI gates. Documentation and recruitment fixes use separate commits with actual dates and agent authorship.
- The older `ski` candidate's object review covered 821 blobs, 66 commits and 480 trees. It found two non-example contact literals in the older candidate and no credential requiring rotation. The candidate is outside the new Laravel refs. Two local workstation paths in an unpublished Laravel draft were redacted before the baseline was sealed. The private draft remains outside the shareable history.
- Gitleaks scanned the worktree, reachable history and raw blobs and commit objects. The source review also checked local configuration, SQL export signatures, private paths and non-demo contact strings. Dependency lockfiles contain legitimate dependency author contacts. No credential requiring rotation was confirmed in the shareable scope.
- The three replacement screenshots were originally taken on the isolated 60-customer demo. Their database and visual/OCR checks used generated records and reserved example identities. The screenshots are separate from the private interview notes and interactive test artifacts.

## Outstanding decisions and limitations

- Follow the [current GitHub publication checklist](publication-readiness.md#publication-checklist).
- Physical passkeys, full accessibility conformance and x86-64 execution remain outside the verified results. Some customer success notifications and older service-period controls are still English. The tablet booking table uses horizontal scrolling, and the customer list's active badge counts the visible page. Complex forms and role combinations are covered by a combination of feature tests and selected browser checks.
- A Driver can load reports, but the existing service picker depends on the privileged catalog API, so the Driver sees the all-services option. The broader enterprise search and export workloads need separate MySQL and browser benchmarking before changing the UI contracts.
- The prepared database still requires an authorized migration rollout for the two additive receipts and date-index migrations. A staff email-verification rollout remains an owner policy decision for imported identities.

## Synthetic screenshots

[Season dashboard](screenshots/dashboard.png) · [Customer list](screenshots/customers.png) · [Mobile navigation](screenshots/mobile-navigation.png)
