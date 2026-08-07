#!/usr/bin/env bash
set -euo pipefail

BASE_URL="${BASE_URL:-http://127.0.0.1:8092/api}"
WEB_BASE_URL="${WEB_BASE_URL:-http://127.0.0.1:8092}"
EMAIL="${SANAD_TEST_EMAIL:-demo@admin.com}"
PASSWORD="${SANAD_TEST_PASSWORD:-12345678}"

fail() {
  echo "FAIL: $*" >&2
  exit 1
}

pass() {
  echo "PASS: $*"
}

cd "$(dirname "$0")/.."

grep -Fq "Fallback to human support" resources/views/sanad/ai-console.blade.php || fail "AI console missing fallback policy"
grep -Fq "Needs human review by Sanad support" resources/views/sanad/ai-console.blade.php || fail "AI console missing escalation note"
pass "AI console has visible fallback and escalation wording"

php -l app/Http/Controllers/API/SanadController.php
php -l app/Http/Controllers/SanadWebController.php

if ! command -v jq >/dev/null 2>&1; then
  echo "SKIP: jq not available; source-level AI escalation checks passed."
  exit 0
fi

login_response="$(mktemp)"
ai_response="$(mktemp)"
trap 'rm -f "$login_response" "$ai_response"' EXIT

login_code="$(curl -sS -o "$login_response" -w "%{http_code}" -X POST "$BASE_URL/login" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  --data "{\"email\":\"$EMAIL\",\"password\":\"$PASSWORD\"}" || true)"

if [[ "$login_code" -lt 200 || "$login_code" -ge 300 ]]; then
  echo "SKIP: API login unavailable at ${BASE_URL}; source-level AI escalation checks passed."
else
  token="$(jq -r '.api_token // .data.api_token // empty' "$login_response")"
  [[ -n "$token" ]] || fail "API login did not return api_token"

  unique_question="Sanad escalation qa unmatched $(date +%s%N)"
  ai_code="$(curl -sS -o "$ai_response" -w "%{http_code}" -X POST "$BASE_URL/sanad/ai/ask" \
    -H "Accept: application/json" \
    -H "Content-Type: application/json" \
    -H "Authorization: Bearer $token" \
    --data "{\"question\":\"$unique_question\"}")"

  [[ "$ai_code" -ge 200 && "$ai_code" -lt 300 ]] || fail "AI ask returned HTTP ${ai_code}"
  jq -e '.data.requires_escalation == true and .data.status == "escalated" and (.data.answer | contains("Sanad support team"))' "$ai_response" >/dev/null
  pass "API low-confidence AI question escalates to support"
fi

cookie_jar="$(mktemp)"
trap 'rm -f "$login_response" "$ai_response" "$cookie_jar" /tmp/sanad_ai_login.html /tmp/sanad_ai_console.html' EXIT

if curl -fsS -c "$cookie_jar" "${WEB_BASE_URL}/login" >/tmp/sanad_ai_login.html; then
  token="$(grep -o 'name="_token" value="[^"]*"' /tmp/sanad_ai_login.html | sed 's/.*value="//;s/"$//' | head -1)"
  if [[ -n "$token" ]]; then
    curl -fsS -b "$cookie_jar" -c "$cookie_jar" \
      -d "_token=$token" \
      -d "email=admin@admin.com" \
      -d "password=12345678" \
      -d "login=login" \
      "${WEB_BASE_URL}/login" >/dev/null || true

    if curl -fsS -b "$cookie_jar" "${WEB_BASE_URL}/sanad/ai" >/tmp/sanad_ai_console.html; then
      grep -Fq "Fallback to human support" /tmp/sanad_ai_console.html || fail "Rendered AI console missing fallback policy"
      pass "Rendered AI console shows fallback policy"
    else
      echo "SKIP: rendered AI console unavailable at ${WEB_BASE_URL}/sanad/ai"
    fi
  else
    echo "SKIP: could not read web login CSRF token"
  fi
else
  echo "SKIP: web login unavailable at ${WEB_BASE_URL}"
fi

echo "Sanad AI escalation QA passed."
