# Sanad Admin Full Coverage Report

Source document: `docs/requirements/Sanad Admin.pdf`  
Scope: Laravel admin panel and the backend contracts required to make the admin workflows persistent and compatible.

Manual browser runbook: `docs/requirements/sanad-admin-browser-qa-checklist.md`

## Current status

This report is intentionally evidence-based. `Implemented` means the code path exists and has passed static/migration checks. `Partial` means the behavior exists in part but still needs browser-level QA or a larger workflow surface. `Deferred` means the PDF places it outside the current V1 scope.

| PDF area | Status | Evidence / remaining verification |
| --- | --- | --- |
| Dashboard terminology and core metrics | Partial | `HomeController` provides active services/partners, paid revenue/monthly revenue chart data, and order-status counts, including legacy bookings without Sanad stages; `dashboard/dashboard.blade.php` renders the Sanad order labels. Browser visual QA remains. |
| Categories | Implemented | Category migration, request, model, controller, form, datatable, API resource, ordering, icon/image media. |
| Sub Categories | Partial | Existing parent relationship and media flow retained; standard resource edit/update routes are now functional and edit-preview still needs manual browser QA. |
| Services | Implemented | Sanad fields persist and render; Provider/Address/Visit Type are hidden from admin input; legacy compatibility values remain internal. |
| Service Bundles | Partial | Packages retained and relabeled; legacy package controls are hidden, standard CRUD routes are functional, and attachment preview/remove is retained. Examples and browser QA remain. |
| Additional Services | Partial | Add-ons retained and relabeled; standard CRUD routes, image preview/remove, and persistence are implemented. Examples and browser QA remain. |
| Orders list | Partial | Booking-backed admin list now exposes Order Number, Customer, Service, Partner, status, priority, created date, expected completion, and payment status; the main page title is Orders. Datatable browser QA remains. |
| Assignment | Review | Authenticated browser QA verified `/booking-assign-form/1`: Suggested, Auto, and Manual modes, Partner Information Card metrics, partner/employee controls, and the reassignment-reason field rendered without errors. Negative validation submission remains untested. |
| Partner performance | Review | Aggregate and service-specific metrics are stored and used by assignment. The dedicated `/sanad/partner-performance` admin view renders the required metric table and empty state; a populated-row check remains because the local dataset has no performance records. |
| Centralized pricing | Partial | Service Government Fee and Service Fee are admin-owned; catalog write screens and the service-save API are restricted to admin roles. Browser visual QA remains; authorization probes passed. |
| Quality Control | Implemented | Customer Complaint, Escalation, SLA Violation, and Customer Feedback issue types can be created, filtered, and listed. |
| Geography modules | Implemented for V1 governance | Cities, Regions, Districts, and Price Lists remain in code/data but are disabled in admin navigation. |
| E-commerce modules | Implemented for V1 governance | Product Categories, Products, Product Approvals, Store, and product Orders remain in code/data but are disabled in admin navigation. |
| Working Hours | Review | Working Hours remains available on the Sanad employee operational profile; authenticated browser QA rendered the field on `/handyman/create` without an application error. |

## Migrations applied locally

- `2026_08_10_000001_add_sanad_fields_to_categories_table`
- `2026_08_10_000006_add_sanad_order_operations_to_bookings_table`
- `2026_08_10_000007_add_sanad_quality_type_to_qualitycontrols_table`
- `2026_08_10_000008_add_sanad_partner_performance_to_users_table`
- `2026_08_10_000009_backfill_sanad_catalog_names`

## Verification executed

- PHP lint passed for touched controllers, models, resources, and migrations.
- Local `php artisan migrate` passed.
- `php artisan migrate:status` confirms all Sanad migrations are applied.
- `php artisan view:clear` passed.
- `git diff --check` is clean for the current implementation changes.
- Local Laravel server probes reached all affected admin routes and correctly returned authentication redirects rather than route-not-found responses.

## Known verification gaps

