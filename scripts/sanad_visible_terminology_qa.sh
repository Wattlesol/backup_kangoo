#!/usr/bin/env bash
set -euo pipefail

export PATH="/usr/local/bin:/opt/homebrew/bin:/usr/bin:/bin:/usr/sbin:/sbin:$PATH"

WEB_BASE_URL="${SANAD_WEB_BASE_URL:-http://127.0.0.1:8092}"
EMAIL="${SANAD_WEB_TEST_EMAIL:-admin@admin.com}"
PASSWORD="${SANAD_WEB_TEST_PASSWORD:-12345678}"

if ! command -v curl >/dev/null 2>&1; then
  echo "FAIL curl is required." >&2
  exit 1
fi

if ! command -v ruby >/dev/null 2>&1; then
  echo "FAIL ruby is required to extract the CSRF token." >&2
  exit 1
fi

tmpdir="$(mktemp -d)"
trap 'rm -rf "$tmpdir"' EXIT

login_page="$tmpdir/login.html"
cookies="$tmpdir/cookies.txt"

curl -sS -c "$cookies" "${WEB_BASE_URL}/login" > "$login_page"
token="$(ruby -ne 'if $_ =~ /name="_token" value="([^"]+)"/; puts $1; exit; end' "$login_page")"

if [[ -z "$token" ]]; then
  echo "FAIL could not read login CSRF token." >&2
  exit 1
fi

curl -sS -b "$cookies" -c "$cookies" \
  -X POST "${WEB_BASE_URL}/login" \
  -H 'Content-Type: application/x-www-form-urlencoded' \
  --data-urlencode "_token=${token}" \
  --data-urlencode "email=${EMAIL}" \
  --data-urlencode "password=${PASSWORD}" \
  -L > "$tmpdir/after-login.html"

paths=(
  "/home"
  "/sanad/dashboard"
  "/sanad/requests"
  "/sanad/requests/1"
  "/sanad/ai"
  "/payment"
)

forbidden_patterns=(
  "\\bKangoo\\b"
  "\\bHandyman\\b"
  "\\bHandymen\\b"
  "\\bCreate Booking\\b"
  "\\bTotal Bookings\\b"
  "\\bRecent Bookings\\b"
  "\\bBooking Status\\b"
  "\\bHandyman List\\b"
  "\\bHandyman Request List\\b"
  "\\bUnassigned Handyman\\b"
  "\\bHandyman Type List\\b"
  "\\bHandyman Ratings\\b"
  "\\bProvider List\\b"
  "\\bProvider Request List\\b"
  "\\bProvider Type List\\b"
  "\\bRecent Providers\\b"
)

failures=0

html_to_text() {
  perl -0777 -pe 's/<script.*?<\/script>//gs; s/<style.*?<\/style>//gs; s/<[^>]+>/\n/g; s/&nbsp;/ /g; s/&amp;/&/g; s/[ \t]+/ /g; s/\n{2,}/\n/g'
}

for path in "${paths[@]}"; do
  page_file="$tmpdir/$(echo "$path" | tr '/' '_').html"
  text_file="$tmpdir/$(echo "$path" | tr '/' '_').txt"
  curl -sS -b "$cookies" "${WEB_BASE_URL}${path}" -o "$page_file"
  html_to_text < "$page_file" > "$text_file"

  for pattern in "${forbidden_patterns[@]}"; do
    if grep -Eiq "$pattern" "$text_file"; then
      echo "FAIL ${path} contains forbidden visible terminology matching ${pattern}" >&2
      grep -Ein "$pattern" "$text_file" | head -5 >&2
      failures=$((failures + 1))
    fi
  done
done

if [[ "$failures" -gt 0 ]]; then
  echo "Sanad visible terminology QA failed with ${failures} issue(s)." >&2
  exit 1
fi

echo "Sanad visible terminology QA completed successfully against ${WEB_BASE_URL}."
