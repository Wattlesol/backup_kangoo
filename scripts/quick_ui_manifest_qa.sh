#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT_DIR"

routes_json="$(mktemp)"
trap 'rm -f "$routes_json"' EXIT
php artisan route:list --json > "$routes_json"

failures=0

require_route() {
    local route_name="$1"
    local label="$2"
    if php -r '$rows=json_decode(file_get_contents($argv[1]),true); foreach($rows as $row){if(($row["name"]??null)===$argv[2]) exit(0);} exit(1);' "$routes_json" "$route_name"; then
        printf 'PASS route: %s (%s)\n' "$label" "$route_name"
    else
        printf 'FAIL missing route: %s (%s)\n' "$label" "$route_name"
        failures=$((failures + 1))
    fi
}

require_view() {
    local view_path="$1"
    local label="$2"
    if [[ -s "$view_path" ]]; then
        printf 'PASS view: %s (%s)\n' "$label" "$view_path"
    else
        printf 'FAIL missing/empty view: %s (%s)\n' "$label" "$view_path"
        failures=$((failures + 1))
    fi
}

require_source() {
    local file="$1"
    local needle="$2"
    local label="$3"
    if grep -Fq "$needle" "$file"; then
        printf 'PASS source: %s\n' "$label"
    else
        printf 'FAIL source: %s (%s lacks %s)\n' "$label" "$file" "$needle"
        failures=$((failures + 1))
    fi
}

require_source_absent() {
    local file="$1"
    local needle="$2"
    local label="$3"
    if grep -Fq "$needle" "$file"; then
        printf 'FAIL source: %s (%s still contains %s)\n' "$label" "$file" "$needle"
        failures=$((failures + 1))
    else
        printf 'PASS source: %s\n' "$label"
    fi
}

printf '== Foundation ==\n'
require_view resources/views/landing-page/index.blade.php 'Landing page'
require_view resources/views/auth/login.blade.php 'Authentication'
require_view resources/views/partials/_body_header.blade.php 'Authenticated header'
require_view resources/views/partials/_body_sidebar.blade.php 'Role-aware sidebar'
require_source resources/views/partials/_body_sidebar.blade.php 'quick-sidebar-footer' 'Sidebar settings/logout footer'

printf '\n== Admin portal ==\n'
require_route sanad.dashboard 'Operations dashboard'
require_route sanad.requests.index 'Request queue'
require_route sanad.requests.show 'Request detail and lifecycle'
require_route sanad.assignments.index 'Assignment workspace'
require_route sanad.documents.queue 'Document queue'
require_route sanad.chat.workspace 'Unified chat workspace'
require_route sanad.ai.index 'AI operations console'
require_route sanad.ai.escalations.index 'AI escalations and monitoring'
require_route sanad.partner-performance 'Partner performance'
require_route payment.index 'Financial center'
require_route service.index 'Service catalog'
require_route service.create 'Service management forms'
require_route provider.index 'Partner directory'
require_route provider.detail 'Partner detail'
require_route handyman.index 'Employee directory'
require_route handyman.detail 'Employee detail'
require_route setting.index 'Operational settings'

printf '\n== Partner portal ==\n'
require_route provider.dashboard 'Partner dashboard'
require_route provider.order.index 'Assigned-order queue'
require_route provider.order.show 'Order detail'
require_route provider.kanban.index 'Operations Kanban'
require_route sanad.chat.workspace 'Partner chat workspace'
require_route provider.services.index 'Enabled services'
require_route provider.workflows.index 'Employee workflow list'
require_route provider.workflows.create 'Employee workflow editor'
require_route provider.employees.index 'Employee directory'
require_route provider.performance.index 'Performance'
require_route provider.financial.index 'Financial center'
require_route provider.notifications.index 'Notification center'
require_route provider.profile.index 'Partner profile'

printf '\n== Employee portal ==\n'
require_view resources/views/dashboard/handyman-dashboard.blade.php 'Employee dashboard'
require_route sanad.requests.index 'Assigned-work queue'
require_route sanad.requests.show 'Request detail and stage actions'
require_route sanad.documents.queue 'Document-request queue'
require_route sanad.chat.workspace 'Customer chat workspace'
require_route payment.index 'Permitted payment status'
require_source resources/views/partials/_body_sidebar.blade.php 'if (auth()->user()->user_type == "handyman")' 'All employees use scoped navigation'

printf '\n== Customer portal ==\n'
require_route customer-portal.dashboard 'Customer dashboard'
require_route customer-portal.catalog 'Service catalog'
require_route customer-portal.catalog.show 'Service detail'
require_route customer-portal.requests.create 'New-request flow'
require_route customer-portal.requests.index 'Request list'
require_route customer-portal.requests.show 'Request detail and timeline'
require_route customer-portal.vault 'Document vault'
require_route customer-portal.messages 'Messages'
require_route customer-portal.billing 'Billing and payments'
require_route customer-portal.support 'Complaints and support'
require_route customer-portal.notifications 'Notifications'
require_route customer-portal.profile 'Customer profile'
require_route customer-portal.ai 'AI assistant'

printf '\n== Cross-cutting contracts ==\n'
require_source app/Helper/helper.php 'localized_model_name' 'Localized model names'
require_source app/Http/Controllers/SanadCustomerPortalController.php "in_array(\$user->user_type, ['user', 'customer'], true)" 'Customer portal role gate'
require_source resources/views/partials/_body_header.blade.php 'quick-notification-menu' 'Customer notification header dropdown'
require_source_absent resources/views/partials/_body_sidebar.blade.php 'customer-portal.notifications' 'Customer sidebar omits Notification Center'
require_source_absent resources/views/partials/_body_sidebar.blade.php 'customer-portal.ai' 'Customer sidebar omits AI assistant'
require_source resources/views/partials/_body_sidebar.blade.php 'provider.notifications.index' 'Partner notification navigation'
require_source public/css/custom.css '.quick-role-hero' 'Shared role hero styling'
require_source public/css/custom.css '@media (max-width: 767.98px)' 'Mobile breakpoint styling'

if (( failures > 0 )); then
    printf '\nQuick UI manifest QA failed with %d issue(s).\n' "$failures"
    exit 1
fi

printf '\nQuick UI manifest QA passed.\n'
