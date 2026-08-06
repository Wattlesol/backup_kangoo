# Sanad Requirements Comparison Report

Date: 2026-08-07  
Environment audited: Local QA at `http://127.0.0.1:8092`  
Database audited: `kangoo_sanad_qa` on `127.0.0.1:3307`  

## Executive Summary

The Sanad backend/API/database foundation, local SQL restore, migrations, Sanad request workflows, document vault, Buzz, chat, AI, payments/wallet contracts, and authenticated local web routes have been implemented and verified locally.

However, the current local web UI is not yet fully Sanad-branded or terminology-clean. Visible Kangoo/marketplace terminology remains across the admin shell and Sanad pages, including `Kangoo`, `Booking`, `Create Booking`, `Handyman`, `Provider List`, and related legacy labels. This contradicts the client documents and means the previous Notion terminology task was incorrectly marked as QA passed.

Production deployment is still not a valid acceptance environment because Dokploy has not applied the merged `prod` code to the running container. The comparison below is therefore based on the local QA environment, which is currently the authoritative test environment.

## Documents Compared

| Document | Purpose |
| --- | --- |
| `Sanad Admin.pdf` | Admin dashboard, orders, services, partner assignment, pricing, performance, quality, AI, documents |
| `Sanad partner dashboard.pdf` | Partner/provider operations dashboard, services, assigned orders, employees, workflow, finance, notifications |
| `Sanad Staff Portal .pdf` | Employee/staff execution dashboard, assigned tasks, documents, checklist, chat, buzz, payment visibility |
| `Sanad User Portal.pdf` | Customer dashboard, service catalog, request creation/tracking, documents, payments, notifications, AI |
| `Genera notes.pdf.pdf` | Cross-platform alignment between dashboards and mobile apps, shared workflows, AI, payments, notifications |
| `Sanad-WBS-Simple.pdf` / `sanad-upgrade-wbs.pdf` | Approved WBS and delivery structure |

## Current Verified Implementation

| Area | Current Status | Evidence |
| --- | --- | --- |
| Local SQL restore and migrations | Passed | `scripts/sanad_local_sql_qa.sh` rebuilt local app/MySQL, restored dump, ran migrations |
| Migration idempotency and schema | Passed | `scripts/sanad_migration_qa.sh` verifies Sanad migrations, second migrate run, required tables/columns |
| Sanad API foundation | Passed locally | `scripts/sanad_integrated_qa.sh` passed against `http://127.0.0.1:8092/api` |
| Request lifecycle update | Passed locally | Integrated QA updates a local Sanad request lifecycle |
| Buzz workflow | Passed locally | Integrated QA creates and acknowledges Buzz |
| Document vault | Passed locally | Integrated QA creates/verifies document vault item |
| Secure chat | Passed locally | Integrated QA creates chat thread/message |
| Sanad AI foundation | Passed locally | Integrated QA creates knowledge and asks AI |
| Payment/wallet contracts | Passed locally | Integrated QA checks payment list, gateways, wallet history/top-up/balance |
| Authenticated web routes | Passed locally | `scripts/sanad_web_sql_qa.sh` passed login, Sanad dashboard, requests, request detail, AI, payment |
| Mobile source/debug artifacts | Partially passed | Source/API bindings and debug APK artifact checks passed; visible mobile branding audit still required |
| Production deployment | Blocked | `kangoo.sa` still serves old code; Sanad live routes return 404 |

## Major Gaps Found

### 1. Visible Kangoo Branding Remains

Client expectation: Sanad Solutions branding should replace Kangoo-facing branding.

Current evidence:

| Source | Finding |
| --- | --- |
| Local database `settings.general-setting` | `site_name` is still `Kangoo` |
| Local database `app_settings` | `site_name` is still `Handyman Service` |
| Local web pages | Header/page text shows `Kangoo Sanad QA` |
| Footer/defaults | Code still contains default `Kangoo Marketplace` copy |

Result: Not complete.

### 2. Booking Terminology Remains Visible

Client expectation: customer-facing and operational flows should use Request/Order terminology, not the original marketplace `Booking` terminology.

Current evidence from authenticated local pages:

| Page | Legacy terms found |
| --- | --- |
| `/home` | `Booking`, `Create Booking`, `Total Bookings`, `Recent Bookings` |
| `/sanad/dashboard` | `Booking` terms in sidebar/admin shell |
| `/sanad/requests` | `Booking` terms in sidebar/admin shell |
| `/sanad/requests/1` | `Booking Status` visible |
| `/sanad/ai` | `Booking` terms in sidebar/admin shell |
| `/payment` | `Booking` terms in sidebar/admin shell |

Result: Not complete.

### 3. Handyman Terminology Remains Visible

Client expectation: Sanad Staff Portal uses Employee/Staff execution terminology.

Current evidence:

| Visible legacy label | Required direction |
| --- | --- |
| `Handyman` | Employee / Staff |
| `Handyman List` | Employee List / Staff List |
| `Handyman Request List` | Employee Tasks / Assigned Requests |
| `Unassigned Handyman` | Unassigned Employee |
| `Handyman Type List` | Employee Type / Staff Role list, if still needed |
| `Handyman Ratings` | Employee Performance / Staff Quality |

