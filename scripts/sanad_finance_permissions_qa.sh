#!/usr/bin/env bash
set -euo pipefail

WEB_BASE_URL="${WEB_BASE_URL:-http://127.0.0.1:8092}"
ADMIN_EMAIL="${SANAD_ADMIN_WEB_TEST_EMAIL:-admin@admin.com}"
PARTNER_EMAIL="${SANAD_PARTNER_WEB_TEST_EMAIL:-demo@provider.com}"
PASSWORD="${SANAD_WEB_TEST_PASSWORD:-12345678}"

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

login_admin_shell() {
  local email="$1"
  local label="$2"
  local cookie_jar="$3"
  local login_page
  local token
  local code

  login_page="$(mktemp)"
  curl -fsS -c "$cookie_jar" "${WEB_BASE_URL}/login" >"$login_page"
  token="$(sed -n 's/.*name="_token" value="\([^"]*\)".*/\1/p' "$login_page" | head -1)"
  rm -f "$login_page"

  [[ -n "$token" ]] || fail "Could not read ${label} login CSRF token"

  code="$(curl -sS -b "$cookie_jar" -c "$cookie_jar" -o /tmp/sanad-finance-login.html -w '%{http_code}' \
    -X POST "${WEB_BASE_URL}/login" \
    -H 'Content-Type: application/x-www-form-urlencoded' \
    --data-urlencode "_token=${token}" \
    --data-urlencode "email=${email}" \
    --data-urlencode "password=${PASSWORD}")"

  [[ "$code" == "302" ]] || fail "${label} login returned HTTP ${code}; expected 302"
}

assert_payment_page() {
  local cookie_jar="$1"
  local label="$2"
  local expected_scope="$3"
  local expected_badge="$4"
  local page_file
  local code

  page_file="$(mktemp)"
  code="$(curl -sS -L -b "$cookie_jar" -o "$page_file" -w '%{http_code}' "${WEB_BASE_URL}/payment")"

  [[ "$code" == "200" ]] || fail "${label} /payment returned HTTP ${code}"
  grep -Fq "$expected_scope" "$page_file" || fail "${label} /payment missing scope: ${expected_scope}"
  grep -Fq "$expected_badge" "$page_file" || fail "${label} /payment missing badge: ${expected_badge}"

  if [[ "$label" == "partner" ]] && grep -Fq "quick-action-form" "$page_file"; then
    fail "partner /payment exposes admin bulk action form"
  fi

  rm -f "$page_file"
  pass "${label} /payment role scope rendered correctly"
}

cd "$(dirname "$0")/.."

echo "== Sanad finance permissions QA =="

assert_file_contains "app/Http/Controllers/PaymentController.php" "Admin finance scope"
assert_file_contains "app/Http/Controllers/PaymentController.php" "Partner finance scope"
assert_file_contains "app/Http/Controllers/PaymentController.php" "Customer finance scope"
assert_file_contains "app/Http/Controllers/PaymentController.php" "can_bulk_manage"
assert_file_contains "app/Models/ProviderPayout.php" "whereRaw('1 = 0')"
assert_file_contains "resources/views/payment/partials/sanad-payment-summary.blade.php" "Scoped view only"
assert_file_contains "resources/views/payment/index.blade.php" "canBulkManagePayments"

php -l app/Http/Controllers/PaymentController.php
php -l app/Models/ProviderPayout.php

if ! curl -fsS --max-time 5 "${WEB_BASE_URL}/login" >/dev/null; then
  echo "SKIP: local web app unavailable at ${WEB_BASE_URL}; source-level finance permission checks passed."
  exit 0
fi

admin_cookie="$(mktemp)"
partner_cookie="$(mktemp)"
trap 'rm -f "$admin_cookie" "$partner_cookie" /tmp/sanad-finance-login.html' EXIT

login_admin_shell "$ADMIN_EMAIL" admin "$admin_cookie"
assert_payment_page "$admin_cookie" admin "Admin finance scope" "Bulk management enabled"

login_admin_shell "$PARTNER_EMAIL" partner "$partner_cookie"
assert_payment_page "$partner_cookie" partner "Partner finance scope" "Scoped view only"

echo "Sanad finance permissions QA passed."
