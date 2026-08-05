#!/usr/bin/env bash
set -euo pipefail

BASE_URL="${BASE_URL:-http://127.0.0.1:8091/api}"
EMAIL="${SANAD_TEST_EMAIL:-demo@admin.com}"
PASSWORD="${SANAD_TEST_PASSWORD:-12345678}"

if ! command -v jq >/dev/null 2>&1; then
  echo "jq is required for this smoke test." >&2
  exit 1
fi

tmpdir="$(mktemp -d)"
trap 'rm -rf "$tmpdir"' EXIT

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

foundation="$(request GET sanad/foundation)"
jq -e '.terminology.booking == "request" and (.request_lifecycle | index("submitted")) and .ai.enabled == true' "$foundation" >/dev/null
echo "PASS foundation metadata"

requests="$(request GET 'sanad/requests?per_page=5')"
jq -e 'has("pagination") and has("data")' "$requests" >/dev/null
echo "PASS request list contract"

buzz="$(request POST sanad/buzz '{"recipient_role":"admin","priority":"urgent","message":"Sanad smoke-test buzz"}')"
buzz_id="$(jq -r '.data.id' "$buzz")"
test "$buzz_id" != "null"
echo "PASS buzz create"

buzz_list="$(request GET 'sanad/buzz?status=unread&per_page=5')"
jq -e 'has("data")' "$buzz_list" >/dev/null
echo "PASS buzz list"

buzz_ack="$(request POST "sanad/buzz/$buzz_id/acknowledge" '{}')"
jq -e '.data.status == "acknowledged"' "$buzz_ack" >/dev/null
echo "PASS buzz acknowledge"

vault="$(request POST sanad/document-vault '{"document_type":"government_verification","visible_to":["admin","user"],"file_name":"smoke-test.pdf","file_path":"smoke-test.pdf"}')"
jq -e '.data.document_type == "government_verification"' "$vault" >/dev/null
echo "PASS document vault create"

vault_list="$(request GET 'sanad/document-vault?per_page=5')"
jq -e 'has("data")' "$vault_list" >/dev/null
echo "PASS document vault list"

chat="$(request POST sanad/chat-messages '{"message":"Sanad smoke-test chat message","visible_to":["admin","user"]}')"
jq -e '.data.message == "Sanad smoke-test chat message" and .data.thread.id' "$chat" >/dev/null
thread_id="$(jq -r '.data.thread.id' "$chat")"
echo "PASS chat message create"

threads="$(request GET "sanad/chat-threads?per_page=5")"
jq -e 'has("data")' "$threads" >/dev/null
echo "PASS chat thread list"

knowledge="$(request POST sanad/ai/knowledge '{"title":"Smoke Test Knowledge","category":"support","content":"Smoke test answer for Sanad assistant.","visible_to":["admin","user"],"is_active":true}')"
jq -e '.data.title == "Smoke Test Knowledge"' "$knowledge" >/dev/null
echo "PASS AI knowledge create"

ai="$(request POST sanad/ai/ask '{"question":"What is the Smoke Test Knowledge answer?"}')"
jq -e '.data.answer | contains("Smoke test answer")' "$ai" >/dev/null
echo "PASS AI ask"

echo "Sanad API smoke test completed successfully against $BASE_URL"
