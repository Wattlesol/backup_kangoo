#!/usr/bin/env bash
set -euo pipefail

export PATH="/usr/local/bin:/opt/homebrew/bin:/usr/bin:/bin:/usr/sbin:/sbin:$PATH"

if ! command -v jq >/dev/null 2>&1; then
  echo "FAIL jq is required." >&2
  exit 1
fi

routes_file="$(mktemp)"
trap 'rm -f "$routes_file"' EXIT
php artisan route:list --json > "$routes_file"

assert_route() {
  local method="$1"
  local uri="$2"
  local name="${3:-}"
  local count

  count="$(jq --arg method "$method" --arg uri "$uri" --arg name "$name" '[.[] | select((.method | contains($method)) and .uri == $uri and ($name == "" or .name == $name))] | length' "$routes_file")"
  if [[ "$count" -ne 1 ]]; then
    echo "FAIL missing or duplicate route: ${method} ${uri} ${name}" >&2
    exit 1
  fi
}

assert_sanctum_route() {
  local method="$1"
  local uri="$2"
  assert_route "$method" "$uri"
  jq -e --arg method "$method" --arg uri "$uri" '
    .[] | select((.method | contains($method)) and .uri == $uri)
    | (.middleware | tostring | contains("Authenticate:sanctum"))
  ' "$routes_file" >/dev/null || {
    echo "FAIL API route is not protected by Sanctum: ${method} ${uri}" >&2
    exit 1
  }
}

while IFS='|' read -r method uri; do
  assert_sanctum_route "$method" "$uri"
done <<'ROUTES'
GET|api/sanad/foundation
GET|api/sanad/requests
POST|api/sanad/requests/{id}/lifecycle
GET|api/sanad/buzz
POST|api/sanad/buzz
POST|api/sanad/buzz/{id}/acknowledge
GET|api/sanad/document-vault
POST|api/sanad/document-vault
POST|api/sanad/document-vault/{id}/verify
GET|api/sanad/chat-threads
POST|api/sanad/chat-messages
GET|api/sanad/requests/{id}/communication
POST|api/sanad/requests/{id}/communication
POST|api/sanad/requests/{id}/communication/{threadId}/read
POST|api/sanad/requests/{id}/document-requests
POST|api/sanad/ai/ask
POST|api/sanad/ai/knowledge
GET|api/booking-list
POST|api/booking-detail
POST|api/booking-update
POST|api/update-profile
POST|api/user-update-status
POST|api/handyman-update-available-status
POST|api/save-payment
GET|api/payment-history
POST|api/transfer-payment
POST|api/provider-document-save
POST|api/provider-document-delete/{id}
POST|api/provider-document-action
POST|api/booking-assigned
POST|api/remove-file
ROUTES

while IFS='|' read -r method uri name; do
  assert_route "$method" "$uri" "$name"
done <<'ROUTES'
GET|sanad/dashboard|sanad.dashboard
GET|sanad/requests|sanad.requests.index
GET|sanad/requests/{id}|sanad.requests.show
GET|sanad/assignments|sanad.assignments.index
GET|sanad/request-documents|sanad.documents.queue
GET|sanad/chat-workspace|sanad.chat.workspace
GET|sanad/chat-workspace/snapshot|sanad.chat.workspace.snapshot
GET|sanad/ai|sanad.ai.index
GET|sanad/ai/escalations|sanad.ai.escalations.index
GET|sanad/partner-performance|sanad.partner-performance
GET|customer-dashboard|customer-portal.dashboard
GET|customer-dashboard/catalog|customer-portal.catalog
GET|customer-dashboard/catalog/{service}|customer-portal.catalog.show
GET|customer-dashboard/requests/create|customer-portal.requests.create
POST|customer-dashboard/requests|customer-portal.requests.store
GET|customer-dashboard/requests|customer-portal.requests.index
GET|customer-dashboard/requests/{id}|customer-portal.requests.show
GET|customer-dashboard/document-vault|customer-portal.vault
GET|customer-dashboard/messages|customer-portal.messages
GET|customer-dashboard/notifications|customer-portal.notifications
GET|customer-dashboard/billing|customer-portal.billing
GET|customer-dashboard/support|customer-portal.support
GET|customer-dashboard/profile|customer-portal.profile
GET|provider-dashboard/dashboard|provider.dashboard
GET|provider-dashboard/orders|provider.order.index
GET|provider-dashboard/orders/{id}|provider.order.show
GET|provider-dashboard/kanban|provider.kanban.index
GET|provider-dashboard/services|provider.services.index
GET|provider-dashboard/workflows|provider.workflows.index
GET|provider-dashboard/employees|provider.employees.index
GET|provider-dashboard/performance|provider.performance.index
GET|provider-dashboard/financial-center|provider.financial.index
GET|provider-dashboard/notification-center|provider.notifications.index
GET|provider-dashboard/profile|provider.profile.index
ROUTES

controller="app/Http/Controllers/API/SanadController.php"
grep -Fq 'authorizeWorkflowMutation' "$controller"
grep -Fq 'authorizeBuzzMutation' "$controller"
grep -Fq 'visibleChatThreadQuery' "$controller"
grep -Fq 'allowedVisibility' "$controller"
grep -Fq "'brand' => config('sanad.brand')" "$controller"
grep -Fq "'name' => 'Quick'" config/sanad.php

php -l "$controller" >/dev/null
php -l app/Models/SanadChatThread.php >/dev/null
php -l app/Models/Booking.php >/dev/null

