# Sanad Permission Audit - zain@wattlesol.com

Date: 2026-08-13
User: zain@wattlesol.com
User ID: 6
Context: admin / direct Sanad employee
Role: handyman

## Assigned Permission Matrix

The user is a direct Sanad employee because `provider_id` is null.

Granted modules:

- Dashboard: read
- Orders: read, write
- Assignment: read, write
- Request Documents: read, write
- Customer Chat: read, write
- Quality Control: read, write
- Partner: read, write
- Employee: read, write
- Customer: read, write, delete
- Payments: read
- Documents: read, write
- AI Tools: read, write

Not granted:

- Service Catalog
- Service Bundles
- Additional Services
- Settings/System

Workflow flags:

- review_documents
- customer_chat
- manage_employees
- view_payment_status

## Route And UI Findings

### 1. Documents module is granted but `/document` returns 403

Expected:
The user has `documents.read = true`, so the system document configuration page should be accessible if the matrix is the source of truth.

Actual:
`GET /document` returns 403.

Cause:
The route is protected by `permission:sanad legacy system access`, but the permission matrix currently derives `document list` and `providerdocument list`. It does not grant `sanad legacy system access`.

Suggested fix:
Either replace the legacy middleware on document admin routes with matrix-aware checks, or include the required legacy permission in the derived permissions for admin employees when appropriate.

### 2. Settings page is not granted but `/setting` returns 200

Expected:
The user has `settings.read = false`, so Settings/System should not be available as a system administration module.

Actual:
`GET /setting` returns 200.

Cause:
The base `setting/{page?}` route only requires authentication and verified email. It does not require the matrix `settings.read` grant or a Spatie settings permission.

Suggested fix:
Decide whether `/setting` is only personal profile/settings or system settings. If it is system settings, add matrix enforcement. If it is profile-only, rename/segment it so it does not conflict with Settings/System permissions.

### 3. Package Requests appears for Zain even though it is not a matrix module

Expected:
Sidebar should reflect modules assigned in the permission matrix.

Actual:
The rendered sidebar includes `Package Requests`.

Cause:
The sidebar adds Package Requests for every `user_type = handyman` with `booking list`; it does not check the Sanad matrix. Zain receives `booking list` from Orders read, so this legacy menu appears.

Suggested fix:
Map Package Requests into the Orders module explicitly or hide it for direct Sanad/admin employees unless a dedicated matrix module/action grants it.

### 4. Service Catalog, Service Bundles, and Additional Services correctly return 403

Expected:
These modules are not granted in the matrix.

Actual:
`GET /service` and `GET /servicepackage` return 403.

Status:
This behavior matches the matrix.

### 5. Orders, Assignment, Request Documents, AI Tools, Partner, Employee, Customer, and Payments are accessible

Expected:
These modules are granted through the matrix.

Actual:
The following routes return 200:

- `/booking`
- `/sanad/assignments`
- `/sanad/request-documents`
- `/sanad/knowledge-base`
- `/provider`
- `/handyman`
- `/user/list/all`
- `/payment`

Status:
This broadly matches the assigned matrix.

## Overall Conclusion

The matrix is partially working, but the app still has mixed enforcement:

- Sanad-specific pages mostly use `hasSanadModulePermission()`.
- Legacy admin pages mostly use Spatie middleware.
- Some legacy pages still require `sanad legacy system access`.
- Some authenticated settings/profile routes are not matrix-gated at all.
- Some sidebar items are still shown from `user_type` plus derived Spatie permissions rather than explicit matrix modules.

The next fix should centralize these cases so the visible UI and backend route access both follow the same matrix-first source of truth.
