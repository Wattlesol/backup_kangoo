# Quick Web UI Integration Contract

Date: 2026-08-26

## Production Architecture

- Production remains Laravel and Blade. The Next.js site on port 3000 is a review fixture only.
- Blade pages receive initial data from Laravel controllers and Eloquent models.
- Blade mutations continue to use named web routes, CSRF, validation, role middleware, Spatie permissions, and Quick module permissions.
- Sanctum APIs remain the shared cross-platform contract for request lifecycle, Buzz, documents, chat, and AI.
- Legacy internal names such as `sanad_*`, `provider`, and `handyman` remain where renaming would risk schema or client compatibility. They must not be rendered as product copy.
- Public product identity is Quick / كويك. Existing stored `SANAD-*` references are presented through `Booking::quick_reference`; new references use `QUICK-*`.

## API Contract Inventory

| Capability | Read contract | Mutation contract | Scope and validation |
| --- | --- | --- | --- |
| Foundation | `GET /api/sanad/foundation` | None | Sanctum; returns Quick brand, role aliases, lifecycle, document visibility, and AI availability |
| Requests | `GET /api/sanad/requests` | `POST /api/sanad/requests/{id}/lifecycle` | `Booking::myBooking()` plus operational write authorization; stage and priority allowlists; audit log |
| Buzz | `GET /api/sanad/buzz` | `POST /api/sanad/buzz`; `POST /api/sanad/buzz/{id}/acknowledge` | Direct recipient/sender or role visibility constrained by visible request; non-admin creation requires an accessible request |
| Document vault | `GET /api/sanad/document-vault` | `POST /api/sanad/document-vault`; `POST /api/sanad/document-vault/{id}/verify` | Owner/uploader/role visibility; approved role allowlist; admin-only verification; audit log |
| Chat list | `GET /api/sanad/chat-threads` | `POST /api/sanad/chat-messages` | Thread participants plus `Booking::myBooking()`; arbitrary thread IDs are rejected; server-owned visibility intersection |
| Request communication | `GET /api/sanad/requests/{id}/communication` | `POST /api/sanad/requests/{id}/communication`; read acknowledgement endpoint | Request scope; shared/internal thread boundary; message validation |
| Document requests | Read through request communication | `POST /api/sanad/requests/{id}/document-requests` | Operations roles only; request scope; provider association check; required reason and target validation |
| AI | History through web controllers | `POST /api/sanad/ai/ask`; `POST /api/sanad/ai/knowledge` | Request scope for contextual questions; admin-only knowledge mutation; audit log |

Public landing/catalog APIs use `PublicUserResource` for Partner and Employee cards. Email, phone, street address, ownership IDs, notification timestamps, authentication identifiers, and push-player IDs are excluded. Customer-specific dashboard/service fields are returned only when an optional Sanctum identity resolves to that customer; a submitted `customer_id` alone never enables them.

The route and middleware manifest is enforced by `scripts/quick_ui_contract_qa.sh`. It covers the 17 Quick workflow endpoints plus integrated booking, profile/status, payment/transfer, partner-document, assignment, and shared media-removal contracts used by existing clients (31 protected endpoints total). It fails when a route is missing, duplicated, loses Sanctum protection, or drops the ownership/role/financial/media barriers checked in source.

## Foundation Screen Bindings

| Screen | Blade/controller source | Live behavior |
| --- | --- | --- |
| Landing | `landing-page/index.blade.php`; landing controllers/settings | Service discovery, categories, search, app links, language, theme, authentication entry |
| Authentication | `auth/*`, `landing-page/login.blade.php`, auth controllers | Admin/partner/employee login, customer login/registration/recovery, role redirect, validation, CSRF |
| Shared shell | `components/master-layout.blade.php`, `partials/_body_header.blade.php`, `partials/_body_sidebar.blade.php` | Role-aware navigation, locale, theme, notifications, profile, settings, logout |
| Shared states | shared Blade partials and CSS | Forms, tables, pagination, alerts, empty/error/loading patterns, RTL/LTR, responsive behavior |

## Admin Screen Bindings

