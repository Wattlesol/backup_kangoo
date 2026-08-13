#!/usr/bin/env bash
set -euo pipefail

cd "$(dirname "$0")/.."

dump_dir="${SANAD_DB_DUMP_DIR:-database/dumps}"
mkdir -p "$dump_dir"

timestamp="$(date +%Y%m%d_%H%M%S)"
dump_file="$dump_dir/live_${timestamp}.sql.gz"

env_file="${ENV_FILE:-.env}"
if [[ -f "$env_file" ]]; then
  set -a
  # shellcheck disable=SC1090
  source "$env_file"
  set +a
fi

live_host="${LIVE_DB_HOST:-${DB_HOST:-}}"
live_port="${LIVE_DB_PORT:-${DB_PORT:-3306}}"
live_database="${LIVE_DB_DATABASE:-${DB_DATABASE:-}}"
live_username="${LIVE_DB_USERNAME:-${DB_USERNAME:-}}"
live_password="${LIVE_DB_PASSWORD:-${DB_PASSWORD:-}}"

local_host="${LOCAL_DB_HOST:-127.0.0.1}"
local_port="${LOCAL_DB_PORT:-3306}"
local_database="${LOCAL_DB_DATABASE:-${DB_DATABASE:-kangoo}}"
local_username="${LOCAL_DB_USERNAME:-root}"
local_password="${LOCAL_DB_PASSWORD:-${MYSQL_ROOT_PASSWORD:-root}}"

if [[ -z "$live_host" || -z "$live_database" || -z "$live_username" ]]; then
  echo "Missing live DB connection. Set LIVE_DB_HOST, LIVE_DB_DATABASE, and LIVE_DB_USERNAME." >&2
  exit 1
fi

echo "Dumping live database to $dump_file"
MYSQL_PWD="$live_password" mysqldump \
  --host="$live_host" \
  --port="$live_port" \
  --user="$live_username" \
  --single-transaction \
  --quick \
  --skip-lock-tables \
  --skip-add-locks \
  --skip-comments \
  --column-statistics=0 \
  --no-tablespaces \
  --set-gtid-purged=OFF \
  "$live_database" | gzip > "$dump_file"

echo "Resetting local database $local_database on $local_host:$local_port"
MYSQL_PWD="$local_password" mysql \
  --host="$local_host" \
  --port="$local_port" \
  --user="$local_username" \
  -e "DROP DATABASE IF EXISTS \`$local_database\`; CREATE DATABASE \`$local_database\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

echo "Importing dump into local database"
gzip -dc "$dump_file" | MYSQL_PWD="$local_password" mysql \
  --host="$local_host" \
  --port="$local_port" \
  --user="$local_username" \
  "$local_database"

echo "Local database is synced from live: $local_database"
