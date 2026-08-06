# Sanad Final Handover Package

## Project Summary

Sanad has been implemented by reusing the existing Kangoo Laravel backend, dashboards, APIs, database models, and Flutter applications, then extending them for Sanad roles, request workflows, financial operations, document verification, Buzz alerts, secure chat, AI assistance, and mobile-facing API contracts.

## Repositories And Branches

| Application | Repository | Branch | PR |
| --- | --- | --- | --- |
| Backend and web dashboards | `Wattlesol/backup_kangoo` | `codex/sanad-phase-1-foundation` | https://github.com/Wattlesol/backup_kangoo/pull/1 |
| Customer mobile app | `Wattlesol/handyman_user_flutter_v11.13.2` | `codex/sanad-phase-1-foundation` | https://github.com/Wattlesol/handyman_user_flutter_v11.13.2/pull/1 |
| Admin/provider mobile app | `Wattlesol/handyman_admin_flutter_app-v3.9.0` | `codex/sanad-phase-1-foundation` | https://github.com/Wattlesol/handyman_admin_flutter_app-v3.9.0/pull/1 |

## Current Backend Delivery Commits

| Commit | Delivery |
| --- | --- |
| `9c3bf2a3` | Sanad platform foundation, APIs, request data, Buzz, documents, chat, AI, audit logs |
| `90451ce3` | Role-aware Sanad dashboards |
| `6838c176` | Sanad service catalog metadata |
| `c0248bc2` | Partner request actions and operational timeline |
| `0813c7ef` | Employee operations profile and fields |
| `59fa986a` | Sanad Financial Center |
| `16ff64ba` | Admin quality control actions |
| `25f84dbd` | Buzz API visibility and validation hardening |
| `37d7d16d` | Payment and wallet API smoke fixes |
| `cd6a02ec` | Government document verification API |

## Demo Credentials

Use the seeded demo account for local/API verification:

| Role | Email | Password |
| --- | --- | --- |
| Admin | `demo@admin.com` | `12345678` |

Additional role accounts should be confirmed from the target deployment seed data before client demo.

## Demo Flow

1. Open the backend dashboard and sign in as admin.
2. Review the Sanad dashboard at `/sanad/dashboard`.
3. Open the Sanad request queue at `/sanad/requests`.
4. Open a request detail page and review lifecycle, monitoring, employee assignment, partner actions, quality control, billing, documents, Buzz alerts, and chat.
5. Create a Buzz alert, confirm it appears in monitoring, then acknowledge it.
6. Add a government verification document, approve it from the web detail screen, or verify it through the API.
7. Review the Financial Center from `/payment`, including settlements, wallet balances, VAT, commission, refunds, invoices, and transaction history.
8. Open the AI console at `/sanad/ai`, add knowledge, and ask a Sanad support question.
9. Open customer and admin/provider mobile apps and validate Sanad screens against the same API base URL.

## Verified Coverage

| Requirement Area | Evidence |
| --- | --- |
| Admin Dashboard | Role dashboard, request queue, request detail, employee oversight, quality control |
| Partner Dashboard | Partner order actions, service restrictions, financial center, wallet/settlements |
| Employee Dashboard | Employee profile fields, permissions, assignment, capacity, operational status |
| Customer Dashboard | Request list/detail, documents, Buzz, chat, payment visibility |
| Customer Mobile App | Sanad customer workflow commit `44031df` and targeted Flutter analysis passed |
| Admin/Provider Mobile App | Sanad operations workflow commit `16ed2d1` and targeted Flutter analysis passed |
| Backend/API | Sanad foundation, Buzz, document vault, chat, AI, payment/wallet, document verification |
| AI Features | AI knowledge and ask flow verified by live API smoke |
| Privacy Rules | Role-scoped requests, Buzz visibility, document visibility, wallet scoping |
| Payment/Wallet | Live authenticated smoke passed for payment list, gateways, wallet history, wallet balance, wallet top-up |
| Government Verification | Live authenticated smoke passed for document create, pending filter, and approve verification |

## Verification Commands

```bash
php -l app/Http/Controllers/API/SanadController.php
php -l app/Http/Controllers/SanadWebController.php
php -l app/Http/Controllers/PaymentController.php
php -l app/Http/Controllers/API/PaymentController.php
php -l app/Http/Controllers/API/WalletController.php
php -l app/Traits/NotificationTrait.php
php -l routes/api.php
php -l routes/web.php
php artisan route:list --name=sanad --columns=method,uri,name,action
php artisan route:list --path=api/sanad --columns=method,uri,action
php artisan route:list --name=payment --columns=method,uri,name,action
php artisan route:list --name=wallet --columns=method,uri,name,action
php artisan route:list --name=providerpayout --columns=method,uri,name,action
git diff --check
BASE_URL=http://127.0.0.1:8091/api SANAD_TEST_EMAIL=demo@admin.com SANAD_TEST_PASSWORD=12345678 scripts/sanad_api_smoke_test.sh
```

## Live Smoke Results

The following local smoke checks passed against `http://127.0.0.1:8091/api`:

| Smoke Area | Result |
| --- | --- |
| Sanad foundation metadata | Passed |
| Sanad request list contract | Passed |
| Buzz create/list/acknowledge | Passed |
| Document vault create/list | Passed |
| Chat message/thread | Passed |
| AI knowledge/ask | Passed |
| Payment list/payment gateways | Passed |
| Wallet history/wallet balance/wallet top-up | Passed |
| Government verification create/list/approve | Passed |

## Deployment Notes

1. Deploy the backend branch `codex/sanad-phase-1-foundation` after PR review.
2. Run Laravel migrations on the target database:

```bash
php artisan migrate --force
```

3. Confirm seed/demo users exist for admin, partner, employee, and customer demo flows.
4. Configure the mobile apps to the deployed API base URL.
5. Rebuild Android/iOS apps after API base URL confirmation.
6. Run the smoke script against the deployed API URL before client handover.

## Known Local Notes

- Local backend smoke testing used `http://127.0.0.1:8091`.
- Local generated Android files remain dirty in mobile repos and are not part of delivery commits.
- Full mobile APK/IPA release builds should be run in the final build environment with the required Flutter, Android, Java, and iOS signing setup.
