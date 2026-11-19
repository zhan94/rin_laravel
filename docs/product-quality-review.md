# Final product acceptance

**19 November 2026** · Laravel release candidate · Apple Silicon

| Decision | Result |
| --- | --- |
| Local acceptance | **GO** for the isolated synthetic ARM64 demo and the documented workflows. |
| Publication | **NO-GO**. See the [current publication readiness report](publication-readiness.md) for rights, GitHub access and release approval. |

The review reused the earlier authentication, migration, OOP coexistence, architecture, and booking acceptance evidence. Verification for this release used isolated synthetic MySQL and in-memory SQLite. The procedural application, the OOP application, and the prepared database were outside the change scope. [Earlier acceptance evidence](final-acceptance.md)

## Findings and corrections

| Severity | Confirmed finding | Correction and evidence |
| --- | --- | --- |
| High | The staff directory listed higher-privilege accounts that the per-account policy denies to the caller. Search and pagination included those accounts. | Apply the same role boundary before querying pages or totals. The API test covers scoped results and search, Super Admin visibility, and the policy-backed Delete action. The interactive Admin check showed only permitted roles and accounts. |
| High | Typing a payment in the Customers unpaid view passed a number to a string-only parser. The operation stopped before submitting a payment. | Reuse the booking payment parser and the existing idempotent payment client. Chrome applied a synthetic payment in the unpaid view and checked the refreshed balance. |
| Medium | Creating or extending a period from Services could overlap an existing range, including a gap within that range. | Use the existing season overlap lookup inside the service lock. Regressions cover internal gaps, inclusive endpoints, adjacent ranges, and edits. Legacy ranges can retain their dates during a label or capacity change. |
| Medium | Reports ignored links from availability, and editing draft filters could relabel or change the all-rows export of an already generated report. Drivers also received a forbidden catalog request while loading the service picker. | Apply linked filters before the first report load. Use the server-confirmed query for the displayed report, pagination, printing, and exports. Report service options expose IDs and localized names through the existing report permission boundary. Chrome verified linked and draft filters, Driver privacy, and German-format dates in CSV. |
| Medium | Profile, the manual, and Logout disappeared on narrow layouts. Desktop sign-out could submit an expired CSRF token after Inertia sign-in and return HTTP 419. | Restore the account actions in the mobile menu. Desktop and mobile sign-out use the same session-aware Inertia action. Chromium verifies sign-out, focus return with Escape, and menu state. Both layouts were checked interactively. |
| Medium | Group Leaders were offered customer writes, payment controls, and season forms that the server disallowed. Customer totals also mixed whole-result and visible-page values without a scope label. | Show actions according to the existing permissions. Label the active count as page-specific and display unpaid counts and totals in the unpaid view. Chrome verified the read-only workflow and open-customer action. |
| Medium | Services and Booking could widen the document on tablets or phones. Decorative shell gradients overflowed, and mobile stacked tables retained desktop minimum widths. | Contain the decoration and let the two stacked tables fit the viewport. The Services and booking lifecycle tests assert the document width on narrow screens. |
| Low | Browser requests for two remote font stylesheets failed. The service-period form and customer feedback used English in German and Spanish views. Some booking balances had no currency. | Host the existing typefaces locally, synchronize the request locale with Laravel's locale, translate service-period and payment labels, and use the established localized EUR/BGN display. Both fonts loaded in Chrome. [Fraunces source](https://github.com/google/fonts/tree/main/ofl/fraunces) · [Space Grotesk source](https://github.com/google/fonts/tree/main/ofl/spacegrotesk) |

The English staff manual now describes role boundaries, booking and payment actions, service-season availability, report ranges and exports, and mobile account actions. It uses the current Laravel labels and routes. The three README screenshots were recaptured from a clean MySQL demo with generated customer records and visually reviewed.

## Browser and integrity evidence

The installed Chrome walkthrough made 36 authorized route visits across the five roles and revisited the new-period form in German. The final instrumented run captured zero unexpected HTTP failures, failed requests, console warnings or errors, and page exceptions on those routes. A second layout sweep covered 19 authorized pages at 820 px and 390 px. It found no page-level horizontal overflow after the corrections. Wider tables can scroll within their containers. A separate interactive browser session exercised the workflows below.

| Role | Interactive check |
| --- | --- |
| Driver | Generated a Ski school transport report and checked that restricted columns were absent. |
| Group Leader | Opened Customers and Unpaid fees and confirmed that customer creation and payment controls were absent. |
| Manager | Opened Ski school daily availability and followed its report link. The generated report matched the linked service and date. |
| Admin | Opened the staff directory and Add User form. Super Admin data and role assignment were outside the visible scope. Verified desktop sign-out. |
| Super Admin | Confirmed visibility of the highest-level staff account, opened the staff manual, and expanded its report troubleshooting item. |

