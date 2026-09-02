#!/usr/bin/env bash
set -euo pipefail

export PATH="/usr/local/bin:/opt/homebrew/bin:/usr/bin:/bin:/usr/sbin:/sbin:$PATH"

BASE_WEB_URL="${SANAD_WEB_BASE_URL:-http://127.0.0.1:8092}"
ADMIN_EMAIL="${SANAD_ADMIN_WEB_TEST_EMAIL:-demo@admin.com}"
PARTNER_EMAIL="${SANAD_PARTNER_WEB_TEST_EMAIL:-demo@provider.com}"
EMPLOYEE_EMAIL="${SANAD_EMPLOYEE_WEB_TEST_EMAIL:-demo@handyman.com}"
CUSTOMER_EMAIL="${SANAD_CUSTOMER_WEB_TEST_EMAIL:-demo@customer.com}"
PASSWORD="${SANAD_WEB_TEST_PASSWORD:-12345678}"

if ! command -v curl >/dev/null 2>&1; then
  echo "FAIL curl is required for web role UAT." >&2
  exit 1
fi

tmpdir="$(mktemp -d)"
trap 'rm -rf "$tmpdir"' EXIT

html_to_text() {
  perl -0777 -pe 's/<script.*?<\/script>//gs; s/<style.*?<\/style>//gs; s/<[^>]+>/\n/g; s/&nbsp;/ /g; s/&amp;/&/g; s/[ \t]+/ /g; s/\n{2,}/\n/g'
}

assert_clean_page() {
  local page_file="$1"
  local label="$2"

  if grep -Eiq 'exception_class|fatal error|stack trace|queryexception|whoops' "$page_file"; then
    echo "FAIL ${label} contains server error markers." >&2
    exit 1
  fi
}

