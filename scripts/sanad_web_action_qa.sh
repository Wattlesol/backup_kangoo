#!/usr/bin/env bash
set -euo pipefail

export PATH="/usr/local/bin:/opt/homebrew/bin:/usr/bin:/bin:/usr/sbin:/sbin:$PATH"

BASE_WEB_URL="${SANAD_WEB_BASE_URL:-http://127.0.0.1:8092}"
ADMIN_EMAIL="${SANAD_ADMIN_WEB_TEST_EMAIL:-admin@admin.com}"
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

extract_csrf() {
  local file="$1"
  sed -n 's/.*name="_token" value="\([^"]*\)".*/\1/p' "$file" | head -n 1
}

login_admin() {
  local cookies="$tmpdir/admin-cookies.txt"
  local login_page="$tmpdir/admin-login.html"
  local token
  local code

  curl -sS -c "$cookies" "${BASE_WEB_URL}/login" -o "$login_page"
  token="$(extract_csrf "$login_page")"
  [[ -n "$token" ]] || fail "admin login CSRF token missing"

  code="$(curl -sS -b "$cookies" -c "$cookies" -o "$tmpdir/admin-post-login.html" -w '%{http_code}' \
    -X POST "${BASE_WEB_URL}/login" \
    -H 'Content-Type: application/x-www-form-urlencoded' \
    --data-urlencode "_token=${token}" \
    --data-urlencode "email=${ADMIN_EMAIL}" \
    --data-urlencode "password=${PASSWORD}")"

  [[ "$code" == "302" ]] || fail "admin login returned HTTP ${code}; expected 302"
  echo "$cookies"
}

first_request_path() {
  local cookies="$1"
  local page_file="$tmpdir/admin-requests.html"
  local request_path
  local code

  code="$(curl -sS -b "$cookies" -L -o "$page_file" -w '%{http_code}' "${BASE_WEB_URL}/sanad/requests")"
  [[ "$code" == "200" ]] || fail "admin request list returned HTTP ${code}"
  assert_clean_page "$page_file" "admin request list"

  request_path="$(grep -Eo 'href="[^"]*/sanad/requests/[0-9]+"' "$page_file" | sed -E 's/^href="[^"]*(\/sanad\/requests\/[0-9]+)"$/\1/' | head -n 1)"
  [[ -n "$request_path" ]] || fail "admin request list has no accessible request detail link"
  echo "$request_path"
}

fetch_detail() {
  local cookies="$1"
  local path="$2"
  local html="$3"
  local text="$4"
  local code

  code="$(curl -sS -b "$cookies" -L -o "$html" -w '%{http_code}' "${BASE_WEB_URL}${path}")"
  [[ "$code" == "200" ]] || fail "${path} returned HTTP ${code}"
  assert_clean_page "$html" "admin request detail"
  html_to_text < "$html" > "$text"
}

post_form() {
  local cookies="$1"
  local url="$2"
  local label="$3"
  shift 3
  local code

  code="$(curl -sS -b "$cookies" -c "$cookies" -o "$tmpdir/${label}.html" -w '%{http_code}' \
    -X POST "$url" \
    -H 'Content-Type: application/x-www-form-urlencoded' \
    "$@")"

  [[ "$code" == "302" ]] || fail "${label} returned HTTP ${code}; expected 302"
}

cookies="$(login_admin)"
request_path="$(first_request_path "$cookies")"
detail_html="$tmpdir/detail.html"
detail_text="$tmpdir/detail.txt"

fetch_detail "$cookies" "$request_path" "$detail_html" "$detail_text"
csrf="$(extract_csrf "$detail_html")"
[[ -n "$csrf" ]] || fail "request detail CSRF token missing"

stamp="$(date +%Y%m%d%H%M%S)"
buzz_message="Sanad web action QA buzz ${stamp}"
document_type="Sanad web action QA document ${stamp}"
document_file="sanad-web-action-${stamp}.pdf"

post_form "$cookies" "${BASE_WEB_URL}${request_path}/buzz" "buzz-create" \
  --data-urlencode "_token=${csrf}" \
  --data-urlencode "recipient_role=admin" \
  --data-urlencode "priority=urgent" \
  --data-urlencode "message=${buzz_message}"

fetch_detail "$cookies" "$request_path" "$detail_html" "$detail_text"
assert_contains "$detail_text" "$buzz_message" "Buzz alert list"
assert_contains "$detail_text" "Acknowledge" "Buzz acknowledgement action"
pass "Buzz create action renders and exposes acknowledgement"

ack_path="$(grep -Eo 'action="[^"]*/sanad/requests/[0-9]+/buzz/[0-9]+/acknowledge"' "$detail_html" | sed -E 's/^action="[^"]*(\/sanad\/requests\/[0-9]+\/buzz\/[0-9]+\/acknowledge)"$/\1/' | head -n 1)"
[[ -n "$ack_path" ]] || fail "Buzz acknowledgement form not found"
csrf="$(extract_csrf "$detail_html")"

post_form "$cookies" "${BASE_WEB_URL}${ack_path}" "buzz-acknowledge" \
  --data-urlencode "_token=${csrf}"

fetch_detail "$cookies" "$request_path" "$detail_html" "$detail_text"
assert_contains "$detail_text" "$buzz_message" "acknowledged Buzz alert"
assert_contains "$detail_text" "Acknowledged" "Buzz acknowledged status"
pass "Buzz acknowledgement action persists and renders"

csrf="$(extract_csrf "$detail_html")"
post_form "$cookies" "${BASE_WEB_URL}${request_path}/documents" "document-create" \
  --data-urlencode "_token=${csrf}" \
  --data-urlencode "document_type=${document_type}" \
  --data-urlencode "file_name=${document_file}" \
  --data-urlencode "file_path=/storage/sanad-qa/${document_file}" \
  --data-urlencode "visible_to[]=admin"

fetch_detail "$cookies" "$request_path" "$detail_html" "$detail_text"
assert_contains "$detail_text" "$document_type" "Document vault list"
assert_contains "$detail_text" "$document_file" "Document file reference"
assert_contains "$detail_text" "Download before deletion" "Document retention guidance"
pass "Document create action renders with retention guidance"

approve_path="$(grep -Eo 'action="[^"]*/sanad/requests/[0-9]+/documents/[0-9]+/approve"' "$detail_html" | sed -E 's/^action="[^"]*(\/sanad\/requests\/[0-9]+\/documents\/[0-9]+\/approve)"$/\1/' | head -n 1)"
[[ -n "$approve_path" ]] || fail "Document approve form not found"
csrf="$(extract_csrf "$detail_html")"

post_form "$cookies" "${BASE_WEB_URL}${approve_path}" "document-approve" \
  --data-urlencode "_token=${csrf}"

fetch_detail "$cookies" "$request_path" "$detail_html" "$detail_text"
assert_contains "$detail_text" "$document_type" "approved document"
assert_contains "$detail_text" "Approved" "Document approved status"
pass "Document approval action persists and renders"

echo "Sanad web action QA completed successfully against ${BASE_WEB_URL}."
