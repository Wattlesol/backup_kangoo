#!/usr/bin/env sh
set -eu

cd /var/www/html

if [ "${RESTORE_DB_ON_BOOT:-false}" != "true" ]; then
  exit 0
fi

DUMP_FILE="${RESTORE_DB_DUMP:-database/dumps/kangoo_sa.sql.gz}"

if [ ! -f "$DUMP_FILE" ]; then
  echo "Database restore requested, but dump file was not found: $DUMP_FILE" >&2
  exit 1
fi

DB_HOST="${DB_HOST:-mysql}"
DB_PORT="${DB_PORT:-3306}"
DB_DATABASE="${DB_DATABASE:-kangoo}"
DB_USERNAME="${DB_USERNAME:-kangoo}"
DB_PASSWORD="${DB_PASSWORD:-kangoo}"
MYSQL_ROOT_PASSWORD="${MYSQL_ROOT_PASSWORD:-${DB_ROOT_PASSWORD:-}}"

echo "Waiting for MySQL at ${DB_HOST}:${DB_PORT}..."
attempt=1
until mysqladmin ping -h"$DB_HOST" -P"$DB_PORT" -u"$DB_USERNAME" -p"$DB_PASSWORD" --silent >/dev/null 2>&1; do
  if [ "$attempt" -ge 60 ]; then
    echo "MySQL did not become ready in time." >&2
    exit 1
  fi
  attempt=$((attempt + 1))
  sleep 2
done

if [ "${RESTORE_DB_FRESH:-false}" = "true" ]; then
  if [ -z "$MYSQL_ROOT_PASSWORD" ]; then
    echo "RESTORE_DB_FRESH=true requires MYSQL_ROOT_PASSWORD or DB_ROOT_PASSWORD." >&2
    exit 1
  fi

  echo "Resetting database ${DB_DATABASE}..."
  mysql -h"$DB_HOST" -P"$DB_PORT" -uroot -p"$MYSQL_ROOT_PASSWORD" \
    -e "DROP DATABASE IF EXISTS \`${DB_DATABASE}\`; CREATE DATABASE \`${DB_DATABASE}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci; GRANT ALL PRIVILEGES ON \`${DB_DATABASE}\`.* TO '${DB_USERNAME}'@'%'; FLUSH PRIVILEGES;"
fi

if [ "${RESTORE_DB_IF_EMPTY:-true}" = "true" ]; then
  TABLE_COUNT="$(mysql -h"$DB_HOST" -P"$DB_PORT" -u"$DB_USERNAME" -p"$DB_PASSWORD" -N -B "$DB_DATABASE" -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='${DB_DATABASE}';")"
  if [ "$TABLE_COUNT" != "0" ]; then
    echo "Skipping database restore because ${DB_DATABASE} already has ${TABLE_COUNT} tables."
    exit 0
  fi
fi

echo "Restoring ${DUMP_FILE} into ${DB_DATABASE}..."
gzip -dc "$DUMP_FILE" | mysql -h"$DB_HOST" -P"$DB_PORT" -u"$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE"

echo "Database restore completed."
