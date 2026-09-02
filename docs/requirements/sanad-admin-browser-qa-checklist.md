# Sanad Admin Browser QA Checklist

Use an authenticated `admin` account against the local admin panel. Record the URL, account, date, and result for each workflow.

## Catalog

- [ ] Category: create Arabic Name, English Name, Icon, Image, Display Order, Featured, and Status; save; reopen; confirm every value and both media previews.
- [ ] Category: edit without a replacement upload; confirm existing Image and Icon remain.
- [ ] Category: explicitly remove Image and Icon; save; confirm previews are gone.
- [ ] Sub Category: create under a parent Category; reopen; confirm parent and image.
- [ ] Sub Category: edit without upload; confirm parent and image remain.
- [x] Service: confirm Arabic Name, English Name, Government Entity, Required Documents, Estimated Completion Time, Government Fees, Service Fees, Service Instructions, Terms & Conditions, Featured, Status, Category, Sub Category, and Image are visible and save correctly.
- [x] Service: confirm Provider, Address, and Visit Type are absent from the usable admin form; edit without upload and confirm existing media remains.
- [ ] Service Bundle: confirm Packages is labelled Service Bundles; create an example bundle and attach Services; reopen and test attachment preview/removal.
- [ ] Additional Service: confirm Add-ons is labelled Additional Services; create an example and test image preview/removal.

## Operations

- [ ] Dashboard: verify Total Orders, Active Services, Active Partners, Monthly Revenue, Pending Orders, In Progress Orders, Completed Orders, and Cancelled Orders.
- [ ] Orders: verify all nine required columns and the Partner terminology.
- [x] Assignment: authenticated browser QA verified Suggested, Auto, and Manual modes, the Partner Information Card, partner/employee controls, and the reassignment-reason field on `/booking-assign-form/1`. A negative validation submission was not run.
- [ ] Partner performance: verify aggregate metrics and service-specific values appear when a Partner–Service performance record exists.
- [ ] Quality Control: create and filter Customer Complaint, Escalation, SLA Violation, and Customer Feedback records.

## Governance

- [x] Confirmed the authenticated dashboard navigation does not expose Geography or E-Commerce links (Cities, Regions, Districts, Price Lists, Product Categories, Products, Product Approvals, Store, Product Orders). The authenticated employee form also visibly renders the preserved Working Hours field without an application error.
- [ ] With a Partner account, confirm master catalog create/update requests are rejected.

## Evidence

Capture screenshots of failed and passed validations, media previews before/after edits, and the final dashboard/orders/assignment states. Append results to `sanad-admin-full-coverage-report.md` and update the matching Notion task.

## Browser smoke evidence (2026-08-10)

- [x] Authenticated browser navigation completed for Category, Sub Category, Service, Service Bundle, Additional Service, Orders, Partners, and Complaints create/list routes.
- [x] No browser smoke route showed an application exception, 404, 500, or expired-page response.
- [x] Computed visibility check confirmed removed Service fields are not usable controls.
- [ ] File chooser upload/replacement and explicit media removal remain manual because the connected browser surface does not support setting local files.
