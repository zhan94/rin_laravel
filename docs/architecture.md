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
