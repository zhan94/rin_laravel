# CRM Migration Parity (`rin_legacy` -> `ski2.0`)

## Target standards
- Laravel 12 + PHP 8.5-ready code style (`strict_types`, DTO + Service + Repository)
- Versioned APIs: `/api/v1/*`
- Unified JSON envelope:
  - `data`
  - `meta`
  - `errors`
- Vue 3 + TypeScript modules with API abstraction (no direct HTTP calls in pages)

## Legacy route groups

| Legacy module | Legacy routes | Laravel module status |
| --- | --- | --- |
| Dashboard | `/` | `Done` (`/dashboard`) |
| Auth / passkeys | `/login`, `/logout`, `/forgot-password`, `/reset-password`, `/passkeys/*` | `Partially done` (Fortify auth ready; passkey compatibility pending) |
| Customers | `/customers*` | `In progress` (`/api/v1/customers` + `crm/CustomersIndex.vue`) |
| Users | `/users*` | `In progress` (`/api/v1/users`) |
| Services | `/services*` | `In progress` (`/api/v1/services`, `/api/v1/services/{service}/slots`) |
| Bonus | `/bonus*` | `In progress` (`/api/v1/bonus` + `crm/BonusIndex.vue`) |
| Seasons | `/seasons` | `In progress` (`/api/v1/seasons*` + `crm/SeasonsIndex.vue`) |
| Booking | `/booking` | `In progress` (`/api/v1/bookings` + `crm/BookingsIndex.vue`) |
| Catalog cards/equips | `/cards*`, `/equips*` | `In progress` (`/api/v1/cards`, `/api/v1/equips` + `crm/CardsIndex.vue`, `crm/EquipsIndex.vue`) |
| Reports | `/reports` | `In progress` (`/api/v1/reports` + `crm/ReportsIndex.vue`) |
| Logs | `/logs` | `In progress` (`/api/v1/logs*` + `crm/LogsIndex.vue`) |
| Locations | `/locations*` | `In progress` (`/api/v1/locations` + `crm/LocationsIndex.vue`) |
| Issue reports | `/issues*` | `In progress` (`/api/v1/issues*` + `crm/IssuesIndex.vue`) |
| Notifications | `/notifications/read/{id}` | `In progress` (`/api/v1/notifications*`) |
| AJAX endpoints | `/ajax`, `/autocomplete` | `In progress` (typed `/api/v1/booking-support/*` + booking payments endpoints) |

## Current implementation in `ski2.0`

### Backend
- Added clean-architecture directories and modules:
  - `app/DTOs/*`
  - `app/Repositories/*`
  - `app/Services/*`
  - `app/Enums/*`
  - `app/Support/Api/*`
- Added global API error mapping in `bootstrap/app.php`.
- Added API v1 routes in `routes/api.php`.
- Added customers/users APIs:
  - requests
  - DTOs
  - repositories
  - services
  - API resources
- Added services/services-slots APIs:
  - service titles CRUD with protected core-title deactivation rule
  - slot CRUD with slot date synchronization
  - typed requests/DTOs/resources/services
- Added booking APIs:
  - booking create/list/show/update/deactivate
  - transactional booking flow with automatic slot/date resolution
  - customer conflict and slot-capacity checks
  - booking payment totals synchronization
  - ski winter details synchronization
- Added booking support and payment parity endpoints:
  - `/api/v1/booking-support/*`
  - `/api/v1/bookings/{booking}/payments/*`
- Added cards/equips APIs:
  - card and equipment CRUD with service relation loading
  - EUR price normalization and `per_day` calculation
  - price-row replacement semantics on update
  - typed requests/DTOs/resources/services/repositories
- Added seasons APIs:
  - overview endpoint with `service` and `bonus` modes
  - default season boundaries update endpoint
  - service-season create/update/delete endpoints
  - overlap validation + existing-service-date range validation + delete guard when bookings exist
- Added reports API:
  - `/api/v1/reports` with date/service/search filters
  - sorting, pagination, totals, and role-aware response shape
