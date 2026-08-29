#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT_DIR"

failures=0

scan_forbidden() {
    local pattern="$1"
    local label="$2"
    local files
    # Search every planned Quick surface. Legacy marketplace-only booking/package
    # views are intentionally excluded until a core approved flow links to them.
    files="$(git grep -rn -I -E "$pattern" \
        resources/views/customer-portal/ \
        resources/views/landing-page/ \
        resources/views/auth/ \
        resources/views/components/ \
        resources/views/dashboard/ \
        resources/views/provider/ \
        resources/views/sanad/ \
        resources/views/service/ \
        resources/views/servicepackage/ \
        resources/views/serviceaddon/ \
        resources/views/payment/ \
        resources/views/booking/index.blade.php \
        resources/views/booking/details.blade.php \
        resources/views/partials/_body_header.blade.php \
        resources/views/partials/_body_sidebar.blade.php \
        2>/dev/null || true)"
    if [[ -n "$files" ]]; then
        printf 'FAIL visible terminology leaked: %s\n%s\n' "$label" "$files"
        failures=$((failures + 1))
    else
        printf 'PASS no leaked terminology: %s\n' "$label"
    fi
}

printf '== Static Terminology & Brand QA ==\n'
scan_forbidden '>\s*Sanad\b' 'Visible "Sanad" in customer/landing views'
scan_forbidden '>\s*سند\b' 'Visible "سند" in customer/landing views'
scan_forbidden '\bKangoo\b' 'Visible "Kangoo"'
scan_forbidden '\bCreate Booking\b' 'Visible "Create Booking"'
scan_forbidden '\bTotal Bookings\b' 'Visible "Total Bookings"'
scan_forbidden '\bRecent Bookings\b' 'Visible "Recent Bookings"'
scan_forbidden '>[^<]*(Package List|Packagee List|Package Name|Package Type)[^<]*<' 'Visible legacy Package terminology'
scan_forbidden '>[^<]*Add-ons?[^<]*<' 'Visible legacy Add-on terminology'
scan_forbidden '>[^<]*(Booking Number|Total Bookings|Recent Bookings)[^<]*<' 'Visible legacy Booking terminology'

if (( failures > 0 )); then
    printf '\nQuick static terminology QA failed with %d issue(s).\n' "$failures"
    exit 1
fi

printf '\nQuick static terminology QA passed.\n'