The automated Chromium business flow created a synthetic customer, booked an existing service day and an eligible gap day, applied a payment, moved booking dates, ran a report, cancelled the booking, and compared the remaining places in a Group Leader session. It also checked a late service response, real CSV downloads, and an independent print window. The suite used synthetic roles and records exclusively.

The fresh database contained 60 customers, two services, six service periods, 112 scheduled days, 16 bookings, 28 booking-date links, and 16 payment mirrors. Each of six checks returned zero findings. The checks covered overlapping periods, dates outside their period, service mismatches, payment mirror differences, overbooking, and duplicate active customer-service dates. The separate MySQL last-place booking and overpayment races each accepted one write and rejected the competing write.

## Release verification

| Gate | Actual result |
| --- | --- |
| Clean-checkout Docker install | 24.73 seconds from Compose build/start to healthy on ARM64, with existing image and build caches and fresh isolated database volumes. This measurement excludes cloning and Docker installation. The earlier cold-cache results remain in the previous acceptance report. |
| Runtime and PHP checks | PHP 8.5.13 in the ARM64 image. 200 PHP tests and 1,185 assertions passed. PHPStan level 8, syntax, Pint, Composer validation and platform requirements passed. |
| Frontend checks | 84 Vitest tests passed. ESLint, Prettier, Vue type checking, and the Vite build passed. |
| Dependency audits | The Composer and npm lockfile audits both returned zero advisories for this run. |
| Chromium | 11 browser scenarios in the Docker quality gate, with the opt-in screenshot case skipped there. The screenshot case passed separately in the synthetic Chrome run. |
| Repository scan | Gitleaks scanned the shareable working tree and reachable Git history without findings. |

## Performance

The existing deterministic fixture was run with 100,000 synthetic customers on both SQLite 3.51.2 and MySQL 8.4.13. Each median uses seven measured repository-level samples after a warm-up. SQLite ran in memory on PHP 8.5.3. MySQL ran in a disposable Docker schema using the same generator, pagination and query cases with the PHP 8.5.13 container client. HTTP rendering and network latency are outside these numbers.

| Workload | SQLite median | MySQL median | Queries |
| --- | ---: | ---: | ---: |
| First page of 25 | 0.13 ms | 3.65 ms | 2 |
| Page 3,900 | 0.54 ms | 20.59 ms | 2 |
| Matching substring | 25.99 ms | 82.79 ms | 2 |
| Missing substring | 13.77 ms | 35.65 ms | 1 |

Peak allocated PHP memory was 36.5 MiB in both runs. A synthetic report returned 10 or 16 rows with seven SQL queries in either case. The report count and totals are database aggregates and details are fetched in batches. The Vue report starts catalog and data requests in parallel. The production entry JavaScript was 102.46 kB gzipped and the locale chunk was 19.87 kB gzipped. The two basic Latin webfont files total 89,708 bytes on first uncached use.

The customer query continues to use server-side offset pagination and substring matching. The measurements give a basis for a future change if the workload warrants it. The schema already has relationship and date indexes, and this review leaves search and index changes outside the release. Services, bookings, and reports were reviewed for eager loading, bounded pages, and repeated requests. The 100,000-row benchmark covers customer list and search. It does not represent a 100,000-booking export or a production latency budget.

## Architecture and limitations

- Authorization remains in Laravel policies and middleware. Payment parsing reuses the booking helper, and overlap checks run under the existing service lock. Dependencies, data models, and database schema were kept in place.
- Grouped season presentation helpers remain reachable only from older frontend tests, while service labels, bonus grouping, and daily report link helpers are still used. The single-implementation repository contracts and forwarding service methods remain candidates for later simplification when related features change.
- At this review, German and Spanish each lacked 356 UI strings, and all locales shared the English manual. The [publication review](publication-readiness.md) records the corrections and practical accessibility results. Full accessibility conformance, physical passkeys and x86-64 execution remain unverified.
- Booking rows retain their full date itinerary within a matched report record. Header filters refine the current page, and whole-range CSV/print still collect server pages in the browser. Unpaid balances and whole-result exports were verified on the demo fixture. Their performance at enterprise scale remains unmeasured.
- Review and testing were agent-assisted. The owner provided the task scope and earlier approvals. Human review of this release and permission to publish are separate, outstanding steps. The local commits preserve the existing Git history. [Engineering record](ai-development.md)

The current [publication checklist](publication-readiness.md#publication-checklist) supersedes this review's publication gates.
