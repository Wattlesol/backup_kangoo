# Sanad UAT Checklist

This checklist is for client/user acceptance testing after the Sanad backend, dashboards, APIs, and Android debug test builds are available in a shared test environment.

## Test Environment

| Item | Value |
| --- | --- |
| Backend branch | `codex/sanad-phase-1-foundation` |
| Customer mobile branch | `codex/sanad-phase-1-foundation` |
| Admin/provider mobile branch | `codex/sanad-phase-1-foundation` |
| Backend PR | https://github.com/Wattlesol/backup_kangoo/pull/1 |
| Customer mobile PR | https://github.com/Wattlesol/handyman_user_flutter_v11.13.2/pull/1 |
| Admin/provider mobile PR | https://github.com/Wattlesol/handyman_admin_flutter_app-v3.9.0/pull/1 |
| Current build gate | Android debug test APKs and integrated backend/API QA |
| Release builds | Deferred until after full system testing/UAT approval |

## Local QA Baseline

The following implementation-side gates have passed locally and should be used as the baseline before client UAT:

| Gate | Evidence | Status |
| --- | --- | --- |
| Web role UAT | `scripts/sanad_web_role_uat.sh` | Passed |
| Visible terminology and branding | `scripts/sanad_visible_terminology_qa.sh` | Passed |
| Customer-facing partner visibility | `scripts/sanad_partner_visibility_qa.sh` | Passed |
| Document retention/download policy | `scripts/sanad_document_policy_qa.sh` | Passed |
| AI fallback escalation | `scripts/sanad_ai_escalation_qa.sh` | Passed |
| Finance role permissions | `scripts/sanad_finance_permissions_qa.sh` | Passed |
| Cross-platform lifecycle contract | `scripts/sanad_cross_platform_lifecycle_qa.sh` | Passed |
| Customer mobile QA gate | `handyman_user_flutter_v11.13.2/scripts/sanad_mobile_qa.sh` | Passed |
| Operations mobile QA gate | `handyman_admin_flutter_app-v3.9.0/scripts/sanad_mobile_qa.sh` | Passed |

## Pre-UAT Checks

| Check | Expected Result | Status |
| --- | --- | --- |
| Backend deployment is available | Dashboard opens successfully | Blocked by Dokploy live container update |
| Database migrations are applied | Sanad tables/fields are available | Passed locally; pending deployed UAT confirmation |
| Demo users are confirmed | Admin, partner, employee, and customer accounts are available | Passed locally; pending deployed UAT confirmation |
| Mobile apps point to UAT API URL | Apps load Sanad data from shared backend | Ready after UAT API URL confirmation |
| Integrated QA is run against UAT URL | `scripts/sanad_integrated_qa.sh` passes against deployed API | Pending deployment |

## Admin Acceptance Testing

| Step | Expected Result | Status |
| --- | --- | --- |
| Sign in as admin | Admin reaches dashboard successfully | Passed locally; client sign-off pending |
| Open Sanad dashboard | Sanad KPIs, request overview, financial indicators, and operational alerts are visible | Passed locally; client sign-off pending |
| Open request queue | Admin can view Sanad requests with lifecycle, priority, role, and payment context | Passed locally; client sign-off pending |
| Open request detail | Admin can review customer, partner, employee, document, billing, quality, Buzz, and chat sections | Passed locally; client sign-off pending |
| Update request lifecycle | Request stage and priority update successfully | Passed locally; client sign-off pending |
| Assign employee/team | Assignment saves and appears on the request detail | Passed locally; client sign-off pending |
| Review document vault | Admin can view and approve/verify submitted documents | Passed locally; client sign-off pending |
| Use Buzz alerts | Admin can create and acknowledge Buzz alerts | Passed locally; client sign-off pending |
| Use AI console | Admin can add knowledge and ask an AI support question | Passed locally; client sign-off pending |
| Review Financial Center | Payments, wallet, VAT, commission, refunds, invoices, and settlement data are accessible | Passed locally; client sign-off pending |

## Partner Acceptance Testing

| Step | Expected Result | Status |
| --- | --- | --- |
| Sign in as partner/provider | Partner reaches assigned operational view successfully | Passed locally; client sign-off pending |
| View assigned requests | Partner sees only relevant service requests | Passed locally; client sign-off pending |
| Update partner action/status | Partner action is saved and reflected in request timeline | Passed locally; client sign-off pending |
| Review service restrictions | Partner view follows allowed service and role boundaries | Passed locally; client sign-off pending |
| Review financial data | Partner wallet/settlement/payment visibility is scoped correctly | Passed locally; client sign-off pending |
| Use mobile operations screen | Partner can view Sanad request data in the admin/provider mobile app | Source/build QA passed; device walkthrough pending |
| Confirm privacy boundaries | Partner cannot access unrelated customer/admin-only documents or data | Passed locally; client sign-off pending |

## Employee Acceptance Testing

| Step | Expected Result | Status |
| --- | --- | --- |
| Sign in as employee | Employee reaches assigned operational view successfully | Passed locally; client sign-off pending |
| Review profile/permissions | Employee role, capacity, and operational status are visible where applicable | Passed locally; client sign-off pending |
| View assigned work | Employee sees assigned Sanad requests/tasks only | Passed locally; client sign-off pending |
| Update operational status | Employee status/action is saved and visible to operations/admin | Passed locally; client sign-off pending |
| Use mobile operations screen | Employee can review assigned Sanad workflow in the admin/provider mobile app | Source/build QA passed; device walkthrough pending |
| Confirm privacy boundaries | Employee cannot access admin-only, partner-only, or unrelated customer data | Passed locally; client sign-off pending |

## Customer Acceptance Testing

| Step | Expected Result | Status |
| --- | --- | --- |
| Sign in as customer | Customer reaches customer dashboard/mobile app successfully | Passed locally; client sign-off pending |
| View request list | Customer sees their own Sanad requests only | Passed locally; client sign-off pending |
| Open request detail | Customer can review lifecycle, payment, document, Buzz, and chat context intended for customer visibility | Passed locally; client sign-off pending |
| Upload/review documents | Customer document visibility follows privacy rules | Passed locally; client sign-off pending |
| Use chat | Customer can send/receive request-related messages | Passed locally; client sign-off pending |
| Review Buzz alert visibility | Customer sees only relevant Buzz alerts | Passed locally; client sign-off pending |
| Review payment visibility | Customer payment/wallet visibility is scoped correctly | Passed locally; client sign-off pending |
| Use customer mobile Sanad screen | Customer mobile app loads Sanad foundation and request data from the shared API | Source/build QA passed; device walkthrough pending |

## UAT Sign-Off

| Acceptance Area | Sign-Off Owner | Result | Notes |
| --- | --- | --- | --- |
| Admin acceptance | Client/admin reviewer | Pending |  |
| Partner acceptance | Client/partner reviewer | Pending |  |
| Employee acceptance | Client/operations reviewer | Pending |  |
| Customer acceptance | Client/customer reviewer | Pending |  |

After all four acceptance areas are approved, move the remaining Notion UAT tasks from `Ready for QA` to `QA Passed`, then begin release build/signing work.
