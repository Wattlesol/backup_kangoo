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

## Pre-UAT Checks

| Check | Expected Result | Status |
| --- | --- | --- |
| Backend deployment is available | Dashboard opens successfully | Pending |
| Database migrations are applied | Sanad tables/fields are available | Pending |
| Demo users are confirmed | Admin, partner, employee, and customer accounts are available | Pending |
| Mobile apps point to UAT API URL | Apps load Sanad data from shared backend | Pending |
| Integrated QA is run against UAT URL | `scripts/sanad_integrated_qa.sh` passes against deployed API | Pending |

## Admin Acceptance Testing

| Step | Expected Result | Status |
| --- | --- | --- |
| Sign in as admin | Admin reaches dashboard successfully | Pending |
| Open Sanad dashboard | Sanad KPIs, request overview, financial indicators, and operational alerts are visible | Pending |
| Open request queue | Admin can view Sanad requests with lifecycle, priority, role, and payment context | Pending |
| Open request detail | Admin can review customer, partner, employee, document, billing, quality, Buzz, and chat sections | Pending |
| Update request lifecycle | Request stage and priority update successfully | Pending |
| Assign employee/team | Assignment saves and appears on the request detail | Pending |
| Review document vault | Admin can view and approve/verify submitted documents | Pending |
| Use Buzz alerts | Admin can create and acknowledge Buzz alerts | Pending |
| Use AI console | Admin can add knowledge and ask an AI support question | Pending |
| Review Financial Center | Payments, wallet, VAT, commission, refunds, invoices, and settlement data are accessible | Pending |

## Partner Acceptance Testing

| Step | Expected Result | Status |
| --- | --- | --- |
| Sign in as partner/provider | Partner reaches assigned operational view successfully | Pending |
| View assigned requests | Partner sees only relevant service requests | Pending |
| Update partner action/status | Partner action is saved and reflected in request timeline | Pending |
| Review service restrictions | Partner view follows allowed service and role boundaries | Pending |
| Review financial data | Partner wallet/settlement/payment visibility is scoped correctly | Pending |
| Use mobile operations screen | Partner can view Sanad request data in the admin/provider mobile app | Pending |
| Confirm privacy boundaries | Partner cannot access unrelated customer/admin-only documents or data | Pending |

## Employee Acceptance Testing

| Step | Expected Result | Status |
| --- | --- | --- |
| Sign in as employee | Employee reaches assigned operational view successfully | Pending |
| Review profile/permissions | Employee role, capacity, and operational status are visible where applicable | Pending |
| View assigned work | Employee sees assigned Sanad requests/tasks only | Pending |
| Update operational status | Employee status/action is saved and visible to operations/admin | Pending |
| Use mobile operations screen | Employee can review assigned Sanad workflow in the admin/provider mobile app | Pending |
| Confirm privacy boundaries | Employee cannot access admin-only, partner-only, or unrelated customer data | Pending |

## Customer Acceptance Testing

| Step | Expected Result | Status |
| --- | --- | --- |
| Sign in as customer | Customer reaches customer dashboard/mobile app successfully | Pending |
| View request list | Customer sees their own Sanad requests only | Pending |
| Open request detail | Customer can review lifecycle, payment, document, Buzz, and chat context intended for customer visibility | Pending |
| Upload/review documents | Customer document visibility follows privacy rules | Pending |
| Use chat | Customer can send/receive request-related messages | Pending |
| Review Buzz alert visibility | Customer sees only relevant Buzz alerts | Pending |
| Review payment visibility | Customer payment/wallet visibility is scoped correctly | Pending |
| Use customer mobile Sanad screen | Customer mobile app loads Sanad foundation and request data from the shared API | Pending |

## UAT Sign-Off

| Acceptance Area | Sign-Off Owner | Result | Notes |
| --- | --- | --- | --- |
| Admin acceptance | Client/admin reviewer | Pending |  |
| Partner acceptance | Client/partner reviewer | Pending |  |
| Employee acceptance | Client/operations reviewer | Pending |  |
| Customer acceptance | Client/customer reviewer | Pending |  |

After all four acceptance areas are approved, move the remaining Notion UAT tasks from `Ready for QA` to `QA Passed`, then begin release build/signing work.
