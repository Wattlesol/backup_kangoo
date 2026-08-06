#!/usr/bin/env bash
set -euo pipefail

export PATH="/usr/local/bin:/opt/homebrew/bin:/usr/bin:/bin:/usr/sbin:/sbin:$PATH"

DOKPLOY_URL="${DOKPLOY_URL:-https://deploy.wattlesol.com}"
DOKPLOY_COMPOSE_ID="${DOKPLOY_COMPOSE_ID:-dt3y93oBMayMC8VBFjXVJ}"
EXPECTED_COMMIT="${SANAD_EXPECTED_COMMIT:-$(git rev-parse HEAD)}"
LIVE_BASE_URL="${SANAD_LIVE_BASE_URL:-https://kangoo.sa/api}"
PROD_BRANCH="${SANAD_DEPLOY_BRANCH:-origin/prod}"

if [[ -z "${DOKPLOY_API_KEY:-}" ]]; then
  echo "FAIL DOKPLOY_API_KEY is required." >&2
  exit 1
fi

if ! command -v jq >/dev/null 2>&1; then
  echo "FAIL jq is required." >&2
  exit 1
fi

echo "Fetching latest branch state..."
git fetch origin prod >/dev/null

if ! git merge-base --is-ancestor "$EXPECTED_COMMIT" "$PROD_BRANCH"; then
  echo "FAIL ${PROD_BRANCH} does not contain expected Sanad commit ${EXPECTED_COMMIT}." >&2
  echo "Review/merge the prod alignment PR before deploying." >&2
  exit 1
fi

echo "Verified ${PROD_BRANCH} contains ${EXPECTED_COMMIT}."

tmpdir="$(mktemp -d)"
trap 'rm -rf "$tmpdir"' EXIT

compose_response="$tmpdir/compose.json"
compose_code="$(curl -sS -o "$compose_response" -w '%{http_code}' \
  -H "x-api-key: ${DOKPLOY_API_KEY}" \
  -H 'Accept: application/json' \
  "${DOKPLOY_URL}/api/compose.one?composeId=${DOKPLOY_COMPOSE_ID}")"

if [[ "$compose_code" != "200" ]]; then
  echo "FAIL unable to read Dokploy compose ${DOKPLOY_COMPOSE_ID}; HTTP ${compose_code}" >&2
  cat "$compose_response" >&2
  exit 1
fi

compose_branch="$(jq -r '.branch // empty' "$compose_response")"
compose_repo="$(jq -r '.repository // empty' "$compose_response")"
compose_owner="$(jq -r '.owner // empty' "$compose_response")"
compose_name="$(jq -r '.name // empty' "$compose_response")"

if [[ "$compose_branch" != "prod" || "$compose_owner" != "Wattlesol" || "$compose_repo" != "backup_kangoo" ]]; then
  echo "FAIL unexpected Dokploy compose target: ${compose_owner}/${compose_repo}:${compose_branch}" >&2
  exit 1
fi

echo "Deploying Dokploy compose ${compose_name} (${DOKPLOY_COMPOSE_ID}) from ${compose_owner}/${compose_repo}:${compose_branch}..."

deploy_payload="$(jq -cn \
  --arg composeId "$DOKPLOY_COMPOSE_ID" \
  --arg title "Deploy Sanad UAT ${EXPECTED_COMMIT:0:8}" \
  --arg description "Sanad UAT deployment after local SQL and web QA. Commit: ${EXPECTED_COMMIT}" \
  '{composeId: $composeId, title: $title, description: $description}')"

deploy_response="$tmpdir/deploy.json"
deploy_code="$(curl -sS -o "$deploy_response" -w '%{http_code}' \
  -X POST \
  -H "x-api-key: ${DOKPLOY_API_KEY}" \
  -H 'Accept: application/json' \
  -H 'Content-Type: application/json' \
  --data "$deploy_payload" \
  "${DOKPLOY_URL}/api/compose.redeploy")"

if [[ "$deploy_code" -lt 200 || "$deploy_code" -ge 300 ]]; then
  echo "FAIL Dokploy redeploy returned HTTP ${deploy_code}" >&2
  cat "$deploy_response" >&2
  exit 1
fi

echo "Dokploy redeploy accepted. Waiting for deployment result..."

for attempt in $(seq 1 120); do
  curl -sS -o "$compose_response" \
    -H "x-api-key: ${DOKPLOY_API_KEY}" \
    -H 'Accept: application/json' \
    "${DOKPLOY_URL}/api/compose.one?composeId=${DOKPLOY_COMPOSE_ID}"

  latest_status="$(jq -r '.deployments[0].status // empty' "$compose_response")"
  latest_description="$(jq -r '.deployments[0].description // empty' "$compose_response")"

  if [[ "$latest_description" == *"$EXPECTED_COMMIT"* ]]; then
    if [[ "$latest_status" == "done" ]]; then
      echo "Dokploy deployment completed successfully."
      break
    fi

    if [[ "$latest_status" == "error" || "$latest_status" == "failed" ]]; then
      echo "FAIL Dokploy deployment finished with status ${latest_status}." >&2
      exit 1
    fi
  fi

  if [[ "$attempt" -eq 120 ]]; then
    echo "FAIL Dokploy deployment did not finish within timeout." >&2
    exit 1
  fi

  sleep 10
done

echo "Running post-deploy integrated QA against ${LIVE_BASE_URL}..."
BASE_URL="${LIVE_BASE_URL}" \
SANAD_REQUIRE_REQUEST="${SANAD_REQUIRE_REQUEST:-false}" \
scripts/sanad_integrated_qa.sh

echo "Sanad Dokploy deploy and post-deploy QA completed successfully."
