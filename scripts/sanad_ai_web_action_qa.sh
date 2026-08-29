#!/usr/bin/env bash
set -euo pipefail

export PATH="/usr/local/bin:/opt/homebrew/bin:/usr/bin:/bin:/usr/sbin:/sbin:$PATH"

BASE_WEB_URL="${SANAD_WEB_BASE_URL:-http://127.0.0.1:8092}"
ADMIN_EMAIL="${SANAD_ADMIN_WEB_TEST_EMAIL:-admin@admin.com}"
PASSWORD="${SANAD_WEB_TEST_PASSWORD:-12345678}"

if ! command -v curl >/dev/null 2>&1; then
  echo "FAIL curl is required." >&2
  exit 1
fi

tmpdir="$(mktemp -d)"
trap 'rm -rf "$tmpdir"' EXIT

fail() {
  echo "FAIL $*" >&2
  exit 1
}

pass() {
  echo "PASS $*"
}

html_to_text() {
  perl -0777 -pe 's/<script.*?<\/script>//gs; s/<style.*?<\/style>//gs; s/<[^>]+>/\n/g; s/&nbsp;/ /g; s/&amp;/\&/g; s/&#039;/'"'"'/g; s/[ \t]+/ /g; s/\n{2,}/\n/g'
}

extract_csrf() {
  local file="$1"
  sed -n 's/.*name="_token" value="\([^"]*\)".*/\1/p' "$file" | head -n 1
}

assert_clean_page() {
  local file="$1"
  local label="$2"

  if grep -Eiq 'exception_class|fatal error|stack trace|queryexception|whoops' "$file"; then
    fail "${label} contains server error markers"
  fi
}

assert_contains() {
  local file="$1"
  local marker="$2"
  local label="$3"

  grep -Fqi "$marker" "$file" || fail "${label} missing marker: ${marker}"
}

login_admin() {
  local cookies="$tmpdir/admin-cookies.txt"
  local login_page="$tmpdir/admin-login.html"
  local token
  local code

  curl -sS -c "$cookies" "${BASE_WEB_URL}/login" -o "$login_page"
  token="$(extract_csrf "$login_page")"
  [[ -n "$token" ]] || fail "admin login CSRF token missing"

  code="$(curl -sS -b "$cookies" -c "$cookies" -o "$tmpdir/admin-post-login.html" -w '%{http_code}' \
    -X POST "${BASE_WEB_URL}/login" \
    -H 'Content-Type: application/x-www-form-urlencoded' \
    --data-urlencode "_token=${token}" \
    --data-urlencode "email=${ADMIN_EMAIL}" \
    --data-urlencode "password=${PASSWORD}")"

  [[ "$code" == "302" ]] || fail "admin login returned HTTP ${code}; expected 302"
  echo "$cookies"
}

fetch_ai_console() {
  local cookies="$1"
  local html="$2"
  local text="$3"
  local code

  code="$(curl -sS -b "$cookies" -L -o "$html" -w '%{http_code}' "${BASE_WEB_URL}/sanad/ai")"
  [[ "$code" == "200" ]] || fail "AI console returned HTTP ${code}"
  assert_clean_page "$html" "AI console"
  html_to_text < "$html" > "$text"
}

post_form() {
  local cookies="$1"
  local url="$2"
  local label="$3"
  shift 3
  local code

  code="$(curl -sS -b "$cookies" -c "$cookies" -o "$tmpdir/${label}.html" -w '%{http_code}' \
    -X POST "$url" \
    -H 'Content-Type: application/x-www-form-urlencoded' \
    "$@")"

  [[ "$code" == "302" ]] || fail "${label} returned HTTP ${code}; expected 302"
}

cookies="$(login_admin)"
html="$tmpdir/ai.html"
text="$tmpdir/ai.txt"

fetch_ai_console "$cookies" "$html" "$text"
csrf="$(extract_csrf "$html")"
[[ -n "$csrf" ]] || fail "AI console CSRF token missing"

stamp="$(date +%Y%m%d%H%M%S)"
keyword="sanadqa${stamp}"
knowledge_title="Quick AI Web QA ${keyword}"
knowledge_content="Quick AI web action answer ${keyword}: customers must prepare required documents before request approval."

post_form "$cookies" "${BASE_WEB_URL}/sanad/ai/knowledge" "ai-knowledge-create" \
  --data-urlencode "_token=${csrf}" \
  --data-urlencode "title=${knowledge_title}" \
  --data-urlencode "category=QA" \
  --data-urlencode "content=${knowledge_content}" \
  --data-urlencode "title_en=${knowledge_title}" \
  --data-urlencode "title_ar=اختبار معرفة كويك ${keyword}" \
  --data-urlencode "content_en=${knowledge_content}" \
  --data-urlencode "content_ar=يجب على العملاء تجهيز المستندات المطلوبة قبل الموافقة على الطلب ${keyword}." \
  --data-urlencode "visible_to[]=admin" \
  --data-urlencode "visible_to[]=user"

fetch_ai_console "$cookies" "$html" "$text"
assert_contains "$text" "$knowledge_title" "AI knowledge list"
assert_contains "$text" "$knowledge_content" "AI knowledge content"
pass "AI knowledge creation action persists and renders"

csrf="$(extract_csrf "$html")"
post_form "$cookies" "${BASE_WEB_URL}/sanad/ai/ask" "ai-ask-known" \
  --data-urlencode "_token=${csrf}" \
  --data-urlencode "question=What should customers do for ${keyword}?"

fetch_ai_console "$cookies" "$html" "$text"
assert_contains "$text" "What should customers do for ${keyword}?" "AI known-question interaction"
assert_contains "$text" "$knowledge_content" "AI known answer"
assert_contains "$text" "Answered" "AI known answer status"
pass "AI ask action returns knowledge-base answer in rendered history"

csrf="$(extract_csrf "$html")"
unknown_question="Unmatched escalation probe zyxwvutsrq plmoknijb qatest${stamp}"
post_form "$cookies" "${BASE_WEB_URL}/sanad/ai/ask" "ai-ask-unknown" \
  --data-urlencode "_token=${csrf}" \
  --data-urlencode "question=${unknown_question}"

fetch_ai_console "$cookies" "$html" "$text"
assert_contains "$text" "$unknown_question" "AI escalation question"
assert_contains "$text" "Quick team" "AI escalation answer"
assert_contains "$text" "Escalated" "AI escalation status"
pass "AI low-confidence action escalates and renders human-review guidance"

echo "Quick AI web action QA completed successfully against ${BASE_WEB_URL}."