| Screen | View and data source | Actions that must remain wired |
| --- | --- | --- |
| Operations dashboard | `sanad/dashboard.blade.php`; `SanadWebController::dashboard` | Metric filters, request navigation, role-scoped operations data |
| Request queue | `sanad/requests-index.blade.php`; `indexRequests` | Search, stage/priority/SLA filters, pagination, detail navigation |
| Request detail and QC | `sanad/request-show.blade.php`; `showRequest` | Lifecycle, partner/employee assignment, actions, payment status, document review, Buzz, chat, QC |
| Assignment workspace | `sanad/assignments.blade.php`; assignment service | Filter/rank partners, confirm assignment, preserve scoring snapshot and audit |
| Document verification | `sanad/document-queue.blade.php`; document queries | Request and partner document review, replacement/rejection reasons, retention guidance |
| Unified chat | `sanad/chat-workspace.blade.php`; snapshot controller | Conversation filters, thread selection, assignment, messages, read state, Buzz replies, document requests |
| AI operations | `sanad/ai-console.blade.php`; RAG/ingestion services | Ask, knowledge CRUD, scrape/run/status, source visibility, promotion |
| AI escalations | `sanad/ai-escalations.blade.php` | Review, delete, promote examples, request navigation |
| Partner performance | `sanad/partner-performance.blade.php` | Partner/SLA filters and performance drill-down |
| Financial center | `payment/index.blade.php`; `PaymentController` | Scoped list/DataTable, permitted status actions, invoice/history links |
| Service catalog | `service/index.blade.php` and service forms | CRUD, categories/subcategories, bilingual names, fees, documents, packages, add-ons |
| Partner directory | `provider/index.blade.php` and forms | Scoped CRUD, verification documents, status and detail navigation |
| Employee directory | `handyman/index.blade.php` and forms | Employee CRUD, operational fields, permission matrix, partner-team scope |
| Notifications/settings | notification and setting views/controllers | Notification list/counts/templates and permission-protected operational settings |

## Partner Screen Bindings

| Screen | View/controller source | Actions that must remain wired |
| --- | --- | --- |
| Dashboard | `provider/dashboard.blade.php`; provider order controller | KPIs, today work, SLA risks, workload, recent orders |
| Assigned orders | `provider/order/index.blade.php` | Scoped DataTable, filters, detail navigation |
| Order detail | `provider/order/view.blade.php` | Status/action updates, employee assignment, stage completion, documents, chat |
| Operations Kanban | `provider/kanban.blade.php` | Scoped columns and validated stage moves |
| Chat | shared chat workspace | Only assigned requests/threads; message and read actions |
| Enabled services | `provider/services.blade.php` | Toggle only catalog services allowed to the partner; no master pricing mutation |
| Workflow list | `provider/workflows/index.blade.php` | List/edit/delete partner-owned workflows |
| Workflow editor | `provider/workflows/create.blade.php` | Validated stages, order, mode, employee association |
| Employee directory | `provider/employees.blade.php` | Partner-team employees only |
| Performance | `provider/performance.blade.php` | SLA, throughput, workload, quality metrics |
| Financial center | `provider/financial.blade.php` | Partner-owned settlement/payment data only |
| Notifications | `provider/notifications.blade.php` | Partner notification center |
| Profile | `provider/profile.blade.php` | Office/profile data and partner-owned document upload |

## Employee Screen Bindings

| Screen | Shared source | Permission boundary |
| --- | --- | --- |
| Dashboard | role branch in `HomeController` and dashboard partials | Direct or partner employee scope; module-filtered shortcuts |
| Assigned work | `sanad/requests-index.blade.php` | `Booking::myBooking()`; only assigned/team-visible work |
| Request detail/actions | `sanad/request-show.blade.php` | Flags/module permissions for stages, notes, Buzz, documents, chat, payment read |
| Document queue | `sanad/document-queue.blade.php` | `request_documents` read/write and review/upload flags |
| Customer chat | shared chat workspace | `customer_chat`; request/thread visibility; no unrelated conversations |
| Payment/team views | payment and employee views | Read-only payment status unless explicitly granted; partner-team boundary |

## Customer Screen Bindings

