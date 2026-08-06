#!/usr/bin/env bash
set -euo pipefail

BASE_URL="${BASE_URL:-http://127.0.0.1:8091/api}"
EMAIL="${SANAD_TEST_EMAIL:-demo@admin.com}"
PASSWORD="${SANAD_TEST_PASSWORD:-12345678}"
USER_APP_DIR="${SANAD_USER_APP_DIR:-/Users/xain/Documents/kangoo/handyman_user_flutter_v11.13.2}"
ADMIN_APP_DIR="${SANAD_ADMIN_APP_DIR:-/Users/xain/Documents/kangoo/handyman_admin_flutter_app-v3.9.0}"

if ! command -v jq >/dev/null 2>&1; then
  echo "jq is required for this QA check." >&2
  exit 1
fi

tmpdir="$(mktemp -d)"
trap 'rm -rf "$tmpdir"' EXIT

TOKEN=""

request() {
  local method="$1"
  local endpoint="$2"
  local body="${3:-}"
  local output="$tmpdir/$(echo "$method-$endpoint" | tr '/?' '__').json"

  if [[ -n "$body" ]]; then
    http_code="$(curl -sS -o "$output" -w "%{http_code}" -X "$method" "$BASE_URL/$endpoint" \
      -H "Accept: application/json" \
      -H "Content-Type: application/json" \
      -H "Authorization: Bearer $TOKEN" \
      --data "$body")"
  else
    http_code="$(curl -sS -o "$output" -w "%{http_code}" -X "$method" "$BASE_URL/$endpoint" \
      -H "Accept: application/json" \
      -H "Authorization: Bearer $TOKEN")"
  fi

  if [[ "$http_code" -lt 200 || "$http_code" -ge 300 ]]; then
    echo "FAIL $method /$endpoint returned HTTP $http_code" >&2
    cat "$output" >&2
    exit 1
  fi

  jq empty "$output" >/dev/null
  echo "$output"
}

assert_file() {
  local path="$1"
  if [[ ! -f "$path" ]]; then
    echo "FAIL required file is missing: $path" >&2
    exit 1
  fi
}

assert_grep() {
  local pattern="$1"
  local path="$2"
  if ! grep -q "$pattern" "$path"; then
    echo "FAIL expected pattern '$pattern' in $path" >&2
    exit 1
  fi
}

login_response="$tmpdir/login.json"
login_code="$(curl -sS -o "$login_response" -w "%{http_code}" -X POST "$BASE_URL/login" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  --data "{\"email\":\"$EMAIL\",\"password\":\"$PASSWORD\"}")"

if [[ "$login_code" -lt 200 || "$login_code" -ge 300 ]]; then
  echo "FAIL login returned HTTP $login_code" >&2
  cat "$login_response" >&2
  exit 1
fi

TOKEN="$(jq -r '.api_token // .data.api_token // empty' "$login_response")"
if [[ -z "$TOKEN" ]]; then
  echo "FAIL login did not return api_token" >&2
  cat "$login_response" >&2
  exit 1
fi
echo "PASS authenticated API session"

foundation="$(request GET sanad/foundation)"
jq -e '
  .terminology.booking == "request"
  and (.roles | has("admin"))
  and (.roles | has("partner"))
  and (.roles | has("employee"))
  and (.roles | has("customer"))
  and (.request_lifecycle | index("submitted"))
  and (.request_lifecycle | index("completed"))
  and (.document_visibility | index("admin"))
  and (.document_visibility | index("user"))
  and .ai.enabled == true
' "$foundation" >/dev/null
echo "PASS foundation role/terminology/privacy metadata"

requests="$(request GET 'sanad/requests?per_page=5')"
jq -e 'has("pagination") and (.data | type == "array")' "$requests" >/dev/null
echo "PASS request list mobile contract"

request_id="$(jq -r '.data[0].id // empty' "$requests")"
if [[ -n "$request_id" ]]; then
  lifecycle="$(request POST "sanad/requests/$request_id/lifecycle" '{"sanad_stage":"quality_review","sanad_priority":"high"}')"
  jq -e '.data.sanad_stage == "quality_review" and .data.sanad_priority == "high"' "$lifecycle" >/dev/null
  echo "PASS request lifecycle update"
else
  echo "SKIP request lifecycle update: no visible request found for demo account"
fi

buzz="$(request POST sanad/buzz '{"recipient_role":"admin","priority":"urgent","message":"Sanad integrated QA buzz"}')"
buzz_id="$(jq -r '.data.id' "$buzz")"
test "$buzz_id" != "null"
buzz_ack="$(request POST "sanad/buzz/$buzz_id/acknowledge" '{}')"
jq -e '.data.status == "acknowledged"' "$buzz_ack" >/dev/null
echo "PASS Buzz create/acknowledge workflow"

