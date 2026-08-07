# Sanad Requirements Comparison Report

Date: 2026-08-07  
Environment audited: Local QA at `http://127.0.0.1:8092`  
Database audited: `kangoo_sanad_qa` on `127.0.0.1:3307`  
Current backend/web branch: `codex/sanad-phase-1-foundation`

## Executive Summary

The Sanad backend, database, authenticated web dashboards, local SQL restore flow, request lifecycle, Buzz alerts, secure chat, document vault, AI foundation, payments/wallet contracts, visible web terminology, and customer-facing partner visibility rules have been implemented and verified in the local QA environment.

The previous audit found visible Kangoo, Booking, Handyman, and Provider marketplace terminology in the local web UI. That evidence is now outdated. The current local QA gates pass for visible Sanad terminology, partner visibility, and authenticated Sanad web routes.

Production deployment is still not a valid acceptance environment because Dokploy has not applied the merged production code to the running container. Local QA remains the authoritative technical verification environment until the deployment worker/build issue is resolved.

## Documents Compared

| Document | Purpose |
| --- | --- |
| `Sanad Admin.pdf` | Admin dashboard, orders, services, partner assignment, pricing, performance, quality, AI, documents |
| `Sanad partner dashboard.pdf` | Partner operations dashboard, services, assigned orders, employees, workflow, finance, notifications |
| `Sanad Staff Portal .pdf` | Employee/staff execution dashboard, assigned tasks, documents, checklist, chat, Buzz, payment visibility |
| `Sanad User Portal.pdf` | Customer dashboard, service catalog, request creation/tracking, documents, payments, notifications, AI |
| `Genera notes.pdf.pdf` | Cross-platform alignment between dashboards and mobile apps, shared workflows, AI, payments, notifications |
| `Sanad-WBS-Simple.pdf` / `sanad-upgrade-wbs.pdf` | Approved WBS and delivery structure |

## Current Verified Implementation

| Area | Current Status | Evidence |
| --- | --- | --- |
| Local SQL restore and migrations | Passed | `scripts/sanad_local_sql_qa.sh` rebuilds local app/MySQL, restores dump, runs migrations |
| Migration idempotency and schema | Passed | `scripts/sanad_migration_qa.sh` verifies Sanad migrations, clean second migrate run, required tables/columns |
| Sanad API foundation | Passed locally | `scripts/sanad_integrated_qa.sh` validates Sanad foundation contracts against `http://127.0.0.1:8092/api` |
| Request lifecycle update | Passed locally | Integrated QA updates a local Sanad request lifecycle and audit trail |
| Buzz workflow | Passed locally | Integrated QA creates, lists, and acknowledges Buzz alerts |
| Document vault | Passed locally | Integrated QA creates and verifies document vault items |
| Secure chat | Passed locally | Integrated QA creates chat thread/messages and validates role-aware access |
| Sanad AI foundation | Passed locally | Integrated QA creates knowledge items and asks AI |
| Payment/wallet contracts | Passed locally | Integrated QA checks payment list, gateways, wallet history, top-up, and balance |
| Authenticated web routes | Passed locally | `scripts/sanad_web_sql_qa.sh` passed login, dashboard, requests, request detail, AI, and payment pages |
| Local web role UAT | Passed locally | `scripts/sanad_web_role_uat.sh` passed Admin, Partner, Employee, and Customer route/content checks |
| Request detail frontend sign-off gate | Passed locally | `scripts/sanad_request_detail_frontend_qa.sh` verifies admin, partner, and employee request detail sections, role controls, Buzz, documents, chat, billing, lifecycle, and privacy markers |
| Customer request frontend sign-off gate | Passed locally | `scripts/sanad_customer_frontend_qa.sh` verifies customer request detail Sanad terminology, operations/privacy messaging, payment summary markers, and absence of direct partner/employee profile labels |
| Service catalog frontend sign-off gate | Passed locally | `scripts/sanad_service_catalog_frontend_qa.sh` verifies admin service catalog, Sanad bilingual/government metadata fields, partner terminology, and partner service catalog visibility |
| Visible web terminology | Passed locally | `scripts/sanad_visible_terminology_qa.sh` passed against `http://127.0.0.1:8092` |
| Customer-facing partner visibility | Passed locally | `scripts/sanad_partner_visibility_qa.sh` passed against `http://127.0.0.1:8092` |
| Customer mobile source/build QA | Passed source/build gate | Customer app commit `bc9ff79`; Dart analyze passed with one pre-existing map deprecation info; Android debug APK built |
| Operations mobile source/build QA | Passed source/build gate | Operations app commit `d2e6087`; targeted analyzer, locale terminology scan, and Android debug APK build passed |
| Production deployment | Blocked | `kangoo.sa` still serves old code; Sanad live routes return 404 |

