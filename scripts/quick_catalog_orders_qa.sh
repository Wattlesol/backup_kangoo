#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT_DIR"

fail() {
    printf 'FAIL: %s\n' "$1" >&2
    exit 1
}

require_pattern() {
    local pattern="$1"
    local file="$2"
    local message="$3"
    rg -q -- "$pattern" "$file" || fail "$message"
}

reject_pattern() {
    local pattern="$1"
    local file="$2"
    local message="$3"
    if rg -q -- "$pattern" "$file"; then
        fail "$message"
    fi
}

CATEGORY_FORM="resources/views/category/create.blade.php"
CATEGORY_LIST="resources/views/category/index.blade.php"
CATEGORY_CONTROLLER="app/Http/Controllers/CategoryController.php"
CATEGORY_RESOURCE="app/Http/Resources/API/CategoryResource.php"
SERVICE_FORM="resources/views/service/create.blade.php"
SERVICE_REQUEST="app/Http/Requests/ServiceRequest.php"
SERVICE_RESOURCE="app/Http/Resources/API/ServiceResource.php"
ORDER_LIST="resources/views/booking/index.blade.php"
ORDER_CONTROLLER="app/Http/Controllers/BookingController.php"
BUNDLE_FORM="resources/views/servicepackage/create.blade.php"
BUNDLE_CONTROLLER="app/Http/Controllers/ServicePackageController.php"
BUNDLE_RESOURCE="app/Http/Resources/API/ServicePackageResource.php"
ADDON_FORM="resources/views/serviceaddon/create.blade.php"
ADDON_CONTROLLER="app/Http/Controllers/ServiceAddonController.php"
ADDON_RESOURCE="app/Http/Resources/API/ServiceAddonResource.php"

for field in name_en name_ar display_order status category_icon category_image is_featured; do
    require_pattern "$field" "$CATEGORY_FORM" "Category form is missing $field"
done
for field in name_ar display_order status is_featured; do
    require_pattern "$field" "$CATEGORY_LIST" "Category list is missing $field"
    require_pattern "$field" "$CATEGORY_RESOURCE" "Category API resource is missing $field"
done
require_pattern "messages.english_name" "$CATEGORY_LIST" "Category list is missing its English name column"
require_pattern "name_en" "$CATEGORY_RESOURCE" "Category API resource is missing name_en"
require_pattern "storeMediaFile.*category_image" "$CATEGORY_CONTROLLER" "Category image is not persisted"
require_pattern "storeMediaFile.*category_icon" "$CATEGORY_CONTROLLER" "Category icon is not persisted"

for field in name_en name_ar category_id status government_entity required_documents estimated_completion_time government_fee service_fee service_instructions terms_and_conditions; do
    require_pattern "$field" "$SERVICE_FORM" "Service form is missing $field"
    require_pattern "$field" "$SERVICE_REQUEST" "Service validation is missing $field"
    require_pattern "$field" "$SERVICE_RESOURCE" "Service API resource is missing $field"
done
reject_pattern "Form::select\('provider_id'" "$SERVICE_FORM" "Legacy Partner selector remains in service admin input"
reject_pattern "provider_address_id|add_provider_address_link" "$SERVICE_FORM" "Legacy Partner Address input remains in service editor"
reject_pattern "Form::select\('visit_type'" "$SERVICE_FORM" "Legacy Visit Type input remains in service editor"

for column in customer_id service_id provider_id handyman_id priority expected_completion_at status; do
    require_pattern "data: '$column'|data:'$column'" "$ORDER_LIST" "Orders list is missing $column"
done
require_pattern "data: 'order_number'|data:'order_number'" "$ORDER_LIST" "Orders list is missing its order reference"
require_pattern "data: 'payment_id'|data:'payment_id'" "$ORDER_LIST" "Orders list is missing payment status"
for field in sanad_priority expected_completion_at payment_status; do
    require_pattern "$field" "$ORDER_CONTROLLER" "Orders data source is missing $field"
done

for field in name name_ar price status package_attachment service_id_data; do
    require_pattern "$field" "$BUNDLE_FORM" "Service Bundle form is missing $field"
done
for field in name name_ar bundle_price original_price discount_amount discount_percentage services; do
    require_pattern "$field" "$BUNDLE_RESOURCE" "Service Bundle API resource is missing $field"
done
require_pattern "packageServices.*create" "$BUNDLE_CONTROLLER" "Service Bundle membership is not persisted"
reject_pattern "legacy_user_id|pricelist_id|car_number|package_type|sanad-legacy-field" "$BUNDLE_FORM" "Legacy package inputs remain in the Service Bundle editor"

for field in name name_ar price status category_ids service_ids serviceaddon_image; do
    require_pattern "$field" "$ADDON_FORM" "Additional Service form is missing $field"
    require_pattern "$field" "$ADDON_CONTROLLER" "Additional Service persistence is missing $field"
done
for field in name name_ar price status category_ids service_ids serviceaddon_image; do
    require_pattern "$field" "$ADDON_RESOURCE" "Additional Service API resource is missing $field"
done

printf 'PASS: category master data form, persistence, list, and API contract\n'
printf 'PASS: service master data form, validation, and API contract\n'
printf 'PASS: obsolete service Partner, Address, and Visit Type inputs are absent\n'
printf 'PASS: orders list includes operational ownership, priority, completion, status, and payment fields\n'
printf 'PASS: Service Bundles use the repurposed form, persistence, and API contract without legacy inputs\n'
printf 'PASS: Additional Services include bilingual targeting, persistence, media, and API fields\n'
