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
| Admin/Provider Mobile App | Sanad operations workflow commit `16ed2d1`, `nb_utils` compatibility pin, and targeted Flutter analysis passed |
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
PATH=/Users/xain/development/flutter/bin:$PATH flutter analyze lib/screens/sanad/my_sanad_screen.dart lib/model/sanad_models.dart lib/network/rest_apis.dart
PATH=/Users/xain/development/flutter/bin:$PATH flutter analyze lib/screens/sanad/sanad_operations_screen.dart lib/model/sanad_models.dart lib/networks/rest_apis.dart
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

## Mobile Build Results

The following Android debug builds passed locally using Flutter `3.27.4`, Java `17.0.19`, Android SDK `35.0.0`, and accepted Android SDK licenses:

| Application | Command | Artifact |
| --- | --- | --- |
| Customer mobile app | `JAVA_HOME=/usr/local/opt/openjdk@17 PATH=/usr/local/opt/openjdk@17/bin:/Users/xain/development/flutter/bin:$PATH flutter build apk --debug` | `/Users/xain/Documents/kangoo/handyman_user_flutter_v11.13.2/build/app/outputs/flutter-apk/app-debug.apk` |
| Admin/provider mobile app | `JAVA_HOME=/usr/local/opt/openjdk@17 PATH=/usr/local/opt/openjdk@17/bin:/Users/xain/development/flutter/bin:$PATH flutter build apk --debug` | `/Users/xain/Documents/kangoo/handyman_admin_flutter_app-v3.9.0/build/app/outputs/flutter-apk/app-debug.apk` |

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
- Flutter is installed locally at `/Users/xain/development/flutter`, but it is not currently on the default shell `PATH`.
- Android debug APK builds now pass when `JAVA_HOME` is set to Homebrew OpenJDK 17 and `/Users/xain/development/flutter/bin` is added to `PATH`.
- Xcode 26.6 and CocoaPods 1.16.2 are available locally. The customer iOS no-codesign build reached CocoaPods, but `pod install` was blocked by the local pod/spec installation state after a GoogleSignIn podspec cache error and subsequent repo update.
- Full mobile release AAB/IPA builds are deferred until after system testing/UAT, when production release signing credentials, CocoaPods, and iOS signing setup are finalized.

## Remaining QA Gates

The backend, web dashboards, API smoke tests, and Android debug APK test builds are complete on the local environment. Release signing/store packaging is intentionally deferred until after system testing. The active QA gates are now integrated runtime review and client UAT:

| Gate | Status | Reason |
| --- | --- | --- |
| Client UAT acceptance | Ready for QA | Final sign-off requires client review of the deployed environment and Android debug test builds. |
| Cross-platform runtime synchronization | Ready for QA | API contracts are verified and mobile source is wired to the shared endpoints. Needs integrated runtime testing across backend, dashboards, and mobile test builds. |

## Deferred Release Prep

These items are not blockers for current system testing and should be handled after UAT approval:

| Release Item | Deferred Reason |
| --- | --- |
| Customer Android release APK/AAB | Requires production keystore. Current `key.properties` points to `/Users/apple/upload-keystore.jks`, which is not present in this workspace. |
| Admin/provider Android release APK/AAB | Debug APK builds pass. Release build cleanup can be handled with final release signing/versioning work after system testing. |
| iOS IPA builds | Require final Apple signing/team setup and clean CocoaPods install in the release environment. |
