#!/usr/bin/env bash
set -euo pipefail

APP_CONTAINER="${APP_CONTAINER:-}"
WEB_BASE_URL="${WEB_BASE_URL:-http://127.0.0.1:8092}"

fail() {
  echo "FAIL: $*" >&2
  exit 1
}

pass() {
  echo "PASS: $*"
}

assert_file_contains() {
  local file="$1"
  local text="$2"

  grep -Fq "$text" "$file" || fail "$file missing expected text: $text"
  pass "$file contains: $text"
}

assert_route_contains() {
  local path="$1"
  local text="$2"
  local cookie_jar="$3"

  body="$(curl -fsS -b "$cookie_jar" "${WEB_BASE_URL}${path}")"

  grep -Fq "$text" <<<"$body" || fail "${path} missing expected text: $text"
  pass "${path} contains: $text"
}

login_cookie_jar() {
  local cookie_jar

  cookie_jar="$(mktemp)"
  curl -fsS -c "$cookie_jar" "${WEB_BASE_URL}/login" >/tmp/sanad_login_page.html
  token="$(grep -o 'name="_token" value="[^"]*"' /tmp/sanad_login_page.html | sed 's/.*value="//;s/"$//' | head -1)"
  [[ -n "$token" ]] || fail "Could not read login CSRF token"

  curl -fsS -b "$cookie_jar" -c "$cookie_jar" \
    -d "_token=$token" \
    -d "email=admin@admin.com" \
    -d "password=12345678" \
    -d "login=login" \
    "${WEB_BASE_URL}/login" >/dev/null

  rm -f /tmp/sanad_login_page.html
  echo "$cookie_jar"
}

cd "$(dirname "$0")/.."

echo "== Sanad document policy QA =="

if [[ -z "$APP_CONTAINER" ]]; then
  APP_CONTAINER="$(docker ps --format '{{.Names}}' | grep -E '^(kangoo_sanad_app|kangoo-sanad-qa-app-1|backup_kangoo-app-1)$' | head -1 || true)"
fi

assert_file_contains "app/Http/Controllers/API/SanadController.php" "now()->addHours(48)"
assert_file_contains "app/Http/Controllers/SanadWebController.php" "now()->addHours(48)"
assert_file_contains "resources/views/sanad/request-show.blade.php" "documents default to a 48-hour retention window"
assert_file_contains "resources/views/sanad/request-show.blade.php" "Download before deletion"

php -l app/Http/Controllers/API/SanadController.php
php -l app/Http/Controllers/SanadWebController.php

if [[ -n "$APP_CONTAINER" ]] && docker ps --format '{{.Names}}' | grep -Fxq "$APP_CONTAINER"; then
  cookie_jar="$(login_cookie_jar)"
  requests_body="$(curl -fsS -b "$cookie_jar" "${WEB_BASE_URL}/sanad/requests")"
  request_path="$(grep -Eo '/sanad/requests/[0-9]+' <<<"$requests_body" | head -1 || true)"

  if [[ -n "$request_path" ]]; then
    assert_route_contains "$request_path" "documents default to a 48-hour retention window" "$cookie_jar"
    assert_route_contains "$request_path" "Download before deletion" "$cookie_jar"
  else
    echo "SKIP: no Sanad request row is available in the current local database; source-level policy checks passed."
  fi

  rm -f "$cookie_jar"
else
  echo "SKIP: ${APP_CONTAINER} is not running; source-level policy checks passed."
fi

echo "Sanad document policy QA passed."
