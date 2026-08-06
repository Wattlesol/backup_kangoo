#!/usr/bin/env bash
set -euo pipefail

export PATH="/usr/local/bin:/opt/homebrew/bin:/usr/bin:/bin:/usr/sbin:/sbin:$PATH"

PROJECT_NAME="${SANAD_QA_PROJECT_NAME:-kangoo-sanad-qa}"
APP_PORT="${SANAD_QA_APP_PORT:-8092}"
MYSQL_PUBLIC_PORT="${SANAD_QA_MYSQL_PORT:-3307}"
DB_DATABASE="${SANAD_QA_DB_DATABASE:-kangoo_sanad_qa}"
DB_USERNAME="${SANAD_QA_DB_USERNAME:-kangoo_qa}"
DB_PASSWORD="${SANAD_QA_DB_PASSWORD:-kangoo_qa_password}"
MYSQL_ROOT_PASSWORD="${SANAD_QA_MYSQL_ROOT_PASSWORD:-kangoo_qa_root_password}"
APP_KEY="${SANAD_QA_APP_KEY:-base64:vZVaJZ5MNutp9lcMvbAxfqTL/e8SvWyNs/0ugjYxjEQ=}"
BASE_URL="http://127.0.0.1:${APP_PORT}/api"

if ! command -v docker >/dev/null 2>&1; then
  echo "FAIL docker is required for local SQL QA." >&2
  exit 1
fi

if [[ ! -f database/dumps/kangoo_sa.sql.gz ]]; then
  echo "FAIL missing database/dumps/kangoo_sa.sql.gz." >&2
  exit 1
fi

echo "Starting local Sanad SQL QA environment..."
echo "Project: ${PROJECT_NAME}"
echo "API: ${BASE_URL}"
echo "MySQL: 127.0.0.1:${MYSQL_PUBLIC_PORT}/${DB_DATABASE}"

APP_NAME="Kangoo Sanad QA" \
APP_ENV=local \
APP_KEY="${APP_KEY}" \
APP_DEBUG=true \
APP_PORT="${APP_PORT}" \
APP_URL="http://127.0.0.1:${APP_PORT}" \
ASSET_URL="http://127.0.0.1:${APP_PORT}" \
LOG_CHANNEL=stderr \
LOG_LEVEL=debug \
DB_CONNECTION=mysql \
DB_HOST=mysql \
DB_PORT=3306 \
DB_DATABASE="${DB_DATABASE}" \
DB_USERNAME="${DB_USERNAME}" \
DB_PASSWORD="${DB_PASSWORD}" \
MYSQL_ROOT_PASSWORD="${MYSQL_ROOT_PASSWORD}" \
MYSQL_PUBLIC_PORT="${MYSQL_PUBLIC_PORT}" \
CACHE_DRIVER=file \
SESSION_DRIVER=file \
QUEUE_CONNECTION=sync \
RESTORE_DB_ON_BOOT=true \
RESTORE_DB_IF_EMPTY=false \
RESTORE_DB_FRESH=true \
RESTORE_DB_DUMP=database/dumps/kangoo_sa.sql.gz \
RUN_MIGRATIONS=true \
RUN_SEEDERS=false \
docker compose -p "${PROJECT_NAME}" -f docker-compose.yml up -d --build --force-recreate

echo "Waiting for local QA API..."
for attempt in $(seq 1 90); do
  if /usr/bin/curl -fsS "http://127.0.0.1:${APP_PORT}" >/dev/null 2>&1; then
    break
  fi

  if [[ "$attempt" -eq 90 ]]; then
    echo "FAIL local QA API did not become ready." >&2
    docker compose -p "${PROJECT_NAME}" -f docker-compose.yml ps >&2
    exit 1
  fi

  sleep 2
done

echo "Seeding local QA request data..."
docker compose -p "${PROJECT_NAME}" -f docker-compose.yml exec -T mysql \
  mysql -u"${DB_USERNAME}" -p"${DB_PASSWORD}" "${DB_DATABASE}" <<'SQL'
INSERT INTO bookings (
  sanad_reference,
  customer_id,
  service_id,
  provider_id,
  date,
  start_at,
  end_at,
  quantity,
  amount,
  discount,
  total_amount,
  description,
  status,
  sanad_stage,
  sanad_priority,
  address,
  duration_diff,
  type,
  created_at,
  updated_at
)
SELECT
  'SANAD-LOCAL-QA-000001',
  customer.id,
  service.id,
  provider.id,
  NOW(),
  NOW(),
  DATE_ADD(NOW(), INTERVAL 1 HOUR),
  1,
  100,
  0,
  100,
  'Local Sanad SQL QA request',
  'pending',
  'submitted',
  'normal',
  'Local Sanad QA address',
  '3600',
  'service',
  NOW(),
  NOW()
FROM
  (SELECT id FROM users WHERE user_type = 'user' ORDER BY id LIMIT 1) customer
  JOIN (SELECT id FROM users WHERE user_type = 'provider' ORDER BY id LIMIT 1) provider
  JOIN (SELECT id FROM services WHERE status = 1 ORDER BY id LIMIT 1) service
WHERE NOT EXISTS (
  SELECT 1 FROM bookings WHERE sanad_reference = 'SANAD-LOCAL-QA-000001'
);
SQL

echo "Running Sanad integrated QA against local SQL database..."
BASE_URL="${BASE_URL}" \
SANAD_TEST_EMAIL="${SANAD_TEST_EMAIL:-demo@admin.com}" \
SANAD_TEST_PASSWORD="${SANAD_TEST_PASSWORD:-12345678}" \
SANAD_REQUIRE_REQUEST=true \
scripts/sanad_integrated_qa.sh

echo "Running Sanad migration QA against local SQL database..."
scripts/sanad_migration_qa.sh

echo "Local Sanad SQL QA completed successfully."
