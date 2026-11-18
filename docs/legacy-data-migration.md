# Legacy CRM data migration

## Scope and database structure

The local migration used PHP 8.5.3, Laravel 12 and MySQL 9.6.0. The active default Laravel connection was verified as `mysql` on `rin_lara`. Both databases use InnoDB and utf8mb4. Their database default collation is `utf8mb4_0900_ai_ci` and the application tables use `utf8mb4_unicode_ci`. The source receipt and migration ledgers also contain ASCII columns. The source had 30 base tables and no views, triggers, routines or events. Inspection covered column types, sizes, defaults, nullability, primary and unique keys, secondary indexes, foreign keys, and exact counts. The import plan was reconciled with the existing Laravel migrations, Eloquent models, booking and payments workflows, and the legacy fixture generator.

The destination uses Laravel's existing tables, including its own migrations ledger, sessions, password resets, passkeys, challenges, queues, caches, API tokens and notification preferences/templates. `services_price` and `payments` also exist in Laravel. This source has no equivalent rows or tables, so those modules start with their existing schema and no imported rows. The authoritative imported payment amounts are on `bookings` and `booking_payments`.

The two new migrations preserve missing catalog status fields, textual lookup keys, the winter equipment flag, notification history and original links, and historical mutation receipts. They also use `DATETIME` for source wall-clock timestamps that would otherwise shift when read through the application's MySQL session. Native Laravel primary keys, default fields and foreign keys are reused. The archive receipt table keeps the legacy composite identity and references the Laravel user table. Both application schemas keep indexes for the already-supported browsing and relationship queries.

## Table and column mappings

Every included source column appears in [the executable mapping](../app/Services/LegacyImport/LegacyImportPlan.php). A column listed below without an arrow keeps its name. Primary IDs, operational timestamps and related IDs are retained. A list of derived fields and deliberate conversions follows the table.

| Source → destination | Business meaning and mapped columns | Source rows | Final destination rows |
| --- | --- | ---: | ---: |
| `roles` | Authorization roles. `id, role_key, title_bg, title_en, sort_order, is_active`. | 5 | 5 |
| `users` | Staff identities and authentication. `id, username, password, firstname, surname, lastname, email, role_id, active → is_active`. | 6 | 6 |
| `season_defaults` | Calendar singleton. `id, start_month, start_day, end_month, end_day`. | 1 | 1 |
| `services` | Service catalog and booking capacity. `id, title_bg, title_en, title_key → legacy_title_key, max, active`. | 5 | 5 |
| `skills` | Winter skill levels. `id, title`. | 3 | 3 |
| `locations` | Service-specific transport routes and times. `id, service_id, location_from, location_to, from_time, to_time, active`. | 39 | 39 |
| `cards` | Lift-card catalog. `id, title, title_bg, title_en, service, active`. | 4 | 4 |
| `equips` | Rental equipment catalog. `id, title, title_bg, title_en, service, active`. | 4 | 4 |
| `bonus` | Optional activities. `id, title_bg, title_en, title_key → legacy_title_key, location_from, location_to, date_from, date_to, active, price, description`. | 3 | 3 |
| `cards_price` | Card duration pricing. `id, card_id, days, price, per_day`. | 12 | 12 |
| `equips_price` | Equipment duration pricing. `id, equip_id, days, price, per_day`. | 12 | 12 |
| `bonus_price` | Activity duration pricing. `id, bonus_id, days, price, per_day`. | 5 | 5 |
| `service_slots` | Scheduled service periods. `id, service_id, name, max, date_start, date_end, season_type, active`. | 208 | 208 |
| `service_dates` | Bookable days within periods. `id, slot_id, slot_date, is_active`. | 728 | 728 |
| `customers` | Customer and guardian/contact records. `id, firstname, surname, lastname, date_birth, age, parent_name, parent_phone, email, active`. | 10,000 | 10,000 |
| `bookings` | Reservations, balance, transport and notes. `id, customer_id, service, service_type, sum_paid, paid, location, approved, active, lunch, note`. `skill_id, equip_id, card_id` are checked against the normalized winter detail row. | 25,478 | 25,478 |
| `booking_dates` | Reservation-to-service-day links. `id, booking_id, service_date_id`. | 55,111 | 55,111 |
| `booking_payments` | Financial mirror of the booking. `booking_id, sum_paid, paid`. | 25,478 | 25,478 |
| `booking_winter_details` | Winter rentals, skill, and measurements. `booking_id, skill_id, shoe_size, height, equip_enabled, equip_id, card_id, created_at, updated_at`. `customer_id, service_id, service_type` are checked against the parent booking. | 24,477 | 24,477 |
| `logs` | Historical audit events. `id, user, page, ip, location, date`. | 25,491 | 25,491 |
| `issue_reports` | Issues with ownership, workflow and timestamps. `id, created_by, module_key, issue_key, priority, status, title, description, page_url, resolved_by, assigned_to, assigned_at, resolved_at, created_at, updated_at`. | 102 | 102 |
| `issue_report_comments` | Issue discussion history. `id, issue_id, user_id, comment_text, created_at`. | 204 | 204 |
| `issue_report_attachments` | Private file metadata. `id, issue_id, stored_name, original_name, mime_type, file_size, created_at`. Bytes come from the configured private source directory. | 102 | 102 |
| `user_notifications` | Notification inbox and historical delivery state. `id, user_id, actor_user_id, notification_type, module_key, action_key, entity_type, entity_id, title, message, metadata, link_path → legacy_link_path, status, priority, delivery_channel, is_sent, sent_at, read_at, deleted_at, created_at, updated_at`. | 25,482 | 25,482 |
| `mutation_requests` → `legacy_mutation_receipts` | Historical receipts. `operation, actor_id, request_key, request_hash, result_json, created_at`. The archive has no replay or authentication path. | 6 | 6 |
| **Mapped business rows** | | **192,966** | **192,966** |

