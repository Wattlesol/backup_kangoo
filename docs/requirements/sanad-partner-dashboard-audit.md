# Sanad Partner Portal Audit

Source document: `docs/requirements/Sanad partner dashboard.pdf`

Audit date: 2026-08-10

## Summary

The current Provider/Partner panel is still mostly the original marketplace provider dashboard. It has some Sanad dashboard overlays and the shared request/document API work is in progress, but the Partner Portal does not yet fully match the partner-dashboard requirements.

The PDF asks for a lightweight Partner Operations Platform where partners execute Sanad-assigned orders, manage employees and workflows, communicate in request context, upload/review documents, and monitor SLA/workload/financial settlement metrics. The current panel still exposes marketplace/product/service concepts that should be hidden or repurposed for Sanad Phase 1.

## QA Login

Use this active local provider account for browser QA:

| Role | Email | Password | Local URL |
| --- | --- | --- | --- |
| Partner / Provider | `demo@provider.com` | `12345678` | `http://localhost:8000` |

The database currently has this provider user active as `Demo Provider`.

## Current Implementation Snapshot

| Area | Current Panel State | Requirement | Status |
| --- | --- | --- | --- |
| Partner dashboard | Uses legacy provider dashboard cards plus Sanad partner overview partial. Still links to bookings, services, handymen, revenue, customers, recent bookings. | Replace marketplace dashboard with operations dashboard: Total Orders, New Orders, In Progress, Completed, Delayed, Waiting for Customer, Waiting for Government, Avg SLA, CSAT, Active Employees, Workload, Monthly Revenue, Pending Settlement, Commission. | Partial |
| Dashboard widgets | Has revenue chart, top customers, recent bookings. Some Sanad overview partial exists. | Today's Tasks, Team Performance, Recent Orders, SLA Alerts, Employee Workload, Revenue Summary, Notification Center. | Partial |
| Category/Subcategory | Admin modules exist. Provider sidebar does not show category/subcategory creation directly. | Hide category and subcategory from provider; no creation/edit/delete. | Mostly aligned |
| Services | Provider API/dashboard still counts `Service::myService()`. Sidebar may expose service-related routes through permissions; provider can still have service/product-era capabilities depending permissions. | Providers cannot create public services. They may only enable/disable Sanad-offered services and set availability, execution time, employee skills, internal notes. Pricing controlled only by Sanad. | Gap |
| Bundles / Packages | Package booking links exist; admin package module was repurposed as Service Bundles. Provider bundle creation should not be public. | Package module remains internally but disabled for providers in Phase 1. | Needs verification |
| Order management | Legacy booking/orders exist. Provider e-commerce order screen still shows product orders and product totals. Sanad request detail now has document/chat workflow in admin context. | Assigned Sanad orders are the primary module with customer, service, required documents, internal notes, SLA timer, current stage, priority, employees, chat, documents. | Major gap |
| Provider actions on orders | Existing booking actions and e-commerce status update exist. | Accept, reject with reason, request missing docs, reassign employees, add internal notes, upload docs, complete stage, request admin review, mark order completed. | Gap |
| Internal employee workflow | Existing `handyman` model represents provider workers; renamed in some docs only. No visible workflow/stage model found in provider panel. | Multi-employee sequential/parallel workflow, execution order, duration, dependencies, automatic next stage. | Gap |
| Employee management | Existing Handyman CRUD exists. Labels still use Handyman in many routes/views/sidebar. | Rename Handyman to Employees; fields: name, email, phone, job title, department, skills, permissions, working hours, daily capacity, status. Remove country/city/state/address. | Major gap |
| Employee permissions | Existing Spatie permissions exist but not tailored to partner operations. | View Orders, Upload Documents, Customer Chat, Government Submission, Close Stage, Approve Stage, View Financial Data, Manage Employees. | Gap |
| Employee assignment | Existing booking-handyman mapping exists. | Single/multiple employee assignment, manual/sequential/parallel/automatic next-stage modes. | Gap |
| Monitoring | Admin request communication/document work exists; provider visibility still mainly API/request-scoped. | Provider monitors employee chat, customer chat, documents, timeline, execution history, audit logs, SLA compliance. | Partial backend, frontend gap |
| Workload management | Existing `is_available`, `last_online_time`, and dashboard employee count patterns exist. | Current orders per employee, daily capacity, available employees, workload distribution, late tasks, suggested reassignment. | Gap |
| Smart recommendations | Admin assignment module ranks partners. No provider employee recommendation engine found. | Recommend employees by skills, performance, completion time, workload, availability, accuracy. | Gap |
| Kanban operations board | No provider Kanban board found. | New, Waiting for Documents, Government Processing, Legal Review, Accounting, Quality Review, Ready for Delivery, Completed with drag/drop. | Gap |
| Employee performance | Existing handyman ratings/payouts exist. | Replace Employee Earnings with completed orders, completion time, delayed orders, rating, quality, reopened orders, SLA, productivity. | Gap |
| Financial center | Provider payout/wallet/payment routes exist. | Replace payment list with Financial Center: payments, settlements, commission, invoices, VAT, wallet, settlement history. | Partial |
| Notifications | Existing notification center exists. | Rename push notifications to Notification Center with audiences/templates/scheduled/recurring/template library. | Partial |
| Provider profile | Existing provider profile, documents, bank, address, slots exist. | Office details, commercial registration, licenses, bank/IBAN, hours, contact, supported services, branches. | Partial |
| Phase 2 backlog | Not tracked in provider code. | Branch management, AI workload balancing, OCR, government API, SLA prediction, workflow builder, advanced reporting, etc. | Not in Phase 1 |