booking_api_controller="app/Http/Controllers/API/BookingController.php"
grep -Fq -- "->myBooking()" "$booking_api_controller"
grep -Fq "in_array(auth()->user()->user_type, ['admin', 'provider', 'handyman'], true)" "$booking_api_controller"
grep -Fq "BookingServiceAddonMapping::where('booking_id', \$bookingdata->id)->findOrFail" "$booking_api_controller"
grep -Fq "\$data['customer_id']" "$booking_api_controller"
grep -Fq "BookingRating::where('customer_id', auth()->id())->findOrFail" "$booking_api_controller"
grep -Fq "Booking::where('customer_id', auth()->id())->findOrFail" "$booking_api_controller"
php -l "$booking_api_controller" >/dev/null

user_api_controller="app/Http/Controllers/API/User/UserController.php"
grep -Fq "\$user = \\Auth::user();" "$user_api_controller"
grep -Fq "collect(\$validated)->except" "$user_api_controller"
grep -Fq "hasAnyRole(['admin', 'demo_admin', 'provider'])" "$user_api_controller"
grep -Fq "auth()->user()->user_type === 'handyman'" "$user_api_controller"
php -l "$user_api_controller" >/dev/null

payment_api_controller="app/Http/Controllers/API/PaymentController.php"
grep -Fq "Booking::query()->myBooking()->findOrFail" "$payment_api_controller"
grep -Fq "\$data['customer_id'] = \$booking->customer_id" "$payment_api_controller"
grep -Fq "\$data['total_amount'] = (float) \$booking->total_amount" "$payment_api_controller"
grep -Fq "Payment::query()->myPayment()->findOrFail" "$payment_api_controller"
grep -Fq "DB::transaction" "$payment_api_controller"
grep -Fq "PaymentHistory::where('booking_id', \$booking->id)->findOrFail" "$payment_api_controller"
grep -Fq "\$booking->handymanAdded->contains('handyman_id', \$actor->id)" "$payment_api_controller"
php -l "$payment_api_controller" >/dev/null

provider_document_controller="app/Http/Controllers/ProviderDocumentController.php"
grep -Fq "scopedDocumentQuery" "$provider_document_controller"
grep -Fq "authorizeDocumentActor(true)" "$provider_document_controller"
grep -Fq "\$data['provider_id'] = auth()->id()" "$provider_document_controller"
grep -Fq "\$this->scopedDocumentQuery()->findOrFail" "$provider_document_controller"
php -l "$provider_document_controller" >/dev/null
php -l app/Http/Requests/ProviderDocumentRequest.php >/dev/null

booking_web_controller="app/Http/Controllers/BookingController.php"
grep -Fq "Booking::query()->myBooking()->findOrFail(\$request->id)" "$booking_web_controller"
grep -Fq "hasAnyRole(['admin', 'demo_admin', 'provider'])" "$booking_web_controller"
grep -Fq "where('provider_id', \$partnerId)" "$booking_web_controller"
grep -Fq "Every selected Employee must belong to the assigned Partner" "$booking_web_controller"
php -l "$booking_web_controller" >/dev/null

home_controller="app/Http/Controllers/HomeController.php"
grep -Fq "authorizeFileRemoval" "$home_controller"
grep -Fq "ProviderDocument::where('provider_id', \$user->id)->whereKey(\$id)->exists()" "$home_controller"
grep -Fq "Booking::query()->myBooking()->whereKey" "$home_controller"
grep -Fq "\$model instanceof Bank" "$home_controller"
php -l "$home_controller" >/dev/null

dashboard_api_controller="app/Http/Controllers/API/DashboardController.php"
service_api_controller="app/Http/Controllers/API/ServiceController.php"
service_api_resource="app/Http/Resources/API/ServiceResource.php"
public_user_resource="app/Http/Resources/API/PublicUserResource.php"
grep -Fq "\$authenticatedCustomer = auth('sanctum')->user()" "$dashboard_api_controller"
grep -Fq "\$customerId = \$authenticatedCustomer->id" "$dashboard_api_controller"
grep -Fq "\$authenticatedCustomer = auth('sanctum')->user()" "$service_api_controller"
grep -Fq "\$authenticatedCustomer = auth('sanctum')->user()" "$service_api_resource"
grep -Fq "PublicUserResource::collection" app/Http/Controllers/API/User/UserController.php
grep -Fq "\$request->merge(['provider_id' => \$apiViewer->id])" app/Http/Controllers/API/User/UserController.php
grep -Fq "\$request->has('booking_id') && \$isOperationalAdminRequest" app/Http/Controllers/API/User/UserController.php
grep -Fq "\$user->user_type === 'handyman'" app/Http/Controllers/API/User/UserController.php
grep -Fq "new PublicUserResource" "$service_api_controller"
for forbidden_public_field in email contact_number address player_ids provider_id uid last_notification_seen; do
  if grep -Fq "'$forbidden_public_field'" "$public_user_resource"; then
    echo "FAIL public user resource exposes private field: $forbidden_public_field" >&2
    exit 1
  fi
done
php -l "$dashboard_api_controller" >/dev/null
php -l "$service_api_controller" >/dev/null
php -l "$service_api_resource" >/dev/null
php -l "$public_user_resource" >/dev/null

echo "PASS Quick UI contract: 31 Sanctum workflow endpoints, protected ownership/financial/media barriers, and optional-auth privacy on public payloads are present."