Result: Not complete.

### 4. Provider/Partner Visibility Needs Cleanup

Client expectation:

- Customers interact with Sanad, not directly with providers.
- Providers/partners execute assigned orders under Sanad.
- Partners cannot create public services or control customer pricing.

Current evidence:

| Visible legacy label | Risk |
| --- | --- |
| `Provider List` | Marketplace/admin language remains |
| `Provider Request List` | Needs Partner/Operations terminology |
| `Provider Type List` | Needs validation against partner model |
| `Recent Providers` | Marketplace framing remains |
| `Provider Demo` | Visible in Sanad request/admin pages |

Result: Partially complete; needs UI and permission review.

### 5. Automated QA Did Not Catch Visible Terminology

Current QA scripts verify routes, APIs, migrations, and contracts, but they did not fail when visible pages still contained legacy labels. A dedicated visible terminology QA gate is needed.

Result: QA gap identified.

### 6. Mobile Branding Is Not Fully Proven

General Notes require mobile apps to match the relevant dashboards. Current checks prove source/API bindings and debug APK existence, but do not prove that visible mobile labels are Sanad-clean.

Result: Further mobile audit needed before release builds.

## Requirements Coverage Matrix

| Requirement Area | Client Requirement | Current Result | Gap |
| --- | --- | --- | --- |
| Admin dashboard | Replace marketplace metrics with Sanad orders/services/partners/revenue/SLA metrics | Partially implemented | Legacy admin shell still shows Booking/Provider/Handyman labels |
| Admin orders | Replace Booking with Orders/Requests and add Sanad metadata | Partially implemented | Visible Booking Status and Booking menu remain |
| Services | Sanad-owned master data, bilingual names, government entity, docs, fees | Partially implemented | Migration fields exist; UI/service creation visibility needs review |
| Partner dashboard | Partner operations, assigned orders, employees, workload, finance | Partially implemented | Provider marketplace labels remain |
| Partner services | Partners enable/disable Sanad services only | Needs verification | Legacy provider service modules may still be visible |
| Staff portal | Employee dashboard/tasks/documents/checklist/chat/buzz | Partially implemented | Handyman terminology remains |
| Customer portal | Requests, documents, payments, AI, privacy | Partially implemented | Request routes exist; shell/sidebar terminology still legacy |
| Buzz | Normal/Urgent/Critical notifications and acknowledgement | Implemented foundation | Delivery/seen/opened/action-completed tracking needs full UI verification |
| Documents | Request docs vs personal vault, privacy/retention | Implemented foundation | 48-hour deletion and customer download before deletion need deeper verification |
| AI | Knowledge base, request summaries, status explanation | Implemented foundation | Proactive AI and fallback-to-human not fully proven |
| Payments | Invoices, payment status, refunds, wallet-compatible structure | Partially implemented | Role-specific financial permissions need manual verification |
| Mobile apps | Customer/provider/employee/admin apps aligned with dashboards | Partially implemented | Visible mobile terminology/branding audit still required |
| Cross-platform sync | Same statuses, workflow, chat, docs, payments, AI across web/mobile | Partially implemented | Needs end-to-end role testing after UI cleanup |
| Branding | Final system reflects Sanad, not Kangoo | Not complete | Visible Kangoo/Kangoo QA/Handyman Service remains |

## Notion Updates Made

The existing task `Sanad terminology implementation map` was changed from `Done / QA Passed` to `Blocked`, because local UI evidence contradicts completion.

New tasks added:

| Task | Status | Purpose |
| --- | --- | --- |
| Replace visible Kangoo branding in local web settings | Ready | Fix app settings, titles, header/footer/default branding |
| Replace Booking terminology with Request/Order in visible web menus and pages | Ready | Remove visible Booking labels where Sanad requires Request/Order |
| Replace Handyman terminology with Employee/Staff in visible web UI | Ready | Align Staff Portal terminology |
| Align Provider terminology and visibility with Partner portal rules | Ready | Clean customer/provider/partner visibility and labels |
| Add automated visible terminology QA gate | Ready | Prevent recurrence by failing local QA on forbidden visible labels |
| Mobile app branding and terminology audit against Sanad docs | Ready | Confirm mobile visible UI matches Sanad dashboards |

## Recommended Next Work Order

1. Fix Sanad branding data and defaults locally.
2. Clean the shared web sidebar/admin shell terminology.
3. Clean role-specific visible labels for Admin, Partner, Employee, and Customer.
4. Add a visible terminology QA script and wire it into local SQL QA.
5. Re-run local SQL QA, migration QA, web QA, and terminology QA.
6. Perform mobile visible branding/terminology audit.
7. Only after all local gates pass, retry production deployment once Dokploy worker/build issue is resolved.

## Current Decision

The current implementation is not ready for client-facing UAT because visible legacy Kangoo/Kangoo marketplace terminology remains in local web UI.

The backend/database foundations are in a good state locally, but branding and terminology cleanup must be completed before presenting the system as Sanad-compliant.
