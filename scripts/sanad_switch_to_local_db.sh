#!/usr/bin/env bash
set -euo pipefail

cd "$(dirname "$0")/.."

env_file="${ENV_FILE:-.env}"
if [[ ! -f "$env_file" ]]; then
  echo "$env_file not found" >&2
  exit 1
fi

backup_file="${env_file}.before-local-db.$(date +%Y%m%d_%H%M%S)"
cp "$env_file" "$backup_file"

python3 - "$env_file" <<'PY'
from pathlib import Path
import sys

path = Path(sys.argv[1])
lines = path.read_text().splitlines()

def parse(lines):
    values = {}
    for line in lines:
        if not line or line.lstrip().startswith("#") or "=" not in line:
            continue
        key, value = line.split("=", 1)
        values[key] = value
    return values

values = parse(lines)
live_defaults = {
    "LIVE_DB_HOST": values.get("DB_HOST", ""),
    "LIVE_DB_PORT": values.get("DB_PORT", "3306"),
    "LIVE_DB_DATABASE": values.get("DB_DATABASE", ""),
    "LIVE_DB_USERNAME": values.get("DB_USERNAME", ""),
    "LIVE_DB_PASSWORD": values.get("DB_PASSWORD", ""),
}

updates = {
    **{key: values.get(key, fallback) or fallback for key, fallback in live_defaults.items()},
    "DB_CONNECTION": "mysql",
    "DB_HOST": "127.0.0.1",
    "DB_PORT": "3306",
    "DB_DATABASE": values.get("LOCAL_DB_DATABASE", values.get("DB_DATABASE", "kangoo")),
    "DB_USERNAME": values.get("LOCAL_DB_USERNAME", "root"),
    "DB_PASSWORD": values.get("LOCAL_DB_PASSWORD", values.get("MYSQL_ROOT_PASSWORD", "root")),
    "DB_PERSISTENT": "true",
    "CACHE_DRIVER": values.get("CACHE_DRIVER", "file"),
    "SESSION_DRIVER": values.get("SESSION_DRIVER", "file"),
}

seen = set()
out = []
inserted_live_header = False

for line in lines:
    if not line or line.lstrip().startswith("#") or "=" not in line:
        out.append(line)
        continue
    key = line.split("=", 1)[0]
    if key in updates:
        if key.startswith("LIVE_DB_") and not inserted_live_header:
            if out and out[-1] != "":
                out.append("")
            out.append("# Live database connection used by sync/migration scripts")
            inserted_live_header = True
        out.append(f"{key}={updates[key]}")
        seen.add(key)
    else:
        out.append(line)

missing_live = [key for key in live_defaults if key not in seen]
missing_db = [key for key in ("DB_CONNECTION", "DB_HOST", "DB_PORT", "DB_DATABASE", "DB_USERNAME", "DB_PASSWORD", "DB_PERSISTENT") if key not in seen]

if missing_live:
    out.append("")
    out.append("# Live database connection used by sync/migration scripts")
    for key in missing_live:
        out.append(f"{key}={updates[key]}")

if missing_db:
    out.append("")
    for key in missing_db:
        out.append(f"{key}={updates[key]}")

path.write_text("\n".join(out) + "\n")
PY

echo "Updated $env_file for local MySQL. Previous file saved as $backup_file"