| Screen | View/controller source | Live behavior |
| --- | --- | --- |
| Dashboard | `customer-portal/dashboard.blade.php`; customer portal controller | Real counts, recent requests, action center, timeline/activity |
| Catalog | `customer-portal/catalog.blade.php` | Search/category filters and active services |
| Service detail | `customer-portal/service-show.blade.php` | Localized service metadata, fees, requirements, related services |
| New request | `customer-portal/request-create.blade.php` | Service selection, configured documents, vault reuse, fees, validated submission |
| Request list | `customer-portal/requests-index.blade.php` | Customer-only search/filter/pagination |
| Request detail | `customer-portal/request-show.blade.php` | Progress, documents, requested uploads, messages, Buzz, payment, history |
| Document vault | `customer-portal/vault.blade.php` | Upload/analyze/confirm/cancel/edit/reminder/delete for owned vault items |
| Messages | `customer-portal/messages.blade.php` | Customer-only request threads, snapshots, send/reply/read behavior |
| Billing | `customer-portal/billing.blade.php` | Customer payment records and permitted invoice downloads |
| Support | `customer-portal/support.blade.php` | Complaint creation and customer-owned history |
| Notifications | `customer-portal/notifications.blade.php` | Customer notification list |
| Profile and AI | profile/AI views | Profile data, request-aware AI, explicit human handover |

## Verification Gates

1. `bash scripts/quick_ui_contract_qa.sh`
2. PHP syntax checks for every changed PHP file and `php artisan view:cache`
3. Existing focused privacy, finance, request-detail, catalog, chat, AI, document, and terminology checks updated for Quick branding
4. Authenticated Admin, Partner, Employee, and Customer web UAT against port 8000
5. API smoke and negative authorization tests against port 8000
6. Laravel Mix production build, RTL/LTR and light/dark manual browser checks, responsive checks
7. Notion integration tasks move to Done only when their corresponding evidence passes

## Known Baseline Issues Found on 2026-08-26

- The old UAT script assumed a fixed request ID/reference that is absent from the current local database.
- The customer new-request controller/view variable contract was broken (`$service` versus `$selectedService`/`$docs`).
- Buzz and chat API queries required stronger per-request scoping; arbitrary chat thread posting was possible.
- The AI smoke assertion depends on an external/generated answer containing exact fixture text and can fail even after the endpoint returns successfully.
- The Notion overview says 46 screens, while the task database contains 49 design tasks: Foundation 4, Admin 14, Partner 13, Employee 6, Customer 12.

## Screen Implementation Coverage (49 Planned Screens)

The counts below mean that a planned route/view and its static integration contract exist. They do not, by themselves, prove customer approval, live browser behavior, every UI state, or end-to-end role authorization. Those remain separate gates in the verification list above.

1. **Foundation (4/4 implemented)**: Landing Page, Authentication, Shared Shell, UI Patterns, with light/dark and RTL/LTR implementation hooks.
2. **Admin Portal (14/14 implemented)**: Operations dashboard, request queue, request detail & QC, partner/employee assignment workspace, document queue/verification, unified chat, AI operations console, AI escalations & monitoring, partner performance, financial center, service catalog, partner directory/detail, employee directory/detail, operational settings.
3. **Partner Portal (13/13 implemented)**: Partner dashboard, assigned-order queue, order detail & actions, operations Kanban, chat workspace, enabled services, employee workflow list, employee workflow editor, employee directory, performance analytics, financial center, notification center, partner profile & verification.
4. **Employee Portal (6/6 implemented)**: Employee dashboard, assigned-work queue, request detail & actions, document-request queue, customer chat workspace, permitted payment and team views.
5. **Customer Portal (12/12 implemented)**: Customer dashboard, service catalog, service detail, new request flow, request list, request detail & progress timeline, digital document vault, messages, billing & payments, complaints & support, notifications, customer profile & AI entry points.

### Quality Automation
- `scripts/quick_ui_manifest_qa.sh`: 100% pass across all 49 screens and cross-cutting navigation contracts.
- `scripts/quick_ui_contract_qa.sh`: Validates 31 Sanctum workflow endpoints plus booking, profile, rating, payment/transfer, partner-document, assignment, and media-removal ownership protections.
- `scripts/quick_terminology_static_qa.sh`: Ensures zero visible legacy terminology leaks in user-facing views.
- `scripts/quick_catalog_orders_qa.sh`: Validates category/service master data, the operational Orders columns, Service Bundles, Additional Services, and removal of obsolete catalog inputs.
- `npm run build` (quick-ui-review Next.js) and `npm run production` (backup_kangoo Laravel Mix): clean builds.

### Outstanding Evidence Gates

- Explicit customer approval for every screen task remains required by the Notion workflow before those design tasks can be considered Done.
- Live authenticated browser review at desktop/tablet/mobile widths remains required for all four roles.
- Live API smoke and negative-authorization tests require a reachable Laravel runtime and database.
