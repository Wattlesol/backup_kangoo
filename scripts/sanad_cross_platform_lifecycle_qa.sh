#!/usr/bin/env bash
set -euo pipefail

USER_APP_DIR="${SANAD_USER_APP_DIR:-/Users/xain/Documents/kangoo/handyman_user_flutter_v11.13.2}"
ADMIN_APP_DIR="${SANAD_ADMIN_APP_DIR:-/Users/xain/Documents/kangoo/handyman_admin_flutter_app-v3.9.0}"
BASE_URL="${BASE_URL:-http://127.0.0.1:8092/api}"

fail() {
  echo "FAIL: $*" >&2
  exit 1
}

pass() {
  echo "PASS: $*"
}

assert_contains() {
  local file="$1"
  local text="$2"

  grep -Fq "$text" "$file" || fail "$file missing expected text: $text"
  pass "$file contains: $text"
}

assert_no_disallowed_stage_literals() {
  local file="$1"
  shift
  local allowed_pattern="$1"

  while IFS= read -r literal; do
    [[ -z "$literal" ]] && continue
    if ! grep -Eq "^(${allowed_pattern})$" <<<"$literal"; then
      fail "$file contains disallowed Sanad lifecycle stage literal: $literal"
    fi
  done < <(grep -Eo "_stageButton\\('[^']+', '[^']+'" "$file" | sed -E "s/.*'([^']+)'$/\\1/" | sort -u)

  pass "$file uses only configured Sanad lifecycle stage literals"
}

cd "$(dirname "$0")/.."

echo "== Sanad cross-platform lifecycle QA =="

lifecycle_file="$(mktemp)"
trap 'rm -f "$lifecycle_file" /tmp/sanad-foundation.json' EXIT
php -r 'function env($key, $default = null) { return $default; } $c = include "config/sanad.php"; foreach ($c["request_lifecycle"] as $stage) echo $stage, PHP_EOL;' >"$lifecycle_file"
[[ -s "$lifecycle_file" ]] || fail "No lifecycle stages found in config/sanad.php"

allowed_pattern="$(sed 's/[.[\*^$()+?{}|]/\\&/g' "$lifecycle_file" | paste -sd '|' -)"

assert_contains "app/Http/Controllers/API/SanadController.php" "config('sanad.request_lifecycle')"
assert_contains "app/Http/Controllers/API/SanadController.php" "Invalid request lifecycle stage."
assert_contains "resources/views/booking/partials/sanad-lifecycle.blade.php" "config('sanad.request_lifecycle'"
assert_contains "resources/views/booking/partials/sanad-lifecycle.blade.php" "name=\"sanad_stage\""

assert_contains "$USER_APP_DIR/lib/network/rest_apis.dart" "sanad/foundation"
assert_contains "$USER_APP_DIR/lib/network/rest_apis.dart" "sanad/requests?page="
assert_contains "$USER_APP_DIR/lib/screens/sanad/my_sanad_screen.dart" "e['sanad_stage'] ?? e['status']"

assert_contains "$ADMIN_APP_DIR/lib/networks/rest_apis.dart" "sanad/foundation"
assert_contains "$ADMIN_APP_DIR/lib/networks/rest_apis.dart" "sanad/requests/\$requestId/lifecycle"
assert_contains "$ADMIN_APP_DIR/lib/screens/sanad/sanad_operations_screen.dart" "'sanad_stage': stage"
assert_no_disallowed_stage_literals "$ADMIN_APP_DIR/lib/screens/sanad/sanad_operations_screen.dart" "$allowed_pattern"

php -l app/Http/Controllers/API/SanadController.php
php -l app/Http/Controllers/SanadWebController.php

if command -v jq >/dev/null 2>&1 && curl -fsS --max-time 5 "$BASE_URL/sanad/foundation" >/tmp/sanad-foundation.json; then
  jq -e --argjson expected "$(jq -R . "$lifecycle_file" | jq -s .)" '.request_lifecycle == $expected' /tmp/sanad-foundation.json >/dev/null
  pass "API foundation lifecycle matches config/sanad.php"
else
  echo "SKIP: local API foundation unavailable or jq missing; source-level lifecycle sync checks passed."
fi

echo "Sanad cross-platform lifecycle QA passed."