## Closed Web Gaps Since Previous Audit

### 1. Visible Kangoo Branding

Client expectation: Sanad-facing web screens should not present Kangoo/Kangoo Marketplace branding.

Current result: Closed locally. Sanad local branding seed data, fallback branding, header/footer/page title paths, and visible web surfaces now pass the visible terminology QA gate.

Evidence:

| Evidence | Result |
| --- | --- |
| `scripts/sanad_visible_terminology_qa.sh` | Passed |
| `scripts/sanad_web_sql_qa.sh` | Passed |
| Notion task `Replace visible Kangoo branding in local web settings` | Done / QA Passed |

### 2. Booking Terminology

Client expectation: customer-facing and operational flows use Request/Order terminology instead of marketplace Booking labels.

Current result: Closed locally for the audited web route set. Internal model/API names may still use booking identifiers where they are not user-facing.

Evidence:

| Evidence | Result |
| --- | --- |
| `scripts/sanad_visible_terminology_qa.sh` | Passed |
| `scripts/sanad_web_sql_qa.sh` | Passed on `/sanad/dashboard`, `/sanad/requests`, `/sanad/requests/1`, `/sanad/ai`, and `/payment` |
| Notion task `Replace Booking terminology with Request/Order in visible web menus and pages` | Done / QA Passed |

### 3. Handyman Terminology

Client expectation: Sanad Staff Portal uses Employee/Staff execution terminology.

Current result: Closed locally for audited visible web labels. Internal role constants and database identifiers may still use inherited Kangoo names where they are not displayed.

Evidence:

| Evidence | Result |
| --- | --- |
| `scripts/sanad_visible_terminology_qa.sh` | Passed |
| Notion task `Replace Handyman terminology with Employee/Staff in visible web UI` | Done / QA Passed |

### 4. Provider/Partner Visibility

Client expectation:

- Customers interact with Sanad, not directly with providers.
- Partners execute assigned orders under Sanad.
- Customer-facing marketplace provider browsing, public provider profile paths, and post-job marketplace paths should not remain visible.

Current result: Closed locally for audited customer-facing web routes. Public provider/employee profile and post-job marketplace routes redirect or are blocked, customer request detail does not expose direct partner/employee contact/profile controls, and header navigation no longer exposes provider/store marketplace entry points.

Evidence:

| Evidence | Result |
| --- | --- |
| `scripts/sanad_partner_visibility_qa.sh` | Passed |
| `scripts/sanad_visible_terminology_qa.sh` | Passed |
| Notion task `Align Provider terminology and visibility with Partner portal rules` | Done / QA Passed |

### 5. Visible Terminology QA Gate

Client expectation: future changes should not reintroduce visible Kangoo marketplace terminology.

Current result: Closed locally. The visible terminology gate is implemented and run as an explicit local QA check.

Evidence:

| Evidence | Result |
| --- | --- |
| `scripts/sanad_visible_terminology_qa.sh` | Passed |
| Notion task `Add automated visible terminology QA gate` | Done / QA Passed |

## Current Requirement Coverage Matrix