- The repository has no `tests/` directory, so `php artisan test` cannot run its configured test paths.
- Full `php artisan view:cache` passes after adding compatibility views for the shared backend header, return-back control, and the legacy invoice `app-layout` alias.
- Browser-user visual QA was run on 2026-08-10 against the local authenticated admin panel. All catalog and operational smoke routes loaded without Whoops, exception, 404, 500, or expired-page errors.
- The rendered Service create form was checked for computed visibility: Provider, Address, Visit Type, Price Type, Price, Discount, and Duration are not visible; Sanad fields are visible. Fee and terms labels now match the PDF wording.
- Browser upload controls do not expose a supported file-selection operation in the connected browser surface, so replacement-upload and explicit-remove clicks remain a manual QA gap; authenticated HTTP media-retention probes passed.
- Public API probes returned HTTP 200 for `category-list`, `service-list`, and `subcategory-list`; captured payloads contain the new resource keys and legacy compatibility keys. Authenticated temporary Sanctum API write probes for `category-save` and `service-save` also returned HTTP 200; the QA token was revoked after verification.
- Temporary authenticated Sanctum API write probes returned HTTP 200 for `category-save` and `service-save`, with success messages; the temporary token was revoked immediately afterward.
- Using the seeded local admin account, authenticated probes returned HTTP 200 for Category, Service, Service Bundle, Additional Service, Orders, and Partner admin pages. The authenticated browser also rendered a persisted Category image preview from `/storage/.../qa-media.png` on the edit form; the temporary media fixture was removed afterward.
- Authenticated no-file Category update returned HTTP 302 and preserved the existing `category_image` media count, proving an edit does not clear media when no replacement is uploaded.
- Authenticated Service update returned HTTP 302 and persisted Government Entity, Government Fee, Service Fee, Terms & Conditions, and other Sanad fields without an attachment upload; existing service media count was preserved.
- Authenticated Additional Service creation initially exposed an undefined `id` bug; the controller now handles new records correctly, the QA record was created with HTTP 302 and then removed, and no test data remains.
- Authenticated Service Bundle creation exposed legacy non-null `duration` and `car_number` columns when those controls were hidden; the controller now supplies compatibility defaults, creation with an attachment returned HTTP 302, and the temporary bundle plus media were removed.
- Authenticated no-file Sub Category update returned HTTP 302, retained the parent `category_id`, and preserved the existing `subcategory_image` media count.
- The service catalog API guard was tightened so `service-save` requires an authenticated `admin` or `demo_admin`; partner/customer API tokens cannot create or mutate Sanad master services.
- The same admin-role guard now covers Category, Sub Category, Service Bundle, and Additional Service mutation controllers, including their authenticated API routes.
- Authorization QA: a real seeded Partner Sanctum token received HTTP 403 from `POST /api/service-save`; the temporary token was revoked and the attempted record was not created.
- Migration `2026_08_10_000010_create_sanad_partner_service_performances_table` is applied locally; Suggested/Auto assignment reads service-specific quality, SLA, acceptance, cancellation, speed, and completed-order metrics from it when available.
- The assignment Partner Information Card now displays service-specific score, SLA, average completion, and experience values when a Partner–Service record exists, with aggregate/booking fallbacks for legacy data.
- A standalone `/sanad/partner-performance` admin view now exposes Partner, Service, Quality Score, SLA Compliance, Acceptance, Cancellation, Average Completion, and Completed Orders. The authenticated browser smoke check rendered the table and empty state without errors.
- A fresh authenticated browser smoke pass covered `/home`, Category, Sub Category, Service, Service Bundle, Additional Service, Orders, Partner, Complaints, and Partner Performance list/create routes. Every route loaded without application errors; the rendered pages exposed the expected Sanad labels, including Category Icon/Display Order, Service fees and master-data fields, Service Bundle, Additional Service, and Orders status/payment columns.
- The assignment page `/booking-assign-form/1` was also exercised in the authenticated browser. It rendered the Partner Information Card and all three assignment modes with the expected Partner and Employee controls. The dashboard navigation audit found Geography and E-Commerce modules absent as required; Working Hours remains preserved but was not separately opened during this pass.
- The assignment controller enforces a reassignment reason when an existing booking partner changes (`BookingController::bookingAssigned`). The local browser fixture exposes only one partner, so the negative partner-change submission could not be exercised without creating test data; it remains an explicit QA gap.
- Root-cause media QA found `public/storage` was a stale symlink to `/Users/xain/Documents/GitHub/backup_kangoo/storage/app/public`, so saved media URLs returned broken images in this project. The link now targets the current project storage path, and `GET /storage/9/hair_spa.png` returned HTTP 200 with `image/png`. Category and related image upload validation now reject non-image files; category edit rendering also avoids treating invalid legacy media as an `<img>`.

## Final audit gate

The implementation must not be declared fully aligned until the remaining Partial rows are manually tested in the admin browser and the Notion QA tasks are marked Passed with evidence.

## QA Evidence Matrix

| Workflow | Evidence completed | Remaining gate |
| --- | --- | --- |
| Category edit without replacement image | Authenticated HTTP update returned 302; existing image media count stayed `1`. | Browser click-through and explicit remove/replacement upload. |
| Sub Category edit without replacement image | Authenticated HTTP update returned 302; parent category and image media count stayed intact. | Browser click-through and explicit remove/replacement upload. |
| Service Sanad-field edit | Authenticated HTTP update returned 302; Government Entity, fees, Terms & Conditions, and compatibility name values persisted. Browser confirmed visible Sanad fields and hidden removed legacy controls. | Browser file replacement/removal. |
| Additional Service create | Authenticated HTTP create returned 302 after fixing missing-id handling; temporary record removed. | Browser create/edit/media replacement. |
| Service Bundle create | Authenticated multipart create returned 302 after supplying legacy non-null compatibility defaults; temporary record and media removed. | Browser create/edit/media replacement. |
| Public catalog API reads | Category, Sub Category, and Service list endpoints returned HTTP 200 with new and legacy keys. | Authenticated API write/detail snapshots. |
| Orders and Partner admin pages | Authenticated HTTP probes returned HTTP 200; Sanad labels and performance fields rendered in HTML. | Browser visual and DataTables interaction. |

Additionally, an authenticated HTML assertion checked that all required Service Sanad field names are present and that legacy Service/Bundle controls carry the hidden legacy-field classes in the rendered admin markup.
