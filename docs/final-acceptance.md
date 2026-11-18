# Final acceptance

**Evaluation date** 18 November 2026. The owner approved local acceptance. The owner authorized a fresh local Git history after the original Laravel history could not be recovered. Publication approval remains open.

## Security and business behavior

| Area | Verified change |
| --- | --- |
| Staff provisioning | Public registration is disabled. The user policy blocks administrators from assigning higher roles or changing higher-privilege accounts. Authorization checks load current actor and target records inside staff writes. |
| Credentials and sessions | Fortify rejects a stored password hash supplied as a password. Password, role, and account status changes invalidate existing browser state and personal tokens. Browser API writes use Sanctum's CSRF middleware. Token creation requires recent password confirmation and issues scoped, expiring tokens. |
| Passkeys and recovery | Passkey endpoints use the configured origin and relying party, with throttling and recent password confirmation for management. Recovery responses avoid disclosing account existence. |
| Audit and files | Denied access, credential events, staff changes, and booking and payment writes emit redacted audit events to a dedicated private log. Issue attachments remain private, and database rollback removes a just-written file. Attached historical formats are served as downloads except for approved image MIME types. |
| Data initialization | The default seeder rejects prepared databases. The Compose demo checks the actual MySQL schema and requires an empty business database on the first run. Former manual screenshots were removed. |
| Reports | The all-rows print window builds a semantic table with text nodes. The CSV export quotes international text and neutralizes formula-like values. Chrome actually downloaded the CSV and opened the print window. |
| Concurrency | Parallel MySQL processes competing for the last seat or the same remaining balance produced one successful write each. The rejected requests left counts and payment mirrors consistent. Booking and payment retries use persisted receipts. A stale payment correction returns a conflict, and booked slot dates retain their original IDs. |
| Dashboard and interface | The dashboard uses configured season boundaries and database aggregates. Inertia tests prove a multi-date booking is counted and summed once. Browser checks cover the new dashboard, translated customer page, and mobile keyboard navigation. The staff creation module is labeled “Add customer.” |

The OOP comparison covered role boundaries, capacity checks, payment retries and stale corrections, and dashboard counts. The Laravel implementation retains its own routes, Vue interface, and API envelope. The [architecture notes](architecture.md) record those differences and the compatibility rule for older API clients that omit request keys.

## Automated gates and browser verification

| Check | Local result |
| --- | --- |
| PHP runtime | PHP 8.5.3 on macOS ARM64 and PHP 8.5.12 in Docker |
| Pest | 190 tests and 1,051 assertions passed in isolated SQLite on macOS and in the clean ARM64 image |
| PHPStan/Larastan | Level 8, zero errors |
| Pint | All 412 scanned PHP files passed |
| Frontend | 76 Vitest tests passed. Vite build, TypeScript checks, ESLint, and Prettier passed. |
| Advisory feeds | Composer and npm lockfile audits reported zero advisories |
| Chrome/Playwright | Four tests passed against the Laravel Compose app |
| MySQL concurrency | Both independent-process races passed on the disposable ARM64 MySQL 8.4 server |
| GitLab and GitHub | Both workflows call the same quality and secret-scan script. The end-to-end quality gate passed locally on ARM64, including Composer and npm audits and all four Chromium tests inside Docker against the isolated demo. The GitHub workflow passed actionlint 1.7.12. Hosted runs remain pending. |

Composer metadata validation reported that the root application license is unspecified. Vite emitted a warning about a runtime-resolved legacy background asset. The image was served by the local application. Generated Wayfinder files are excluded from frontend formatting because Vite regenerates them.

## 100,000-customer benchmark

PHP 8.5.3 and SQLite 3.51.2 ran on macOS ARM64. The benchmark used an in-memory database, one warm-up, and the median of seven measured repository calls. Seeding took **0.296 s**. Peak allocated PHP memory was **36.5 MiB**.

| Customer operation | Median | SQL queries |
| --- | ---: | ---: |
| First 25 rows | 0.12 ms | 2 |
| Page 3,900 | 0.47 ms | 2 |
| Unique substring match | 24.52 ms | 2 |
| Substring miss | 13.17 ms | 1 |

The substring filter scans the candidate text fields. These timings cover repository calls on SQLite. The MySQL race test verifies locking under concurrent writes. Full-range print and export still retrieve all selected report pages in the browser. The 100,000-customer benchmark does not measure that workload.

## Docker installation

Docker Desktop Engine 29.9.3 and Compose v5.7.0 ran on an Apple Silicon Mac. The corrected Laravel install took **137.92 seconds** from a clean source export with no installed dependencies, a fresh BuildKit builder, and fresh application and database volumes. The timer stopped after the expected Laravel health body and the Inertia login page responded. The Docker daemon already had the MySQL, Redis, and Alpine service images. The test used a source export because the workspace does not contain Git metadata.

**Clean Git checkout** Both measurements used independent `git clone --no-local` checkouts of `b3ed179` with clean working trees. The isolated ARM64 Docker Engine was 29.10.0 with Compose v5.7.1. The timer covered `docker compose up --build --wait` through the Laravel health response and the rendered Inertia sign-in page.