| Requirement Area | Client Requirement | Current Result | Remaining Verification |
| --- | --- | --- | --- |
| Admin dashboard | Operational summary, requests, partners, employees, payments, alerts, pending actions | Implemented locally; local role-UAT passed | Manual admin reviewer sign-off |
| Admin orders | View, filter, inspect, assign, update, monitor all customer requests | Implemented locally; local role-UAT passed | Manual admin lifecycle sign-off |
| Service catalog | Categories, services, packages, add-ons, pricing inputs, availability | Implemented locally with Sanad frontend metadata QA for bilingual/government fields | Manual service-management UAT |
| Partner dashboard | Partner operations, assigned orders, employees, workload, finance | Implemented locally; local role-UAT passed | Manual partner reviewer sign-off |
| Partner services | Partners enable/disable Sanad services under Sanad rules | Implemented locally with partner service catalog frontend QA | Manual partner permission and service publishing review |
| Staff portal | Employee dashboard/tasks/documents/checklist/chat/Buzz | Implemented locally; local role-UAT passed | Manual employee reviewer sign-off |
| Customer portal | Requests, documents, payments, AI, privacy | Implemented locally; local role-UAT passed | Manual customer reviewer sign-off |
| Buzz | Normal/Urgent/Critical notifications and acknowledgement | Implemented foundation | Full UI seen/opened/action-completed behavior review |
| Documents | Request docs, personal vault, privacy, approval, audit behavior | Implemented locally with 48-hour retention default and download-before-deletion UI guidance | Manual document lifecycle reviewer sign-off |
| AI | Knowledge base, request summaries, status explanation, escalation | Implemented locally with fallback-to-human UI guidance and low-confidence escalation QA | Manual proactive AI scenario reviewer sign-off |
| Payments | Invoices, payment status, refunds, wallet-compatible structure | Implemented locally with role-scope finance UI and permission QA | Manual financial reviewer sign-off |
| Mobile apps | Customer/admin/partner/employee apps aligned with dashboards | Source/build QA passed with repeatable mobile gates and walkthrough artifacts | Manual device/emulator role walkthrough sign-off |
| Cross-platform sync | Same statuses, workflow, chat, docs, payments, AI across web/mobile | Implemented locally with lifecycle contract QA across web, customer mobile, and operations mobile | Manual end-to-end role scenario sign-off |
| Branding | Final system reflects Sanad, not Kangoo | Passed locally for audited web/mobile source gates | Production deployment and manual UAT confirmation |

## Notion Status Alignment

The following Notion tasks now reflect current QA evidence:

| Task | Current Status |
| --- | --- |
| Replace visible Kangoo branding in local web settings | Done / QA Passed |
| Replace Booking terminology with Request/Order in visible web menus and pages | Done / QA Passed |
| Replace Handyman terminology with Employee/Staff in visible web UI | Done / QA Passed |
| Align Provider terminology and visibility with Partner portal rules | Done / QA Passed |
| Add automated visible terminology QA gate | Done / QA Passed |
| Mobile app branding and terminology audit against Sanad docs | Review / Ready for QA |

## Mobile QA Evidence

| App | Evidence | Result |
| --- | --- | --- |
| Customer mobile app | `scripts/sanad_mobile_qa.sh` and `docs/sanad-customer-mobile-walkthrough.md` in `handyman_user_flutter_v11.13.2` | Passed: Sanad labels/config, walkthrough artifact checks, high-risk legacy visible string scan, customer privacy navigation scan, targeted analyzer, debug APK build already passed |
| Admin/Partner/Employee operations mobile app | `scripts/sanad_mobile_qa.sh` and `docs/sanad-operations-mobile-walkthrough.md` in `handyman_admin_flutter_app-v3.9.0` | Passed: Sanad Operations labels/config, walkthrough artifact checks, high-risk legacy visible string scan, targeted analyzer, debug APK build already passed |

## Document Policy Evidence

| Evidence | Result |
| --- | --- |
| API document vault create | Defaults `retention_until` to 48 hours when no date is supplied |
| Web request detail document form | Defaults `retention_until` to 48 hours when no date is supplied |
| Document Vault frontend | Shows Sanad document retention policy, retention date, and download-before-deletion guidance |
| `scripts/sanad_document_policy_qa.sh` | Passed source-level policy and PHP syntax checks; route-render check is enabled when a local request row is available |

## AI Escalation Evidence

| Evidence | Result |
| --- | --- |
| AI console frontend | Shows fallback-to-human support policy and human-review note for escalated interactions |
| API low-confidence question | Returns `requires_escalation=true`, `status=escalated`, and support-team fallback answer |
| `scripts/sanad_ai_escalation_qa.sh` | Passed source, API, and rendered AI console checks against local QA after syncing the image-based container view |

## Finance Permission Evidence

| Evidence | Result |
| --- | --- |
| Financial Center frontend | Shows admin, partner, employee, or customer finance scope text based on signed-in role |
| Admin payment page | Shows admin finance scope and bulk management enabled |
| Partner payment page | Shows partner finance scope, scoped view only, and hides admin bulk action controls |
| Provider payout scoping | Non-admin/non-partner fallback returns no settlement rows |
| `scripts/sanad_finance_permissions_qa.sh` | Passed source, PHP syntax, admin rendered page, and partner rendered page checks against local QA |

