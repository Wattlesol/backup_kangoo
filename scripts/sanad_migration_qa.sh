#!/usr/bin/env bash
set -euo pipefail

export PATH="/usr/local/bin:/opt/homebrew/bin:/usr/bin:/bin:/usr/sbin:/sbin:$PATH"

PROJECT_NAME="${SANAD_QA_PROJECT_NAME:-kangoo-sanad-qa}"
DB_DATABASE="${SANAD_QA_DB_DATABASE:-kangoo_sanad_qa}"
DB_USERNAME="${SANAD_QA_DB_USERNAME:-kangoo_qa}"
DB_PASSWORD="${SANAD_QA_DB_PASSWORD:-kangoo_qa_password}"

mysql_exec() {
  docker compose -p "${PROJECT_NAME}" -f docker-compose.yml exec -T mysql \
    mysql -N -B -u"${DB_USERNAME}" -p"${DB_PASSWORD}" "${DB_DATABASE}" "$@"
}

assert_table() {
  local table="$1"
  local count
  count="$(mysql_exec -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='${DB_DATABASE}' AND table_name='${table}';" 2>/dev/null)"
  if [[ "$count" != "1" ]]; then
    echo "FAIL missing table: ${table}" >&2
    exit 1
  fi
  echo "PASS table exists: ${table}"
}

assert_column() {
  local table="$1"
  local column="$2"
  local count
  count="$(mysql_exec -e "SELECT COUNT(*) FROM information_schema.columns WHERE table_schema='${DB_DATABASE}' AND table_name='${table}' AND column_name='${column}';" 2>/dev/null)"
  if [[ "$count" != "1" ]]; then
    echo "FAIL missing column: ${table}.${column}" >&2
    exit 1
  fi
  echo "PASS column exists: ${table}.${column}"
}

assert_migration() {
  local migration="$1"
  local count
  count="$(mysql_exec -e "SELECT COUNT(*) FROM migrations WHERE migration='${migration}';" 2>/dev/null)"
  if [[ "$count" != "1" ]]; then
    echo "FAIL migration not recorded: ${migration}" >&2
    exit 1
  fi
  echo "PASS migration recorded: ${migration}"
}

if ! docker compose -p "${PROJECT_NAME}" -f docker-compose.yml ps mysql >/dev/null 2>&1; then
  echo "FAIL local QA MySQL container is not available. Run scripts/sanad_local_sql_qa.sh first." >&2
  exit 1
fi

echo "Running Sanad migration QA against ${PROJECT_NAME}/${DB_DATABASE}..."

docker compose -p "${PROJECT_NAME}" -f docker-compose.yml exec -T app php artisan migrate --force >/tmp/sanad-migrate-second-run.log
if ! grep -Eq 'Nothing to migrate|INFO' /tmp/sanad-migrate-second-run.log; then
  echo "FAIL second migration run did not complete cleanly." >&2
  cat /tmp/sanad-migrate-second-run.log >&2
  exit 1
fi
echo "PASS second migration run completed cleanly"

assert_migration "2026_08_05_000001_add_sanad_fields_to_bookings_table"
assert_migration "2026_08_05_000002_create_sanad_foundation_tables"
assert_migration "2026_08_05_000003_add_sanad_fields_to_services_table"
assert_migration "2026_08_05_000004_create_sanad_request_actions_table"
assert_migration "2026_08_05_000005_add_sanad_employee_fields_to_users_table"

assert_column bookings sanad_reference
assert_column bookings sanad_stage
assert_column bookings sanad_priority
assert_column bookings sla_due_at
assert_column bookings assigned_by
assert_column bookings assigned_at
assert_column bookings escalated_at
assert_column bookings closed_at

assert_column services name_ar
assert_column services name_en
assert_column services government_entity
assert_column services required_documents
assert_column services estimated_completion_time
assert_column services government_fee
assert_column services service_fee
assert_column services service_instructions
assert_column services terms_and_conditions
assert_column services partner_availability_notes
assert_column services required_employee_skills

assert_column users sanad_department
assert_column users sanad_job_title
assert_column users sanad_employee_status
assert_column users sanad_permissions
assert_column users sanad_working_hours
assert_column users sanad_daily_capacity

assert_table sanad_ai_interactions
assert_table sanad_ai_knowledge_items
assert_table sanad_audit_logs
assert_table sanad_buzz_alerts
assert_table sanad_chat_messages
assert_table sanad_chat_threads
assert_table sanad_document_vault_items
assert_table sanad_request_actions

echo "Sanad migration QA completed successfully."
