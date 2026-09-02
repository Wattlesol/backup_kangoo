# Sanad Admin Full Admin Panel Coverage

Source: `Sanad Admin.pdf`, all pages. This file is the living implementation and QA reference for the Laravel admin panel.

## Coverage status

Implemented or in progress items are intentionally recorded here with the current code evidence. Items marked deferred are outside the document's V1 admin scope and must not be presented as complete.

## Dashboard and terminology

- In progress: dashboard cards now use Orders, Active Services, and Active Partners terminology and active-service/partner counts.
- In progress: the admin booking surface is presented as Orders; legacy route names remain for compatibility.
- Partial: the full order-status card set (Pending, In Progress, Completed, Cancelled) still requires final visual QA and card coverage.

## Categories

Required fields:

- Arabic Name
- English Name
- Icon
- Image
- Display Order
- Featured
- Status

Implementation coverage:

- Category admin create/edit captures all required fields.
- `English Name` is also saved into the legacy `name` column for existing lists, APIs, and references.
- Icon and Image use separate media collections: `category_icon` and `category_image`.
- Category ordering uses `display_order`, then `name`.

## Services

Removed from visible admin create/edit:

- Provider
- Address
- Visit Type

Required fields:

- Arabic Name
- English Name
- Government Entity
- Required Documents
- Estimated Completion Time
- Government Fees
- Service Fees
- Service Instructions
- Terms & Conditions
- Featured
- Status

Implementation coverage:

- Service admin create/edit captures the required Sanad fields.
- `English Name` is also saved into the legacy `name` column for compatibility.
- Provider is still populated internally for database/API compatibility.
- Legacy price is internally defaulted from Service Fees when needed by existing code.
- API resources expose the new Sanad fields while retaining old keys for downstream compatibility.
- The service-save API is restricted to authenticated Sanad admin roles; Partners cannot create or mutate master services through the API.

## Related Modules

- Sub Categories remain linked to parent Categories and retain image media handling.
- Packages remain enabled and are labeled as Service Bundles.
- Add-ons remain enabled and are labeled as Additional Services.

## Orders, Assignment, and Operations

- In progress: Orders datatable includes Order Number, Customer, Service, Partner, Current Status, Priority, Created Date, Expected Completion Date, and Payment Status.
- In progress: booking persistence supports assignment mode, reassignment reason, assigner, assignment time, SLA due time, and expected completion.
- Partial: Suggested Assignment, Auto Assignment scoring, Partner Information Card, and reassignment workflow require final admin UI and manual QA.
- Partial: Quality Control has the existing complaint workflow; escalations, SLA violations, and customer feedback need explicit admin views/filters.

## Pricing and retained modules

- In progress: service fees and government fees are Sanad-owned fields in the service admin flow; partner bidding is not exposed in the admin catalog.
- Implemented: service catalog mutations are restricted to Sanad admin roles, including the authenticated API route.
- Implemented: geographic and e-commerce navigation is disabled in V1 while routes/tables remain preserved; Working Hours remains available.

## Verification gates

- Migration: run `php artisan migrate` on the local QA database.
- Static: run PHP lint and `git diff --check` on touched files.
- Manual: create/edit/reopen/remove media for Category, Sub Category, Service, Service Bundle, and Additional Service.
- Manual: verify Order list, assignment reason validation, dashboard counts, API compatibility, and disabled navigation.
- Known repository gap: the project currently has no `tests/` directory; full Blade view compilation now passes.
