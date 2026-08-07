#!/usr/bin/env bash
set -euo pipefail

export PATH="/usr/local/bin:/opt/homebrew/bin:/usr/bin:/bin:/usr/sbin:/sbin:$PATH"

BASE_WEB_URL="${SANAD_WEB_BASE_URL:-http://127.0.0.1:8092}"
ADMIN_EMAIL="${SANAD_ADMIN_WEB_TEST_EMAIL:-admin@admin.com}"
PARTNER_EMAIL="${SANAD_PARTNER_WEB_TEST_EMAIL:-demo@provider.com}"
EMPLOYEE_EMAIL="${SANAD_EMPLOYEE_WEB_TEST_EMAIL:-demo@employee.com}"
PASSWORD="${SANAD_WEB_TEST_PASSWORD:-12345678}"

if ! command -v curl >/dev/null 2>&1; then
  echo "FAIL curl is required." >&2
  exit 1
fi

tmpdir="$(mktemp -d)"
trap 'rm -rf "$tmpdir"' EXIT

html_to_text() {
  perl -0777 -pe 's/<script.*?<\/script>//gs; s/<style.*?<\/style>//gs; s/<[^>]+>/\n/g; s/&nbsp;/ /g; s/&amp;/\&/g; s/&#039;/'"'"'/g; s/[ \t]+/ /g; s/\n{2,}/\n/g'
}

fail() {
  echo "FAIL $*" >&2
  exit 1
}

pass() {
  echo "PASS $*"
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

assert_clean_page() {
  local file="$1"
  local label="$2"

  if grep -Eiq 'exception_class|fatal error|stack trace|queryexception|whoops' "$file"; then
    fail "${label} contains server error markers"
  fi
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

first_request_path_for_role() {
  local cookies="$1"
  local role="$2"
  local page_file="$tmpdir/${role}-requests.html"
  local request_path
  local code

  code="$(curl -sS -b "$cookies" -L -o "$page_file" -w '%{http_code}' "${BASE_WEB_URL}/sanad/requests")"
  [[ "$code" == "200" ]] || fail "${role} request list returned HTTP ${code}"
  assert_clean_page "$page_file" "${role} request list"

  request_path="$(grep -Eo 'href="[^"]*/sanad/requests/[0-9]+"' "$page_file" | sed -E 's/^href="[^"]*(\/sanad\/requests\/[0-9]+)"$/\1/' | head -n 1)"
  [[ -n "$request_path" ]] || fail "${role} request list has no accessible request detail link"

  echo "$request_path"
}

fetch_detail() {
  local cookies="$1"
  local role="$2"
  local path="$3"
  local html="$tmpdir/${role}-detail.html"
  local text="$tmpdir/${role}-detail.txt"
  local code

  code="$(curl -sS -b "$cookies" -L -o "$html" -w '%{http_code}' "${BASE_WEB_URL}${path}")"
  [[ "$code" == "200" ]] || fail "${role} ${path} returned HTTP ${code}"
  assert_clean_page "$html" "${role} request detail"
  html_to_text < "$html" > "$text"
  echo "$text"
}

assert_shared_request_detail_markers() {
  local text="$1"
  local role="$2"

  assert_contains "$text" "Operational Monitoring" "$role request detail"
  assert_contains "$text" "Sanad Request Lifecycle" "$role request detail"
  assert_contains "$text" "Document Vault" "$role request detail"
  assert_contains "$text" "documents default to a 48-hour retention window" "$role request detail"
  assert_contains "$text" "Retention Until" "$role request detail"
  assert_contains "$text" "Download before deletion" "$role request detail"
  assert_contains "$text" "Buzz Alerts" "$role request detail"
  assert_contains "$text" "Recipient Role" "$role request detail"
  assert_contains "$text" "Acknowledge" "$role request detail"
  assert_contains "$text" "Secure Chat" "$role request detail"
  assert_contains "$text" "Visible to:" "$role request detail"
  assert_contains "$text" "Billing And Payment" "$role request detail"
  assert_contains "$text" "Invoice visibility" "$role request detail"
  assert_not_contains "$text" "Kangoo" "$role request detail"
  assert_not_contains "$text" "Create Booking" "$role request detail"
  pass "${role} shared request detail frontend markers"
}

admin_cookies="$(login "$ADMIN_EMAIL" admin)"
admin_request_path="$(first_request_path_for_role "$admin_cookies" admin)"
admin_detail="$(fetch_detail "$admin_cookies" admin "$admin_request_path")"
assert_shared_request_detail_markers "$admin_detail" admin
assert_contains "$admin_detail" "Admin Quality Control" "admin request detail"
assert_contains "$admin_detail" "QC Decision" "admin request detail"
assert_contains "$admin_detail" "Save QC" "admin request detail"
assert_contains "$admin_detail" "Employee Assignment" "admin request detail"
assert_contains "$admin_detail" "Save Assignment" "admin request detail"
pass "admin request detail exposes reviewer/admin controls"

partner_cookies="$(login "$PARTNER_EMAIL" partner)"
partner_request_path="$(first_request_path_for_role "$partner_cookies" partner)"
partner_detail="$(fetch_detail "$partner_cookies" partner "$partner_request_path")"
assert_shared_request_detail_markers "$partner_detail" partner
assert_contains "$partner_detail" "Partner Order Actions" "partner request detail"
assert_not_contains "$partner_detail" "QC Decision" "partner request detail"
assert_not_contains "$partner_detail" "Save QC" "partner request detail"
pass "partner request detail keeps admin QC controls hidden"

employee_cookies="$(login "$EMPLOYEE_EMAIL" employee)"
employee_request_path="$(first_request_path_for_role "$employee_cookies" employee)"
employee_detail="$(fetch_detail "$employee_cookies" employee "$employee_request_path")"
assert_shared_request_detail_markers "$employee_detail" employee
assert_not_contains "$employee_detail" "QC Decision" "employee request detail"
assert_not_contains "$employee_detail" "Save QC" "employee request detail"
pass "employee request detail keeps admin QC controls hidden"

echo "Sanad request detail frontend QA completed successfully against ${BASE_WEB_URL}."