The source also contained 4 `schema_migrations` rows, 2 `sessions`, 6 `password_reset_tokens`, 15 `webauthn_challenges` and 7 `user_passkeys`. These 34 infrastructure or authentication rows are excluded. Users sign in with the supported password hashes that were transferred and enroll passkeys again under the Laravel relying party. Legacy session activity and one-time authentication challenges expire independently.

### Conversions and integrity rules

- Keep the source name, contact and text fields byte-for-byte after MySQL's utf8mb4 decoding. Birth and activity dates accept ISO calendar dates, day-month-year strings with dots or slashes, and documented empty/zero date sentinels for null. Invalid dates, required nulls, invalid encoding, values outside the destination's range and unsupported enum/flag values block the run.
- Keep prices and credits in their existing units. Exact decimal strings are checked against the destination's scale before binding. The booking model uses integer BGN and catalog prices use two decimal places. This fixture needed no monetary rounding. A future source with fractional booking totals or sub-cent catalog prices fails validation and needs a reviewed money-model change.
- Preserve both original localized titles and string lookup keys. Build Laravel translation JSON from available titles and use a fallback display title when the source has no standalone title. The native numeric `title_key` is filled only when the source key is a representable unsigned integer. Missing source timestamps on roles, service slots, payment rows and the season setting stay null. Laravel-only authentication fields keep their defaults.
- A booking's `service_type` determines whether `service` points to the service catalog or bonus catalog. A zero booking location is converted to null. A zero `locations.service_id` denotes a global route and retains that meaning. Verify duplicate winter fields before using the normalized record. Keep the explicit `equip_enabled` value for booking prefill.
- Preserve the original notification URL and map recognized legacy links to Laravel CRM routes, including booking customer context and the unpaid customer view. An unrecognized URL is retained as history, with the active notification link left empty for Laravel's fallback handling.
- The importer checks primary and unique indexes and detects destination collisions. Customer emails are not deduplicated because the model permits shared contact email addresses. Logical checks cover links that are polymorphic or use a sentinel, and physical destination foreign keys stay enabled. Attachment metadata is checked against source file size, then SHA-256 is used to verify existing or staged destination files. File names must be safe basenames.

## Runbook

Set `DB_CONNECTION=mysql` and `DB_DATABASE=rin_lara` in the private environment. Set `LEGACY_DB_HOST`, `LEGACY_DB_PORT`, `LEGACY_DB_DATABASE=briliabg_shadmin`, `LEGACY_DB_USERNAME`, `LEGACY_DB_PASSWORD` and `LEGACY_ATTACHMENT_DIRECTORY` as needed. Use a dedicated MySQL account granted `SELECT` on the legacy database. The server also enforces read-only transactions for the Laravel `legacy` connection, and the command checks `@@transaction_read_only` and repeatable-read isolation. The local account had no grant option, so this particular run used the existing local identity through the separate MySQL read-only session. A grant-level read-only account remains a deployment dependency where the provisioner is separate.

Inspect the default database before running Laravel schema migrations. Run during a maintenance window with application and worker writes paused on the destination. For external datasets, confirm private handling, take the destination's normal backup, approve any existing-row changes explicitly, and keep the source and its attachment directory stable for reconciliation.

```sh
php artisan migrate:status --database=mysql
php artisan migrate --database=mysql
php artisan legacy:import --dry-run --chunk=1000
php artisan legacy:import --chunk=1000
php artisan legacy:import --dry-run --chunk=1000
php artisan test
```