admin_login() {
  local email="$1"
  local role="$2"
  local cookies="$tmpdir/${role}-cookies.txt"
  local login_page="$tmpdir/${role}-login.html"
  local token
  local code

  curl -sS -c "$cookies" "${BASE_WEB_URL}/login" -o "$login_page"
  token="$(sed -n 's/.*name="_token" value="\([^"]*\)".*/\1/p' "$login_page" | head -n 1)"

  if [[ -z "$token" ]]; then
    echo "FAIL ${role} login CSRF token was not found." >&2
    exit 1
  fi

  code="$(curl -sS -b "$cookies" -c "$cookies" -o "$tmpdir/${role}-post-login.html" -w '%{http_code}' \
    -X POST "${BASE_WEB_URL}/login" \
    -H 'Content-Type: application/x-www-form-urlencoded' \
    --data-urlencode "_token=${token}" \
    --data-urlencode "email=${email}" \
    --data-urlencode "password=${PASSWORD}")"

  if [[ "$code" != "302" ]]; then
    echo "FAIL ${role} login returned HTTP ${code}; expected 302 redirect." >&2
    exit 1
  fi

  echo "$cookies"
}

customer_login() {
  local cookies="$tmpdir/customer-cookies.txt"
  local login_page="$tmpdir/customer-login.html"
  local token
  local code

  curl -sS -c "$cookies" "${BASE_WEB_URL}/login" -o "$login_page"
  token="$(sed -n 's/.*name="_token" value="\([^"]*\)".*/\1/p' "$login_page" | head -n 1)"

  if [[ -z "$token" ]]; then
    echo "FAIL customer login CSRF token was not found." >&2
    exit 1
  fi

  code="$(curl -sS -b "$cookies" -c "$cookies" -o "$tmpdir/customer-post-login.html" -w '%{http_code}' \
    -X POST "${BASE_WEB_URL}/login" \
    -H 'Content-Type: application/x-www-form-urlencoded' \
    --data-urlencode "_token=${token}" \
    --data-urlencode "email=${CUSTOMER_EMAIL}" \
    --data-urlencode "password=${PASSWORD}" \
    --data-urlencode "login=user_login")"

  if [[ "$code" != "302" ]]; then
    echo "FAIL customer login returned HTTP ${code}; expected 302 redirect." >&2
    exit 1
  fi

  assert_clean_page "$tmpdir/customer-post-login.html" "customer login"
  echo "$cookies"
}

assert_role_page() {
  local cookies="$1"
  local role="$2"
  local path="$3"
  local marker="$4"
  local safe_path
  local page_file
  local text_file
  local code

  safe_path="$(echo "$role-$path" | tr '/?=&' '____')"
  page_file="$tmpdir/${safe_path}.html"
  text_file="$tmpdir/${safe_path}.txt"

  code="$(curl -sS -b "$cookies" -L -o "$page_file" -w '%{http_code}' "${BASE_WEB_URL}${path}")"

  if [[ "$code" != "200" ]]; then
    echo "FAIL ${role} ${path} returned HTTP ${code}" >&2
    exit 1
  fi

  assert_clean_page "$page_file" "${role} ${path}"
  html_to_text < "$page_file" > "$text_file"

  if ! grep -Fqi "$marker" "$text_file" "$page_file"; then
    echo "FAIL ${role} ${path} did not contain expected marker: ${marker}" >&2
    exit 1
  fi

  echo "PASS ${role} ${path}"
}

first_request_path_for_role() {
  local cookies="$1"
  local role="$2"
  local page_file="$tmpdir/${role}-request-list.html"
  local request_path

  curl -sS -b "$cookies" -L -o "$page_file" "${BASE_WEB_URL}/sanad/requests"
  assert_clean_page "$page_file" "${role} request list"

  request_path="$(grep -Eo 'href="[^"]*/sanad/requests/[0-9]+"' "$page_file" | sed -E 's/^href="[^"]*(\/sanad\/requests\/[0-9]+)"$/\1/' | head -n 1)"

  if [[ -z "$request_path" ]]; then
    echo "FAIL ${role} request list did not expose an accessible Sanad request detail link." >&2
    exit 1
  fi

  echo "$request_path"
}

first_customer_request_path() {
  local cookies="$1"
  local page_file="$tmpdir/customer-request-list.html"
  local request_path

  curl -sS -b "$cookies" -L -o "$page_file" "${BASE_WEB_URL}/customer-dashboard/requests"
  assert_clean_page "$page_file" "customer request list"

  request_path="$(grep -Eo 'href="[^"]*/customer-dashboard/requests/[0-9]+"' "$page_file" | sed -E 's/^href="[^"]*(\/customer-dashboard\/requests\/[0-9]+)"$/\1/' | head -n 1)"
  echo "$request_path"
}

first_customer_service_path() {
  local cookies="$1"
  local page_file="$tmpdir/customer-catalog.html"
  local service_path

  curl -sS -b "$cookies" -L -o "$page_file" "${BASE_WEB_URL}/customer-dashboard/catalog"
  assert_clean_page "$page_file" "customer service catalog"
  service_path="$(grep -Eo 'href="[^"]*/customer-dashboard/catalog/[0-9]+"' "$page_file" | sed -E 's/^href="[^"]*(\/customer-dashboard\/catalog\/[0-9]+)"$/\1/' | head -n 1)"
  echo "$service_path"
}

admin_cookies="$(admin_login "$ADMIN_EMAIL" admin)"
admin_request_path="$(first_request_path_for_role "$admin_cookies" admin)"
assert_role_page "$admin_cookies" admin /home "Quick"
assert_role_page "$admin_cookies" admin /sanad/dashboard "Quick"
assert_role_page "$admin_cookies" admin /sanad/requests "Quick"
assert_role_page "$admin_cookies" admin "$admin_request_path" "QUICK-"
assert_role_page "$admin_cookies" admin /provider "Partner"
assert_role_page "$admin_cookies" admin /handyman "Employee"
assert_role_page "$admin_cookies" admin /service "Quick"
assert_role_page "$admin_cookies" admin /document "Quick"
assert_role_page "$admin_cookies" admin /payment "Payment"
assert_role_page "$admin_cookies" admin /sanad/ai "AI"

partner_cookies="$(admin_login "$PARTNER_EMAIL" partner)"
partner_request_path="$(first_request_path_for_role "$partner_cookies" partner)"
assert_role_page "$partner_cookies" partner /home "Quick"
assert_role_page "$partner_cookies" partner /sanad/requests "Quick"
assert_role_page "$partner_cookies" partner "$partner_request_path" "QUICK-"
assert_role_page "$partner_cookies" partner /handyman "Employee"
assert_role_page "$partner_cookies" partner /service "Quick"
assert_role_page "$partner_cookies" partner /payment "Payment"

employee_cookies="$(admin_login "$EMPLOYEE_EMAIL" employee)"
employee_request_path="$(first_request_path_for_role "$employee_cookies" employee)"
assert_role_page "$employee_cookies" employee /home "Quick"
assert_role_page "$employee_cookies" employee /sanad/requests "Quick"
assert_role_page "$employee_cookies" employee "$employee_request_path" "QUICK-"

customer_cookies="$(customer_login)"
assert_role_page "$customer_cookies" customer /customer-dashboard "Quick"
assert_role_page "$customer_cookies" customer /customer-dashboard/catalog "Quick"
customer_service_path="$(first_customer_service_path "$customer_cookies")"
if [[ -n "$customer_service_path" ]]; then
  assert_role_page "$customer_cookies" customer "$customer_service_path" "Quick"
fi
assert_role_page "$customer_cookies" customer /customer-dashboard/requests/create "Quick"
assert_role_page "$customer_cookies" customer /customer-dashboard/requests "Quick"
customer_request_path="$(first_customer_request_path "$customer_cookies")"
if [[ -n "$customer_request_path" ]]; then
  assert_role_page "$customer_cookies" customer "$customer_request_path" "QUICK-"
fi
assert_role_page "$customer_cookies" customer /customer-dashboard/document-vault "Quick"
assert_role_page "$customer_cookies" customer /customer-dashboard/messages "Quick"
assert_role_page "$customer_cookies" customer /customer-dashboard/billing "Quick"
assert_role_page "$customer_cookies" customer /customer-dashboard/support "Quick"
assert_role_page "$customer_cookies" customer /customer-dashboard/notifications "Quick"
assert_role_page "$customer_cookies" customer /customer-dashboard/profile "Quick"
assert_role_page "$customer_cookies" customer /customer-dashboard/ai "Quick"

echo "Quick local web role UAT completed successfully against ${BASE_WEB_URL}."
