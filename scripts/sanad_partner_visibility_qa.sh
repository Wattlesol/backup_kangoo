#!/usr/bin/env bash
set -euo pipefail

export PATH="/usr/local/bin:/opt/homebrew/bin:/usr/bin:/bin:/usr/sbin:/sbin:$PATH"

WEB_BASE_URL="${SANAD_WEB_BASE_URL:-http://127.0.0.1:8092}"
CUSTOMER_EMAIL="${SANAD_CUSTOMER_WEB_TEST_EMAIL:-demo@user.com}"
CUSTOMER_PASSWORD="${SANAD_CUSTOMER_WEB_TEST_PASSWORD:-12345678}"

tmpdir="$(mktemp -d)"
trap 'rm -rf "$tmpdir"' EXIT

html_to_text() {
  perl -0777 -pe 's/<script.*?<\/script>//gs; s/<style.*?<\/style>//gs; s/<[^>]+>/\n/g; s/&nbsp;/ /g; s/&amp;/&/g; s/[ \t]+/ /g; s/\n{2,}/\n/g'
}

assert_redirects_to_service_list() {
  local path="$1"
  local target

  target="$(curl -sS -o /dev/null -w '%{redirect_url}' "${WEB_BASE_URL}${path}")"

  if [[ "$target" != "${WEB_BASE_URL}/service-list" ]]; then
    echo "FAIL ${path} should redirect to /service-list, got '${target}'." >&2
    exit 1
  fi
}

assert_no_public_partner_links() {
  local path="$1"
  local page_file="$tmpdir/$(echo "$path" | tr '/' '_').html"
  local text_file="$tmpdir/$(echo "$path" | tr '/' '_').txt"

  curl -sS "${WEB_BASE_URL}${path}" -o "$page_file"
  html_to_text < "$page_file" > "$text_file"

  if grep -Eiq '(^|[[:space:]])Providers($|[[:space:]])|(^|[[:space:]])Store($|[[:space:]])' "$text_file"; then
    echo "FAIL ${path} exposes public Provider or Store navigation." >&2
    grep -Ein '(^|[[:space:]])Providers($|[[:space:]])|(^|[[:space:]])Store($|[[:space:]])' "$text_file" | head -10 >&2
    exit 1
  fi

  if grep -Eiq 'provider-detail|provider-list|handyman-detail|post-job' "$page_file"; then
    echo "FAIL ${path} exposes provider/employee/post-job marketplace links." >&2
    grep -Ein 'provider-detail|provider-list|handyman-detail|post-job' "$page_file" | head -10 >&2
    exit 1
  fi
}

assert_no_customer_direct_partner_contact() {
  local login_page="$tmpdir/login.html"
  local cookies="$tmpdir/cookies.txt"
  local page_file="$tmpdir/customer-request.html"
  local text_file="$tmpdir/customer-request.txt"
  local token

  curl -sS -c "$cookies" "${WEB_BASE_URL}/login-page" > "$login_page"
  token="$(ruby -ne 'if $_ =~ /name="_token" value="([^"]+)"/; puts $1; exit; end' "$login_page")"

  if [[ -z "$token" ]]; then
    echo "FAIL could not read customer login CSRF token." >&2
    exit 1
  fi

  curl -sS -b "$cookies" -c "$cookies" \
    -X POST "${WEB_BASE_URL}/user-login" \
    -H 'Content-Type: application/x-www-form-urlencoded' \
    --data-urlencode "_token=${token}" \
    --data-urlencode "email=${CUSTOMER_EMAIL}" \
    --data-urlencode "password=${CUSTOMER_PASSWORD}" \
    -L > "$tmpdir/after-customer-login.html"

  curl -sS -b "$cookies" "${WEB_BASE_URL}/booking-detail/1" -o "$page_file"
  html_to_text < "$page_file" > "$text_file"

  if grep -Eiq 'provider-detail|handyman-detail|demo@provider\.com|demo@employee\.com|demo@handyman\.com|Provider Demo|Employee Demo|Handyman Demo' "$page_file" "$text_file"; then
    echo "FAIL customer request detail exposes direct partner/employee profile or contact details." >&2
    grep -Ein 'provider-detail|handyman-detail|demo@provider\.com|demo@employee\.com|demo@handyman\.com|Provider Demo|Employee Demo|Handyman Demo' "$page_file" "$text_file" | head -10 >&2
    exit 1
  fi
}

assert_no_public_partner_links "/"
assert_no_public_partner_links "/service-list"

assert_redirects_to_service_list "/provider-list"
assert_redirects_to_service_list "/provider-detail/1"
assert_redirects_to_service_list "/handyman-detail/1"
assert_redirects_to_service_list "/post-job"
assert_redirects_to_service_list "/post-job-detail/1"
assert_redirects_to_service_list "/book-post-job?id=1"

assert_no_customer_direct_partner_contact

echo "Sanad partner visibility QA completed successfully against ${WEB_BASE_URL}."