All writes require interactive approval. `--replace-season-defaults` permits the one-row migration seed replacement only if the destination is still the original September 1–August 31 setting and its timestamp matches the revision being replaced. For this run the user approved importing the source's September 1–May 31 setting and explicitly approved the 27 later audit rows and staff edits not attributable to the fixture generator. Run the dry run with the same season option before the write if applicable. Other changed primary keys or secondary unique conflicts block the run.

The importer scans by indexed primary keys in bounded batches. It uses a consistent read-only source snapshot, a destination advisory lock, and an all-or-nothing destination database transaction. It reconciles counts and rereads every row before commit. It reports data errors by table and rule without including customer values. Identical destination rows are accepted on retry. If a process stops while publishing files, an already published, matching private attachment can remain for the next retry. Existing file contents are never replaced. The dry run does not publish files.

## Results

### Data integrity

- Laravel recorded 39 migrations on `rin_lara`. The import inserted 192,965 business rows and replaced the one approved season row. All 25 mapped table counts match. The final dry run found all 192,966 mapped rows already identical and proposed zero inserts or replacements.
- The commit passed 87 relationship, duplicate, and consistency checks. The final dry run passed 45 checks after adding another receipt-collation check. The destination had 26 physical foreign keys with checks enabled, zero orphaned card prices, zero duplicate nonempty customer-email groups, and zero non-utf8mb4 text columns.
- All 10,000 customers had both required name fields and a date of birth. All 39 locations had both endpoints, both times and linked services. All four cards had a title, a service and associated prices. The importer also compared every mapped field for all 12 card prices and for all customer, location and relationship records.
- Source and destination booking and payment rows both totaled 4,479,739 BGN booked and 2,103,736 BGN paid. Card price sums matched at 810.00 BGN and per-day price sums matched at 270.00 BGN. The approved season starts September 1 and ends May 31.
- Verified all 102 issue-attachment references and the bytes of their single shared fixture file in Laravel's private storage. The authorized attachment endpoint returned HTTP 200.
- Replayed a Fortify sign-in through the Laravel HTTP kernel with a fixture account. The response redirected successfully and the database session was persisted, then rolled back with the verification transaction. The customer, location, card, booking, unpaid, report, issue, attachment and notification reads all returned HTTP 200. A report with an explicit date range returned 25 of 2,236 rows.
- All 173 automated tests passed, including the 100,000-customer import, rolled-back inserts, conflicting records, composite keys, UTF-8 text, attachments and repeat imports. PHPStan passed for the changed application code.

### Performance

Local MySQL runs used 1,000-row source chunks. HTTP times are medians of three warmed in-process Laravel requests with local MySQL, measured without network or browser time.

| Operation | Result |
| --- | ---: |
| Pre-import dry run | 4.83 s, 46 MiB peak PHP memory |
| Committed import, including row reconciliation | 19.79 s, 52 MiB peak PHP memory |
| Final dry run, with all destination rows populated | 6.72 s, 50 MiB peak PHP memory |
| 100,000-customer automated import into isolated SQLite | 1.79 s, less than 32 MiB additional peak PHP memory |
| Customer list, 25 rows | 2.59 ms, 2 SQL queries |
| Locations list | 2.66 ms, 3 SQL queries |
| Cards and price list | 3.49 ms, 4 SQL queries |
| Bookings list | 13.59 ms, 10 SQL queries |
| Dated bookings report | 166.87 ms |
| Unpaid overview, 17,934 rows | 560.81 ms, 37 SQL queries |

MySQL `EXPLAIN ANALYZE` showed a primary-key range scan for the 1,000-row customer import page and a reverse primary-key scan for the customer listing. Card prices used `idx_cards_price_card_days`, locations used `locations_service_id_index`, and the booking date join used `uq_booking_date_pair` with service-date primary key lookups. The corresponding example query times were approximately 0.148 ms, 0.015 ms, 0.005 ms, 0.010 ms and 0.010 ms.

### Errors and unresolved issues

- The first write attempt found a composite-key lookup defect and rolled back all database writes. The corrected lookup groups the columns in each key together. A regression test verifies rows sharing a booking, slot or operation across separate chunks.
- The pre-existing unpaid overview exceeded the local 128 MiB PHP limit on the imported dataset. Booking and booking-date reads now use 1,000-row batches, and the endpoint passed the follow-up test under the same memory limit. The API still returns the full overview to existing callers.
- The local MySQL identity lacked the grant option needed to provision a dedicated account. This migration used the explicitly checked, MySQL-enforced read-only session on the separate legacy connection. A MySQL administrator can supply grant-level `SELECT` credentials through the private environment.
- WebAuthn passkeys require fresh enrollment. Legacy live sessions, reset tokens and one-time challenges were excluded. The archived receipt rows are available for audit.

This runbook contains only schema metadata and aggregate verification results.