## Cross-Platform Lifecycle Sync Evidence

| Evidence | Result |
| --- | --- |
| Backend lifecycle source | `config/sanad.php` defines the canonical Sanad request lifecycle and API rejects invalid stages |
| Web lifecycle frontend | Request detail lifecycle form reads stages from backend Sanad config |
| Customer mobile app | Reads Sanad foundation/request APIs and displays `sanad_stage` from the shared API payload |
| Operations mobile app | Updates lifecycle through `sanad/requests/{id}/lifecycle` and uses only configured backend stages |
| `scripts/sanad_cross_platform_lifecycle_qa.sh` | Passed source-level web/customer mobile/operations mobile lifecycle contract checks |

## Request Detail Frontend QA Evidence

| Evidence | Result |
| --- | --- |
| Admin request detail | Shows operational monitoring, lifecycle, employee assignment, partner actions, admin quality control, billing, document vault, Buzz, and secure chat markers |
| Partner request detail | Shows shared request workflow, partner order actions, documents, Buzz, chat, and billing while hiding admin-only quality-control controls |
| Employee request detail | Shows shared request workflow, documents, Buzz, chat, and billing while hiding admin-only quality-control controls |
| Document policy fallback states | Retention and Download before deletion guidance stay visible even when no documents exist yet |
| Buzz fallback states | Buzz acknowledgement guidance stays visible even when no open alerts exist |
| Privacy controls | Document and chat role visibility controls are explicitly labelled with Visible to |
| `scripts/sanad_request_detail_frontend_qa.sh` | Passed against the rebuilt local SQL QA environment at `http://127.0.0.1:8092` |

## Customer Frontend QA Evidence

| Evidence | Result |
| --- | --- |
| Customer request detail | Shows Sanad Operations, Request Assigned, Assigned Support, customer price details, total amount, and request history markers |
| Customer privacy | Page explains Sanad coordinates partner and employee execution internally instead of exposing direct profile/contact controls |
| Customer terminology | Replaced inherited visible Booking History and Cancel Booking labels with Request History and Cancel Request on the audited customer detail page |
| Customer forbidden labels | QA blocks Kangoo, Provider Demo, Handyman Demo, About Provider, About Handyman, Provider Profile, Handyman Profile, Booking History, and Cancel Booking on `/booking-detail/1` |
| `scripts/sanad_customer_frontend_qa.sh` | Passed against the rebuilt local SQL QA environment at `http://127.0.0.1:8092` |

## Service Catalog Frontend QA Evidence

| Evidence | Result |
| --- | --- |
| Admin service list | Shows Sanad Service Catalog, Active Services, Packages, Add-ons, Catalog Readiness, and Partner terminology |
| Admin service form | Shows Sanad Service Master Data, English Name, Arabic Name, Government Entity, Required Documents, Sanad Service Fee, Required Employee Skills, and Partner Internal Notes / Availability |
| Partner service list | Shows Sanad Service Catalog and catalog readiness for partner service review |
| Visible terminology | Service list/form use Partner and Partner Address wording instead of visible Provider/Provider Address labels |
| `scripts/sanad_service_catalog_frontend_qa.sh` | Passed against local QA at `http://127.0.0.1:8092` |

## Recommended Next Work Order

1. Run manual reviewer sign-off for Admin, Partner, Employee, and Customer roles using the local role-UAT evidence as the baseline.
2. Run manual device/emulator walkthroughs for the customer and operations mobile apps.
3. Run final manual cross-platform role scenario sign-off.
4. Keep production deployment on hold until Dokploy applies the merged production code and live Sanad routes return 200.
5. After deployment is fixed, run the same Sanad QA gates against the deployed environment before client-facing UAT.

## Current Decision

The current local implementation is ready for structured internal UAT. Automated local web role-UAT now passes for Admin, Partner, Employee, and Customer. It is not yet ready to be accepted as production-complete because production deployment remains blocked and manual reviewer/device UAT still needs to be completed.

The frontend web cleanup requested by the previous comparison report is complete in local QA according to the current automated evidence.