doc="$(request POST sanad/document-vault '{"document_type":"privacy_check","visible_to":["admin"],"file_name":"integrated-qa.pdf","file_path":"integrated-qa.pdf"}')"
doc_id="$(jq -r '.data.id' "$doc")"
jq -e '.data.document_type == "privacy_check" and (.data.visible_to | index("admin"))' "$doc" >/dev/null
verified_doc="$(request POST "sanad/document-vault/$doc_id/verify" '{"verification_status":"approved"}')"
jq -e '.data.verification_status == "approved" and .data.approved_at != null' "$verified_doc" >/dev/null
doc_list="$(request GET 'sanad/document-vault?verification_status=approved&per_page=5')"
jq -e 'has("data")' "$doc_list" >/dev/null
echo "PASS document vault privacy metadata and admin verification"

chat="$(request POST sanad/chat-messages '{"message":"Sanad integrated QA chat","visible_to":["admin","user"]}')"
jq -e '.data.message == "Sanad integrated QA chat" and .data.thread.id' "$chat" >/dev/null
thread_id="$(jq -r '.data.thread.id' "$chat")"
threads="$(request GET "sanad/chat-threads?per_page=5")"
jq -e --argjson id "$thread_id" '.data.data? // .data | tostring | contains(($id|tostring))' "$threads" >/dev/null
echo "PASS secure chat thread/message workflow"

knowledge="$(request POST sanad/ai/knowledge '{"title":"Integrated QA Knowledge","category":"qa","content":"Integrated QA answer for Sanad assistant.","visible_to":["admin","user"],"is_active":true}')"
jq -e '.data.title == "Integrated QA Knowledge"' "$knowledge" >/dev/null
ai="$(request POST sanad/ai/ask '{"question":"What is the Integrated QA Knowledge answer?"}')"
jq -e '.data.answer | contains("Integrated QA answer")' "$ai" >/dev/null
echo "PASS AI knowledge/ask workflow"

payment_list="$(request GET 'payment-list?per_page=5')"
jq -e 'has("pagination") and has("data")' "$payment_list" >/dev/null
gateways="$(request GET payment-gateways)"
jq -e 'has("data")' "$gateways" >/dev/null
wallet_history="$(request GET 'wallet-history?per_page=5')"
jq -e 'has("pagination") and has("data")' "$wallet_history" >/dev/null
wallet_topup="$(request POST wallet-top-up '{"amount":1,"transcation_type":"integrated_qa","transcation_id":"integrated-qa"}')"
jq -e '.data.amount != null' "$wallet_topup" >/dev/null
wallet_balance="$(request GET user-wallet-balance)"
jq -e 'has("balance") or has("amount") or has("data")' "$wallet_balance" >/dev/null
echo "PASS payment/wallet API contracts"

assert_file "$USER_APP_DIR/lib/screens/sanad/my_sanad_screen.dart"
assert_file "$USER_APP_DIR/lib/model/sanad_models.dart"
assert_file "$USER_APP_DIR/lib/network/rest_apis.dart"
assert_file "$USER_APP_DIR/build/app/outputs/flutter-apk/app-debug.apk"
assert_grep "sanad/foundation" "$USER_APP_DIR/lib/network/rest_apis.dart"
assert_grep "sanad/requests" "$USER_APP_DIR/lib/network/rest_apis.dart"
assert_grep "SanadFoundation" "$USER_APP_DIR/lib/model/sanad_models.dart"
echo "PASS customer mobile source/artifact contract"

assert_file "$ADMIN_APP_DIR/lib/screens/sanad/sanad_operations_screen.dart"
assert_file "$ADMIN_APP_DIR/lib/model/sanad_models.dart"
assert_file "$ADMIN_APP_DIR/lib/networks/rest_apis.dart"
assert_file "$ADMIN_APP_DIR/build/app/outputs/flutter-apk/app-debug.apk"
assert_grep "sanad/foundation" "$ADMIN_APP_DIR/lib/networks/rest_apis.dart"
assert_grep "sanad/requests" "$ADMIN_APP_DIR/lib/networks/rest_apis.dart"
assert_grep "SanadFoundation" "$ADMIN_APP_DIR/lib/model/sanad_models.dart"
echo "PASS admin/provider/employee mobile source/artifact contract"

echo "Sanad integrated QA completed successfully against $BASE_URL"
