#!/usr/bin/env bash
set -euo pipefail

cd "$(dirname "$0")/.."

env_file="${ENV_FILE:-.env}"
if [[ -f "$env_file" ]]; then
  set -a
  # shellcheck disable=SC1090
  source "$env_file"
  set +a
fi

required=(
  LIVE_DB_HOST
  LIVE_DB_DATABASE
  LIVE_DB_USERNAME
)

missing=()
for name in "${required[@]}"; do
  if [[ -z "${!name:-}" ]]; then
    missing+=("$name")
  fi
done

if (( ${#missing[@]} > 0 )); then
  echo "Missing live DB variables: ${missing[*]}" >&2
  echo "Refusing to run production migrations without explicit LIVE_DB_* values." >&2
  exit 1
fi

backup_dir="${SANAD_DB_DUMP_DIR:-database/dumps}"
mkdir -p "$backup_dir"
backup_file="$backup_dir/before_live_migrate_$(date +%Y%m%d_%H%M%S).sql.gz"

echo "Backing up live database before migrations: $backup_file"
MYSQL_PWD="${LIVE_DB_PASSWORD:-}" mysqldump \
  --host="$LIVE_DB_HOST" \
  --port="${LIVE_DB_PORT:-3306}" \
  --user="$LIVE_DB_USERNAME" \
  --single-transaction \
  --quick \
  --routines \
  --triggers \
  --no-tablespaces \
  --set-gtid-purged=OFF \
  "$LIVE_DB_DATABASE" | gzip > "$backup_file"

echo "Running Laravel migrations against live database"
DB_HOST="$LIVE_DB_HOST" \
DB_PORT="${LIVE_DB_PORT:-3306}" \
DB_DATABASE="$LIVE_DB_DATABASE" \
DB_USERNAME="$LIVE_DB_USERNAME" \
DB_PASSWORD="${LIVE_DB_PASSWORD:-}" \
php -d error_reporting=6143 artisan migrate --force

echo "Live migrations complete."
