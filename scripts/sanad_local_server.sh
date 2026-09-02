#!/usr/bin/env bash
set -euo pipefail

cd "$(dirname "$0")/.."

host="${SANAD_LOCAL_HOST:-localhost}"
port="${SANAD_LOCAL_PORT:-8000}"
compose_project="${SANAD_LOCAL_COMPOSE_PROJECT:-backup_kangoo}"
mysql_service="${SANAD_LOCAL_MYSQL_SERVICE:-mysql}"
wait_seconds="${SANAD_LOCAL_DB_WAIT_SECONDS:-90}"

if ! command -v docker >/dev/null 2>&1; then
  echo "Docker is required to start the local MySQL database." >&2
  exit 1
fi

if ! docker info >/dev/null 2>&1; then
  if command -v open >/dev/null 2>&1; then
    echo "Docker is not running. Opening Docker Desktop..."
    open -a Docker || true
  fi

  echo "Waiting for Docker..."
  for _ in $(seq 1 "$wait_seconds"); do
    if docker info >/dev/null 2>&1; then
      break
    fi
    sleep 1
  done
fi

if ! docker info >/dev/null 2>&1; then
  echo "Docker did not become ready. Start Docker Desktop, then run this script again." >&2
  exit 1
fi

echo "Starting local MySQL container..."
docker compose -p "$compose_project" -f docker-compose.yml up -d "$mysql_service"

echo "Waiting for local MySQL to become healthy..."
for _ in $(seq 1 "$wait_seconds"); do
  mysql_status="$(
    docker compose -p "$compose_project" -f docker-compose.yml ps "$mysql_service" --format json 2>/dev/null \
      | tr '\n' ' ' \
      | sed -n 's/.*"Health":"\([^"]*\)".*/\1/p'
  )"

  if [[ "$mysql_status" == "healthy" ]]; then
    break
  fi

  sleep 1
done

if [[ "${mysql_status:-}" != "healthy" ]]; then
  echo "Local MySQL did not become healthy. Current container status:" >&2
  docker compose -p "$compose_project" -f docker-compose.yml ps "$mysql_service" >&2
  exit 1
fi

echo "Starting Sanad app on http://${host}:${port}"

exec php \
  -d error_reporting=6143 \
  -d display_errors=0 \
  -d log_errors=1 \
  -S "$host:$port" -t public server.php
