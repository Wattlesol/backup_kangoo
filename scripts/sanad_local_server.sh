#!/usr/bin/env bash
set -euo pipefail

cd "$(dirname "$0")/.."

host="${SANAD_LOCAL_HOST:-localhost}"
port="${SANAD_LOCAL_PORT:-8000}"

exec php \
  -d error_reporting=6143 \
  -d display_errors=0 \
  -d log_errors=1 \
  -S "$host:$port" -t public server.php
