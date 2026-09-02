#!/usr/bin/env bash
set -euo pipefail

export PATH="/usr/local/bin:/opt/homebrew/bin:/usr/bin:/bin:/usr/sbin:/sbin:$PATH"
BASE_URL="${QUICK_API_BASE_URL:-http://127.0.0.1:8000/api}"
TOKEN_NAME="quick-contract-qa-$$"
export TOKEN_NAME

cleanup_token() {
  php -r '
    error_reporting(E_ERROR | E_PARSE);
    require "vendor/autoload.php";
    $app = require "bootstrap/app.php";
    $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
    Laravel\Sanctum\PersonalAccessToken::where("name", "like", getenv("TOKEN_NAME") . "%")->delete();
  ' >/dev/null 2>&1 || true
}
trap cleanup_token EXIT

payload="$(php -r '
  error_reporting(E_ERROR | E_PARSE);
  require "vendor/autoload.php";
  $app = require "bootstrap/app.php";
  $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
  $customer = App\Models\User::whereIn("user_type", ["user", "customer"])
    ->whereHas("booking")
    ->where("status", 1)
    ->firstOrFail();
  $booking = App\Models\Booking::where("customer_id", $customer->id)->firstOrFail();
  $otherThread = App\Models\SanadChatThread::whereNotNull("booking_id")
    ->whereHas("booking", fn ($query) => $query->where("customer_id", "!=", $customer->id))
    ->firstOrFail();
  $provider = App\Models\User::whereIn("user_type", ["provider", "partner"])
    ->whereHas("providerBooking")
    ->where("status", 1)
    ->firstOrFail();
  $providerBooking = App\Models\Booking::where("provider_id", $provider->id)->firstOrFail();
  $providerOtherThread = App\Models\SanadChatThread::whereNotNull("booking_id")
    ->whereHas("booking", fn ($query) => $query->where(function ($scope) use ($provider) {
      $scope->whereNull("provider_id")->orWhere("provider_id", "!=", $provider->id);
    }))
    ->firstOrFail();
  echo json_encode([
    "token" => $customer->createToken(getenv("TOKEN_NAME") . "-customer")->plainTextToken,
    "customer_id" => $customer->id,
    "booking_id" => $booking->id,
    "other_thread_id" => $otherThread->id,
    "provider_token" => $provider->createToken(getenv("TOKEN_NAME") . "-provider")->plainTextToken,
    "provider_id" => $provider->id,
    "provider_booking_id" => $providerBooking->id,
    "provider_other_thread_id" => $providerOtherThread->id,
  ]);
')"

TOKEN="$(jq -r '.token' <<<"$payload")"
CUSTOMER_ID="$(jq -r '.customer_id' <<<"$payload")"
BOOKING_ID="$(jq -r '.booking_id' <<<"$payload")"
OTHER_THREAD_ID="$(jq -r '.other_thread_id' <<<"$payload")"
PROVIDER_TOKEN="$(jq -r '.provider_token' <<<"$payload")"
PROVIDER_ID="$(jq -r '.provider_id' <<<"$payload")"
PROVIDER_BOOKING_ID="$(jq -r '.provider_booking_id' <<<"$payload")"
PROVIDER_OTHER_THREAD_ID="$(jq -r '.provider_other_thread_id' <<<"$payload")"
QA_DIR="$(mktemp -d)"

request_code() {
  local method="$1"
  local endpoint="$2"
  local body="${3:-}"
  local output="$QA_DIR/$(echo "$method-$endpoint" | tr '/{}' '___').json"

  if [[ -n "$body" ]]; then
    curl -sS -o "$output" -w '%{http_code}' -X "$method" "$BASE_URL/$endpoint" \
      -H 'Accept: application/json' \
      -H 'Content-Type: application/json' \
      -H "Authorization: Bearer $TOKEN" \
      --data "$body"
  else
    curl -sS -o "$output" -w '%{http_code}' -X "$method" "$BASE_URL/$endpoint" \
      -H 'Accept: application/json' \
      -H "Authorization: Bearer $TOKEN"
  fi
}

