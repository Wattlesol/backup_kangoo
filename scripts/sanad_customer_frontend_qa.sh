#!/usr/bin/env bash
set -euo pipefail

export PATH="/usr/local/bin:/opt/homebrew/bin:/usr/bin:/bin:/usr/sbin:/sbin:$PATH"

BASE_WEB_URL="${SANAD_WEB_BASE_URL:-http://127.0.0.1:8092}"
CUSTOMER_EMAIL="${SANAD_CUSTOMER_WEB_TEST_EMAIL:-demo@customer.com}"
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

assert_contains_either() {
  local file="$1"
  local first="$2"
  local second="$3"
  local label="$4"

  grep -Fqi "$first" "$file" || grep -Fqi "$second" "$file" || fail "${label} missing markers: ${first} / ${second}"
}

assert_not_contains() {
  local file="$1"
  local marker="$2"
  local label="$3"

  if grep -Fqi "$marker" "$file"; then
    fail "${label} should not expose marker: ${marker}"
  fi
}

cookie_jar="$tmpdir/customer-cookies.txt"
login_page="$tmpdir/customer-login.html"
post_login="$tmpdir/customer-post-login.html"

curl -sS -c "$cookie_jar" "${BASE_WEB_URL}/login" -o "$login_page"
token="$(sed -n 's/.*name="_token" value="\([^"]*\)".*/\1/p' "$login_page" | head -n 1)"
[[ -n "$token" ]] || fail "customer login CSRF token missing"

code="$(curl -sS -b "$cookie_jar" -c "$cookie_jar" -o "$post_login" -w '%{http_code}' \
  -X POST "${BASE_WEB_URL}/login" \
  -H 'Content-Type: application/x-www-form-urlencoded' \
  --data-urlencode "_token=${token}" \
  --data-urlencode "email=${CUSTOMER_EMAIL}" \
  --data-urlencode "password=${PASSWORD}" \
  --data-urlencode "login=user_login")"

[[ "$code" == "302" ]] || fail "customer login returned HTTP ${code}; expected 302"
assert_clean_page "$post_login" "customer login"

request_list="$tmpdir/customer-request-list.html"
curl -sS -b "$cookie_jar" -L -o "$request_list" "${BASE_WEB_URL}/customer-dashboard/requests"
request_path="$(perl -ne 'if (/href="[^"]*(\/customer-dashboard\/requests\/[0-9]+)"/) { print $1; exit }' "$request_list")"
[[ -n "$request_path" ]] || fail "customer request list did not expose an accessible request detail"

detail_html="$tmpdir/customer-request-detail.html"
detail_text="$tmpdir/customer-request-detail.txt"
code="$(curl -sS -b "$cookie_jar" -L -o "$detail_html" -w '%{http_code}' "${BASE_WEB_URL}${request_path}")"
[[ "$code" == "200" ]] || fail "customer ${request_path} returned HTTP ${code}"
assert_clean_page "$detail_html" "customer request detail"
html_to_text < "$detail_html" > "$detail_text"

assert_contains_either "$detail_text" "Request Information" "معلومات الطلب" "customer request detail"
assert_contains_either "$detail_text" "Quick team" "فريق كويك" "customer request detail"
assert_contains_either "$detail_text" "Progress" "نسبة التقدم" "customer request detail"
assert_contains_either "$detail_text" "Timeline" "المراحل والجدول الزمني" "customer request detail"
assert_contains_either "$detail_text" "Talk to Quick" "تحدث إلى كويك" "customer request detail"
assert_contains_either "$detail_text" "Required Documents" "المستندات المطلوبة" "customer request detail"
assert_contains_either "$detail_text" "Document Requests" "طلبات المستندات" "customer request detail"
assert_contains "$detail_html" 'name="document_selection"' "customer request detail"
assert_not_contains "$detail_text" "Customer Rating" "customer request detail"

assert_not_contains "$detail_text" "Kangoo" "customer request detail"
assert_not_contains "$detail_text" "Provider Demo" "customer request detail"
assert_not_contains "$detail_text" "Handyman Demo" "customer request detail"
assert_not_contains "$detail_text" "demo@provider.com" "customer request detail"
assert_not_contains "$detail_text" "demo@handyman.com" "customer request detail"
assert_not_contains "$detail_text" "Assigned Employee" "customer request detail"
assert_not_contains "$detail_text" "About Provider" "customer request detail"
assert_not_contains "$detail_text" "About Handyman" "customer request detail"
assert_not_contains "$detail_text" "Provider Profile" "customer request detail"
assert_not_contains "$detail_text" "Handyman Profile" "customer request detail"
assert_not_contains "$detail_text" "Booking History" "customer request detail"
assert_not_contains "$detail_text" "Cancel Booking" "customer request detail"
assert_not_contains "$detail_text" "booking-detail/" "customer request detail"

invoice_path="$(perl -ne 'if (/href="[^"]*(\/invoice_pdf\/[0-9]+)"/) { print $1; exit }' "$detail_html")"
if [[ -z "$invoice_path" ]]; then
  billing_html="$tmpdir/customer-billing.html"
  curl -sS -b "$cookie_jar" -L -o "$billing_html" "${BASE_WEB_URL}/customer-dashboard/billing"
  assert_clean_page "$billing_html" "customer billing"
  invoice_path="$(perl -ne 'if (/href="[^"]*(\/invoice_pdf\/[0-9]+)"/) { print $1; exit }' "$billing_html")"
fi
if [[ -n "$invoice_path" ]]; then
  invoice_code="$(curl -sS -b "$cookie_jar" -L -o "$tmpdir/customer-invoice.pdf" -w '%{http_code}' "${BASE_WEB_URL}${invoice_path}")"
  [[ "$invoice_code" == "200" ]] || fail "customer invoice returned HTTP ${invoice_code}"
  grep -aq '^%PDF' "$tmpdir/customer-invoice.pdf" || fail "customer invoice response was not a PDF"
  pass "customer invoice download"
fi

pass "customer request detail Quick privacy and terminology markers"

echo "Quick customer frontend QA completed successfully against ${BASE_WEB_URL}."