## Immediate Phase 1 Implementation Tasks

### Backend

- Add/finish partner service availability model: Sanad service ID, provider ID, availability, estimated execution time, required skills, internal notes, active/inactive.
- Block provider-created public services and pricing updates from provider routes/API.
- Create provider order workspace endpoints around assigned `bookings`, not e-commerce product `orders`.
- Add order action persistence for accept, reject reason, missing document request, internal note, stage completion, admin review request, completion.
- Add workflow/stage tables for booking employee assignments: stage name, employee, role, order, duration, dependency group, status.
- Normalize Handyman as Partner Employee at the UI/API boundary while preserving legacy user type internally.
- Add employee operational fields if missing: job title, department, skills, working hours, daily capacity, employment status, granular permissions.
- Add workload and SLA metrics per employee.
- Add recommendation service for employee assignment using skills, performance, average completion time, workload, availability, and historical accuracy.
- Extend financial APIs to expose pending settlement, platform commission, wallet balances, VAT, invoices, and settlement history.

### Partner Frontend

- Replace marketplace dashboard cards with Sanad operations KPIs.
- Add widgets for today's tasks, team performance, SLA alerts, employee workload, revenue summary, recent orders, notification center.
- Hide provider category/subcategory/service creation, service pricing, public package creation, and product marketplace menus.
- Add Partner Orders as the primary module with assigned Sanad requests and order detail workspace.
- Add request chat/document workspace for shared thread, missing document requests, uploads, and status.
- Rename Handyman screens to Employees and remove country/city/state/address from employee create/edit forms.
- Add employee assignment UI for single, multiple, sequential, and parallel workflows.
- Add Kanban operations board with Sanad stages and drag/drop stage progression.
- Replace employee earnings screen with employee performance metrics.
- Replace payment list with Financial Center.
- Update Provider Profile to show office/commercial registration/licenses/bank/IBAN/hours/contact/supported services/branches.

## Recommended QA Order

1. Login as `demo@provider.com` and capture current sidebar/dashboard.
2. Confirm provider cannot create categories, subcategories, public services, service pricing, public bundles, or marketplace products.
3. Confirm provider can only see orders assigned to that provider.
4. Confirm provider can open an assigned request and see service, customer, required documents, SLA/current stage, chat, uploaded documents, and assigned employees.
5. Confirm provider can accept/reject, request documents, upload documents, add notes, assign employees, progress stages, request admin review, and mark completed where permitted.
6. Confirm customer/private admin threads are not leaked.
7. Confirm financial center and employee performance match Sanad labels and data.