- Added logs API:
  - `/api/v1/logs` list with legacy visibility rules
  - single delete, bulk delete-selected, and clear-visible endpoints
  - admin/super-admin role gating parity
- Added notifications API:
  - `/api/v1/notifications` list + unread count
  - `/api/v1/notifications/{id}/read`
  - `/api/v1/notifications/read-all`
- Added cross-module manager-action notifications:
  - shared `ManagerActionNotificationService` for manager-only emission
  - recipients limited to `admin` and `super_admin` users (excluding actor)
  - integrated into create/update/delete flows for customers, users, services, slots, bookings, bonus, locations, cards, equips
  - integrated into booking payment apply flow
  - contextual payload metadata (`module_key`, `action_key`, `entity_type`, `entity_id`, `link_path`)
- Added issues API:
  - `/api/v1/issues` list + summary + filters + pagination
  - `/api/v1/issues/{id}` details with comments and attachments
  - create, status update, assignee update, comment create endpoints
  - attachment open/download endpoint
  - role gating parity + issue notifications (`assigned`, `status_changed`, `comment_added`)
- Added core migrations:
  - `roles`
  - `customers`
  - legacy-compatible `users` columns (`username`, `firstname`, `surname`, `lastname`, `role_id`, `is_active`)
  - `services`, `service_slots`, `service_dates`
  - `locations`, `season_defaults`
  - `bookings`, `booking_dates`, `booking_payments`, `booking_winter_details`
  - `cards`, `cards_price`, `equips`, `equips_price`
  - `bonus`, `bonus_price`
  - `logs`, `user_notifications`, notification templates/preferences
- Added role seeder with legacy keys.

### Frontend
- Added HTTP abstraction:
  - `resources/js/services/http.ts`
- Added customer module:
  - `resources/js/modules/customers/*`
- Added CRM customers page:
  - `resources/js/pages/crm/CustomersIndex.vue`
  - route: `/crm/customers`
- Added CRM services page:
  - `resources/js/pages/crm/ServicesIndex.vue`
  - route: `/crm/services`
- Added booking module:
  - `resources/js/modules/bookings/*`
- Added CRM bookings page:
  - `resources/js/pages/crm/BookingsIndex.vue`
  - route: `/crm/bookings`
- Added catalog module:
  - `resources/js/modules/catalog/*`
- Added CRM cards/equipment pages:
  - `resources/js/pages/crm/CardsIndex.vue`
  - `resources/js/pages/crm/EquipsIndex.vue`
  - routes: `/crm/cards`, `/crm/equips`
- Added locations module/page:
  - `resources/js/modules/locations/*`
  - `resources/js/pages/crm/LocationsIndex.vue`
  - route: `/crm/locations`
- Added seasons module:
  - `resources/js/modules/seasons/*`
- Added CRM seasons page:
  - `resources/js/pages/crm/SeasonsIndex.vue`
  - route: `/crm/seasons`
- Added reports module/page:
  - `resources/js/modules/reports/*`
  - `resources/js/pages/crm/ReportsIndex.vue`
  - route: `/crm/reports`
- Added logs module/page:
  - `resources/js/modules/logs/*`
  - `resources/js/pages/crm/LogsIndex.vue`
  - route: `/crm/logs`
- Added issues module/page:
  - `resources/js/modules/issues/*`
  - `resources/js/pages/crm/IssuesIndex.vue`
  - route: `/crm/issues`

## Next migration order (recommended)
1. Passkey compatibility layer for legacy credentials/challenges
2. Issue workflow polish (labels/translations, richer UI parity details)
3. Notifications UX polish (toasts/live refresh and contextual deep-links in all CRM pages)

## Non-negotiable coding rules for next phases
- Controllers remain thin; no query logic inside controllers.
- DB writes go through services (transaction boundaries in service layer).
- No raw array payloads across layers; DTOs only.
- API output only through Resources + response envelope.
- Heavy jobs (reports/export/notifications) should be queued.