provider_request_code() {
  local method="$1"
  local endpoint="$2"
  local body="${3:-}"
  local output="$QA_DIR/provider-$(echo "$method-$endpoint" | tr '/{}' '___').json"

  if [[ -n "$body" ]]; then
    curl -sS -o "$output" -w '%{http_code}' -X "$method" "$BASE_URL/$endpoint" \
      -H 'Accept: application/json' \
      -H 'Content-Type: application/json' \
      -H "Authorization: Bearer $PROVIDER_TOKEN" \
      --data "$body"
  else
    curl -sS -o "$output" -w '%{http_code}' -X "$method" "$BASE_URL/$endpoint" \
      -H 'Accept: application/json' \
      -H "Authorization: Bearer $PROVIDER_TOKEN"
  fi
}

foundation_code="$(request_code GET sanad/foundation)"
[[ "$foundation_code" == 200 ]]
jq -e '.brand.name == "Quick" and .brand.name_ar == "كويك"' "$QA_DIR/GET-sanad_foundation.json" >/dev/null
echo "PASS Quick foundation brand contract"

requests_code="$(request_code GET 'sanad/requests?per_page=100')"
[[ "$requests_code" == 200 ]]
jq -e --argjson customer "$CUSTOMER_ID" '(.data | length) > 0 and all(.data[]; .customer_id == $customer)' "$QA_DIR/GET-sanad_requests?per_page=100.json" >/dev/null
echo "PASS customer request list is customer-scoped"

lifecycle_code="$(request_code POST "sanad/requests/$BOOKING_ID/lifecycle" '{"sanad_stage":"in_progress","sanad_priority":"normal"}')"
[[ "$lifecycle_code" == 403 ]]
echo "PASS customer cannot mutate request lifecycle"

buzz_code="$(request_code POST sanad/buzz "{\"booking_id\":$BOOKING_ID,\"recipient_role\":\"admin\",\"message\":\"Unauthorized test\"}")"
[[ "$buzz_code" == 403 ]]
echo "PASS customer cannot create operational Buzz alerts"

chat_code="$(request_code POST sanad/chat-messages "{\"thread_id\":$OTHER_THREAD_ID,\"message\":\"Unauthorized test\"}")"
[[ "$chat_code" == 404 ]]
echo "PASS customer cannot post to an unrelated chat thread"

vault_code="$(request_code POST sanad/document-vault '{"document_type":"authorization-test","provider_id":2,"visible_to":["admin"]}')"
[[ "$vault_code" == 403 ]]
echo "PASS customer cannot attach vault data to a partner identity"

knowledge_code="$(request_code POST sanad/ai/knowledge '{"title":"Unauthorized","content":"Unauthorized"}')"
[[ "$knowledge_code" == 403 ]]
echo "PASS customer cannot manage AI knowledge"

provider_requests_code="$(provider_request_code GET 'sanad/requests?per_page=100')"
[[ "$provider_requests_code" == 200 ]]
jq -e --argjson provider "$PROVIDER_ID" '(.data | length) > 0 and all(.data[]; .provider_id == $provider or .chat_owner_user_id == $provider)' "$QA_DIR/provider-GET-sanad_requests?per_page=100.json" >/dev/null
echo "PASS partner request list is partner-scoped"

provider_chat_code="$(provider_request_code POST sanad/chat-messages "{\"thread_id\":$PROVIDER_OTHER_THREAD_ID,\"message\":\"Unauthorized test\"}")"
[[ "$provider_chat_code" == 404 ]]
echo "PASS partner cannot post to an unrelated chat thread"

provider_vault_code="$(provider_request_code POST sanad/document-vault "{\"document_type\":\"authorization-test\",\"provider_id\":$((PROVIDER_ID + 1)),\"visible_to\":[\"admin\"]}")"
[[ "$provider_vault_code" == 403 ]]
echo "PASS partner cannot attach vault data to another partner identity"

provider_knowledge_code="$(provider_request_code POST sanad/ai/knowledge '{"title":"Unauthorized","content":"Unauthorized"}')"
[[ "$provider_knowledge_code" == 403 ]]
echo "PASS partner cannot manage AI knowledge"

echo "Quick API authorization QA passed against $BASE_URL"
