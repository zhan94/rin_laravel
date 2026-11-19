# Architecture

RIN uses Laravel Fortify, Sanctum, Inertia, Eloquent, Form Requests, and policies. The Vue UI uses typed API clients and the existing API envelope of `data`, `meta`, and `errors`. Validated booking inputs are normalized into the existing DTOs. Existing services and repositories still own the related business logic and query composition. The dashboard uses Eloquent relationships and grouped queries directly because it has a single page consumer.

## Staff access

Staff provisioning starts from an authorized administrator. The user policy checks the actor's role against the target account and the assigned role. Signed-in staff requests verify current account and role status. Password, role, and activation changes invalidate previous browser stamps and revoke personal access tokens. Normal browser API traffic uses Sanctum stateful CSRF verification. API tokens have read and write scopes, a bounded lifetime, and recent password confirmation at issuance.

## Booking and payments

The booking transaction locks the affected service before checking capacity and customer/date conflicts. Slot and season writes coordinate on that same row. Slot updates retain existing date IDs and reject a change that would remove a booked date or reduce capacity below the number of bookings. Payment writes lock the booking row and update the payment mirror in the same transaction. Booking edits preserve the paid amount when the client omits it. Changing a paid amount or applying a correction requires the previously loaded BGN balance.

The web client supplies a UUID `request_key` for booking creation and payment application and reuses it when retrying the same pending request. The server records the request hash and result atomically, scoped to the staff account and operation. Reusing a key with different input returns HTTP 409. Calls from older integrations can omit the key and retain their existing single-request behavior. Persisted payment receipts contain amounts, while booking creation receipts store the booking ID only. Confirmation email dispatch waits for the transaction to commit.

## Reports and dashboard

Print output builds DOM text nodes from row values. CSV output quotes cells and prefixes formula-like values as spreadsheet text. Dashboard statistics count each booking and payment once even when a booking spans several dates. Season boundaries follow the configured season defaults. The dashboard shows the first 100 daily rows and links to reports for the full list. Driver accounts land on reports, matching the OOP application. The dashboard preserves the legacy treatment of historical booking dates and inactive bookings in its season aggregates.

## Verification boundaries

The PHP test suite boots only with an isolated in-memory SQLite database. MySQL locking is verified separately with parallel processes. Browser tests run on generated data. Legacy import receipts remain historical records. Current Laravel mutation receipts use a separate table. See [the original migration record](legacy-data-migration.md) for mapping and prepared-database evidence.

## Recruitment review

The HTTP paths use Composer PSR-4 loading, Form Requests, API Resources, Eloquent casts and relationships, and native Laravel middleware and policies. The service container binds repository contracts to Eloquent implementations in `app/Providers/AppServiceProvider.php`. Interfaces with a single implementation and forwarding methods add navigation cost. For example, customer and user pagination simply delegate to their repositories. Booking and report repositories carry query composition, eager loading, and grouped totals that are distinct from write coordination and presentation. Consolidating every contract as part of this review would change constructors and tests without addressing a proven behavior or performance issue.

The booking repository and the booking and report Vue composables are the largest paths reviewed. The booking page already delegates its steps, location picker, and calendar to subcomponents. The catalog pages already share a composable for CRUD. Prefer extracting code when a concrete change reveals a stable shared operation. Both lockfiles were retained, and the review kept the installed Laravel, Vue, TypeScript, Tailwind, and Vite lines. Vue templates normally escape text. The remaining raw SVG binding is the server-generated Fortify QR code in the two-factor setup flow.

The schema uses keys and uniqueness constraints for catalog prices, slot dates, booking-date links, payment mirrors, and mutation receipts. Booking service references are polymorphic in the legacy layout, and a global pickup location can use the legacy sentinel. Keep those integrity rules explicit in the application and import checks. Reports use bounded result pages and batched detail reads. The dashboard uses grouped aggregates, and the booking list eager loads its relationships. The report date predicate wraps the date column, so the presence of an index alone does not establish that MySQL selects a range plan. Search uses substring semantics, and unpaid and full-export workflows can collect many results. The measured customer benchmark covers list queries rather than those whole-result workloads. [Final acceptance results](final-acceptance.md)

During the final review, the Driver report's data boundary was aligned with its existing visible fields. The authenticated role scopes the report query and presentation. Birth dates, email addresses, and note values are cleared for Drivers, monetary values are masked, and the API excludes private email matching and hidden-field sorting. Transport contact name and phone, routes, booking dates, and the existing outstanding-record count remain available to Drivers.


## Final product review

Report drafts are separate from the server-confirmed filters used for the table, paging and exports. Report service options contain localized names and IDs, including archived services needed for historical reports. Drivers access the same option endpoint under the report permission boundary. The staff directory scopes results before search and pagination using the account policy's role ordering. The per-target policy still authorizes each account write.

Service period writes and legacy season management now share the overlap lookup and the existing service row lock. The customer benchmark and report query counts continue to support the current pagination and eager-loading choices. [Final product acceptance](product-quality-review.md)

The final architecture pass consolidated the Eloquent visibility queries for logs and notifications and removed their mirrored interfaces. The [final review](architectural-review.md) explains the evidence, the contracts retained, and the trade-offs that still apply.
