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
| Admin dashboard | Operational summary, requests, partners, employees, payments, alerts, pending actions | Implemented locally | Manual admin UAT walkthrough |
| Admin orders | View, filter, inspect, assign, update, monitor all customer requests | Implemented locally | Manual admin lifecycle UAT |
| Service catalog | Categories, services, packages, add-ons, pricing inputs, availability | Implemented locally using Kangoo structures | Manual service-management UAT; deeper bilingual/government-field review |
| Partner dashboard | Partner operations, assigned orders, employees, workload, finance | Implemented locally | Manual partner UAT |
| Partner services | Partners enable/disable Sanad services under Sanad rules | Implemented/partially verified | Manual partner permission and service publishing review |
| Staff portal | Employee dashboard/tasks/documents/checklist/chat/Buzz | Implemented locally | Manual employee UAT |
| Customer portal | Requests, documents, payments, AI, privacy | Implemented locally | Manual customer UAT |
| Buzz | Normal/Urgent/Critical notifications and acknowledgement | Implemented foundation | Full UI seen/opened/action-completed behavior review |
| Documents | Request docs, personal vault, privacy, approval, audit behavior | Implemented foundation | 48-hour deletion and customer download-before-deletion policy review |
| AI | Knowledge base, request summaries, status explanation, escalation | Implemented foundation | Proactive AI and fallback-to-human scenario review |
| Payments | Invoices, payment status, refunds, wallet-compatible structure | Implemented locally | Role-specific financial permission UAT |
| Mobile apps | Customer/admin/partner/employee apps aligned with dashboards | Source/build QA passed | Manual device/emulator role walkthrough |
| Cross-platform sync | Same statuses, workflow, chat, docs, payments, AI across web/mobile | Implemented foundation | End-to-end role scenario testing |
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

## Recommended Next Work Order

1. Run a manual local web UAT walkthrough for Admin, Partner, Employee, and Customer roles.
2. Run manual device/emulator walkthroughs for the customer and operations mobile apps.
3. Verify deeper policy scenarios: document retention/download behavior, proactive AI/fallback escalation, role-specific finance permissions, and cross-platform lifecycle sync.
4. Keep production deployment on hold until Dokploy applies the merged production code and live Sanad routes return 200.
5. After deployment is fixed, run the same Sanad QA gates against the deployed environment before client-facing UAT.

## Current Decision

The current local implementation is ready for structured internal UAT. It is not yet ready to be accepted as production-complete because production deployment remains blocked and manual role/device UAT still needs to be completed.

The frontend web cleanup requested by the previous comparison report is complete in local QA according to the current automated evidence.
