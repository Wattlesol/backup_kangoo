#!/usr/bin/env bash
set -euo pipefail

export PATH="/usr/local/bin:/opt/homebrew/bin:/usr/bin:/bin:/usr/sbin:/sbin:$PATH"

BASE_WEB_URL="${SANAD_WEB_BASE_URL:-http://127.0.0.1:8092}"
EMAIL="${SANAD_TEST_EMAIL:-demo@admin.com}"
PASSWORD="${SANAD_TEST_PASSWORD:-12345678}"

if ! command -v curl >/dev/null 2>&1; then
  echo "FAIL curl is required for web QA." >&2
  exit 1
fi

tmpdir="$(mktemp -d)"
trap 'rm -rf "$tmpdir"' EXIT

login_page="$tmpdir/login.html"
cookies="$tmpdir/cookies.txt"

curl -sS -c "$cookies" "${BASE_WEB_URL}/login" -o "$login_page"
csrf_token="$(sed -n 's/.*name="_token" value="\([^"]*\)".*/\1/p' "$login_page" | head -n 1)"

if [[ -z "$csrf_token" ]]; then
  echo "FAIL web login CSRF token was not found." >&2
  exit 1
fi

login_code="$(curl -sS -b "$cookies" -c "$cookies" -o "$tmpdir/post-login.html" -w '%{http_code}' \
  -X POST "${BASE_WEB_URL}/login" \
  -H 'Content-Type: application/x-www-form-urlencoded' \
  --data-urlencode "_token=${csrf_token}" \
  --data-urlencode "email=${EMAIL}" \
  --data-urlencode "password=${PASSWORD}")"

if [[ "$login_code" != "302" ]]; then
  echo "FAIL web login returned HTTP ${login_code}; expected 302 redirect." >&2
  exit 1
fi

echo "PASS authenticated web session"

assert_page() {
  local path="$1"
  local marker="$2"
  local output="$tmpdir/$(echo "$path" | tr '/?' '__').html"
  local code

  code="$(curl -sS -b "$cookies" -o "$output" -w '%{http_code}' "${BASE_WEB_URL}${path}")"

  if [[ "$code" != "200" ]]; then
    echo "FAIL ${path} returned HTTP ${code}" >&2
    exit 1
  fi

  if grep -Eiq 'exception|fatal error|stack trace|queryexception|whoops' "$output"; then
    echo "FAIL ${path} contains server error markers." >&2
    exit 1
  fi

  if ! grep -Fqi "$marker" "$output"; then
    echo "FAIL ${path} did not contain expected marker: ${marker}" >&2
    exit 1
  fi

  echo "PASS ${path}"
}

assert_page /sanad/dashboard "Sanad"
assert_page /sanad/requests "Sanad"
assert_page /sanad/requests/1 "SANAD-LOCAL-QA-000001"
assert_page /sanad/ai "Sanad"
assert_page /payment "Payment"

echo "Sanad web SQL QA completed successfully against ${BASE_WEB_URL}"
