# Sanad API Smoke Test

## Purpose

Verify that the newly added Sanad foundation APIs return usable JSON contracts for the frontend and mobile apps.

## Local Runtime Used

- Backend: Laravel development server
- URL: `http://127.0.0.1:8091`
- API base: `http://127.0.0.1:8091/api`
- Database: local SQLite test database at `database/sanad_testing.sqlite`
- Test account: `demo@admin.com`

## Commands

```bash
touch database/sanad_testing.sqlite

APP_ENV=local \
APP_DEBUG=true \
DB_CONNECTION=sqlite \
DB_DATABASE=/Users/xain/Documents/kangoo/backup_kangoo/database/sanad_testing.sqlite \
CACHE_DRIVER=file \
SESSION_DRIVER=file \
QUEUE_CONNECTION=sync \
php artisan migrate --force

APP_ENV=local \
APP_DEBUG=true \
DB_CONNECTION=sqlite \
DB_DATABASE=/Users/xain/Documents/kangoo/backup_kangoo/database/sanad_testing.sqlite \
CACHE_DRIVER=file \
SESSION_DRIVER=file \
QUEUE_CONNECTION=sync \
php artisan db:seed --class=DemoUsersSeeder --force

APP_ENV=local \
APP_DEBUG=true \
APP_URL=http://127.0.0.1:8091 \
DB_CONNECTION=sqlite \
DB_DATABASE=/Users/xain/Documents/kangoo/backup_kangoo/database/sanad_testing.sqlite \
CACHE_DRIVER=file \
SESSION_DRIVER=file \
QUEUE_CONNECTION=sync \
php artisan serve --host=127.0.0.1 --port=8091

BASE_URL=http://127.0.0.1:8091/api \
SANAD_TEST_EMAIL=demo@admin.com \
SANAD_TEST_PASSWORD=12345678 \
scripts/sanad_api_smoke_test.sh
```

## Verified Endpoints

- `POST /api/login`
- `GET /api/sanad/foundation`
- `GET /api/sanad/requests`
- `GET /api/sanad/buzz`
- `POST /api/sanad/buzz`
- `POST /api/sanad/buzz/{id}/acknowledge`
- `GET /api/sanad/document-vault`
- `POST /api/sanad/document-vault`
- `GET /api/sanad/chat-threads`
- `POST /api/sanad/chat-messages`
- `POST /api/sanad/ai/knowledge`
- `POST /api/sanad/ai/ask`

## Result

All endpoints returned successful authenticated JSON responses during the smoke test.

## Frontend Contract Confirmation

The Flutter customer app now includes Sanad client methods in `lib/network/rest_apis.dart`.

The Flutter admin/provider app now includes Sanad client methods in `lib/networks/rest_apis.dart`.

Flutter runtime validation still requires Flutter to be available on PATH. Dart formatting has passed for the touched files.