| Run | Cache and database state | Time |
| --- | --- | ---: |
| Cold | Empty isolated Docker image store and build cache, fresh demo volumes | **167.35 s** |
| Warm | Same daemon and retained images and build cache, fresh demo volumes | **11.19 s** |

Subsequent commits record acceptance and format the redacted manual references. They preserve application behavior and the benchmarked Docker and CI configuration.

The OOP app started from its existing read-only checkout into a separate Docker project. A fresh OOP Compose install took **12.23 seconds** with cached images and build layers. Inspection verified separate HTTP ports, bridge networks, and MySQL data volumes. A clean clone of the sanitized Laravel revision ran alongside the separate OOP Compose project. Chrome signed in to both in the same browser and confirmed the OOP session did not grant Laravel access. Both sessions continued to work independently. The procedural legacy application was outside both Compose projects.

The isolated MySQL 8.4.13 Laravel demo had 5 staff accounts, 60 customers, 2 services, 2 locations, 2 cards, 8 bookings, 16 booking-date links, and 8 payment mirrors. With utf8mb4 and foreign-key checks enabled, the aggregate checks returned zero payment disagreements, duplicate customer emails, or customer addresses outside the reserved demo domain.

The prepared `rin_lara` records were left intact. The new receipts and date-index migrations ran in isolated databases. See the [original import report](legacy-data-migration.md) for the pre-remediation record counts and foreign-key checks.

## Git history and sharing

The current `ski2.0` workspace has no `.git` directory or worktree pointer. The two local archive variants contain source files. Neither contains Git objects or refs. The neighboring `ski` checkout has 66 reachable commits, no merge commits or tags, and substantial pending edits. Its GitHub remote advertises `master` at `4146933`, the same tip as the local checkout. That tip lacks the CRM customer page, booking payment service, and legacy importer. The neighboring final-review repository contains the framework-free OOP application.

The owner supplied the GitLab project `VV-jan/rin_lara`. The project API reported public visibility and a configured default branch of `main`. Two Git protocol versions returned no advertised refs. The branch and tag APIs also returned empty lists. The project provides no available commits from which to establish the Laravel baseline. The owner then explicitly approved an authentic local source import. The project and the existing local repositories remain intact. [Supplied GitLab project](https://gitlab.com/VV-jan/rin_lara)

### Confidentiality review

- Gitleaks 8.30.1 reported zero credential matches in the shareable Laravel files. A separate literal-credential check found no non-placeholder assignments. Private environments, database exports, dependency folders, logs, and uploads were excluded. The only email-like strings outside reserved demo domains were third-party dependency metadata in lockfiles.
- The older `ski` candidate passed `git fsck`. Gitleaks reported no credentials in its reachable commit patches or in the contents of all 821 readable blobs and 66 commit objects, including local objects outside the available refs. The audit also checked the filenames represented by 480 trees. It found no private environment or database-export filenames. Two historical files, the README and the user factory, contain non-example contact literals that require owner confirmation if this candidate is ever included in a publication. No credential requiring rotation was confirmed in the reviewed material. Historical objects from the pre-import Laravel workspace remain unavailable.
- The replacement screenshots were captured on the isolated Compose demo. The database check found 60 generated customers and zero customer emails outside the reserved demo domain. OCR found no non-example email addresses in the three images. The prepared database was outside the screenshot workflow.

### Handoff and publication

The new local repository starts from the verified Laravel source on `main`. Commit `42f6172` imports the application and its existing documentation. Commit `b3ed179` adds the shared verification gates. A separate documentation commit records the checkout timings and acceptance. The initialization was owner-approved after the GitLab destination and previous candidate were investigated. The initial import does not assert continuity with the older checkout. The new commits use current dates and explicit agent attribution. The initial audit and prior migration and remediation records are included. The new repository passed object integrity, source and reachable-history credential scans, and an object-level confidentiality review. No credential requiring rotation or revocation was confirmed. Two manuals contained a workstation path. The references were generalized before sealing the shareable initial history. The earlier unpublished draft was retained separately for private recovery.

After the history review and clean-checkout verification, GitLab and GitHub should receive the same reviewed commit IDs. The supplied GitLab destination is public. Publication requires separate owner approval and confirmation of the application and asset rights. Any confirmed credentials need rotation or revocation before publication. Review the publication rights and approved visibility before sharing these commits. GitLab and GitHub should receive the same reviewed history.

Physical passkey devices, full accessibility conformance, and hosted CI runs remain outside the verified local results. Requiring confirmed email on every legacy staff account remains a policy decision because some imported identities may need a verification rollout. [Engineering process and human approvals](ai-development.md)

## Synthetic screenshots

Screenshots use the isolated Compose demonstration with `example.test` identities and generated customer records.

- [Season dashboard](screenshots/dashboard.png)
- [Customer list](screenshots/customers.png)
- [Mobile navigation](screenshots/mobile-navigation.png)
