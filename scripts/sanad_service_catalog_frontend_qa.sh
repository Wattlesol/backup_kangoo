#!/usr/bin/env bash
set -euo pipefail

export PATH="/usr/local/bin:/opt/homebrew/bin:/usr/bin:/bin:/usr/sbin:/sbin:$PATH"

BASE_WEB_URL="${SANAD_WEB_BASE_URL:-http://127.0.0.1:8092}"
ADMIN_EMAIL="${SANAD_ADMIN_WEB_TEST_EMAIL:-admin@admin.com}"
PARTNER_EMAIL="${SANAD_PARTNER_WEB_TEST_EMAIL:-demo@provider.com}"
PASSWORD="${SANAD_WEB_TEST_PASSWORD:-12345678}"

if ! command -v curl >/dev/null 2>&1; then
  echo "FAIL curl is required." >&2
  exit 1
fi

tmpdir="$(mktemp -d)"
trap 'rm -rf "$tmpdir"' EXIT

fail() {
  echo "FAIL $*" >&2
  exit 1
}

pass() {
  echo "PASS $*"
}

html_to_text() {
  perl -0777 -pe 's/<script.*?<\/script>//gs; s/<style.*?<\/style>//gs; s/<[^>]+>/\n/g; s/&nbsp;/ /g; s/&amp;/\&/g; s/&#039;/'"'"'/g; s/[ \t]+/ /g; s/\n{2,}/\n/g'
}

login() {
  local email="$1"
  local role="$2"
  local cookies="$tmpdir/${role}-cookies.txt"
  local login_page="$tmpdir/${role}-login.html"
  local token
  local code

  curl -sS -c "$cookies" "${BASE_WEB_URL}/login" -o "$login_page"
  token="$(sed -n 's/.*name="_token" value="\([^"]*\)".*/\1/p' "$login_page" | head -n 1)"
  [[ -n "$token" ]] || fail "${role} login CSRF token missing"

  code="$(curl -sS -b "$cookies" -c "$cookies" -o "$tmpdir/${role}-post-login.html" -w '%{http_code}' \
    -X POST "${BASE_WEB_URL}/login" \
    -H 'Content-Type: application/x-www-form-urlencoded' \
    --data-urlencode "_token=${token}" \
    --data-urlencode "email=${email}" \
    --data-urlencode "password=${PASSWORD}")"
  [[ "$code" == "302" ]] || fail "${role} login returned HTTP ${code}; expected 302"

  echo "$cookies"
}

fetch_text() {
  local cookies="$1"
  local role="$2"
  local path="$3"
  local html="$tmpdir/${role}-$(echo "$path" | tr '/?=&' '____').html"
  local text="$tmpdir/${role}-$(echo "$path" | tr '/?=&' '____').txt"
  local code

  code="$(curl -sS -b "$cookies" -L -o "$html" -w '%{http_code}' "${BASE_WEB_URL}${path}")"
  [[ "$code" == "200" ]] || fail "${role} ${path} returned HTTP ${code}"
  if grep -Eiq 'exception_class|fatal error|stack trace|queryexception|whoops' "$html"; then
    fail "${role} ${path} contains server error markers"
  fi
  html_to_text < "$html" > "$text"
  echo "$text"
}

assert_contains() {
  local file="$1"
  local marker="$2"
  local label="$3"

  grep -Fqi "$marker" "$file" || fail "${label} missing marker: ${marker}"
}

assert_not_contains() {
  local file="$1"
  local marker="$2"
  local label="$3"

  if grep -Fqi "$marker" "$file"; then
    fail "${label} should not expose marker: ${marker}"
  fi
}

admin_cookies="$(login "$ADMIN_EMAIL" admin)"
admin_service_list="$(fetch_text "$admin_cookies" admin /service)"
admin_service_create="$(fetch_text "$admin_cookies" admin /service/create)"

assert_contains "$admin_service_list" "Sanad Service Catalog" "admin service list"
assert_contains "$admin_service_list" "Catalog Readiness" "admin service list"
assert_contains "$admin_service_list" "Active Services" "admin service list"
assert_contains "$admin_service_list" "Packages" "admin service list"
assert_contains "$admin_service_list" "Add-ons" "admin service list"
assert_contains "$admin_service_list" "Partner" "admin service list"

assert_contains "$admin_service_create" "Sanad Service Master Data" "admin service form"
assert_contains "$admin_service_create" "English Name" "admin service form"
assert_contains "$admin_service_create" "Arabic Name" "admin service form"
assert_contains "$admin_service_create" "Government Entity" "admin service form"
assert_contains "$admin_service_create" "Required Documents" "admin service form"
assert_contains "$admin_service_create" "Sanad Service Fee" "admin service form"
assert_contains "$admin_service_create" "Required Employee Skills" "admin service form"
assert_contains "$admin_service_create" "Partner Internal Notes / Availability" "admin service form"
assert_contains "$admin_service_create" "Centralized government-service metadata used by web dashboards and mobile apps" "admin service form"
assert_not_contains "$admin_service_create" "Provider Address" "admin service form"
assert_not_contains "$admin_service_create" "Select Provider" "admin service form"
pass "admin service catalog and Sanad master data frontend markers"

partner_cookies="$(login "$PARTNER_EMAIL" partner)"
partner_service_list="$(fetch_text "$partner_cookies" partner /service)"

assert_contains "$partner_service_list" "Sanad Service Catalog" "partner service list"
assert_contains "$partner_service_list" "Catalog Readiness" "partner service list"
assert_contains "$partner_service_list" "Active Services" "partner service list"
assert_not_contains "$partner_service_list" "Provider List" "partner service list"
assert_not_contains "$partner_service_list" "Provider Address" "partner service list"
pass "partner service catalog frontend markers"

echo "Sanad service catalog frontend QA completed successfully against ${BASE_WEB_URL}."
