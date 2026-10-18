#!/usr/bin/env bash
# Build `.env.e2e.local` from `.env.e2e.dist` for an isolated Playwright smoke stack.
# Overlay shared infra / secrets from `.env.local` (never copy isolation keys from dogfood).
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT"

DIST="${E2E_ENV_DIST:-.env.e2e.dist}"
OUT="${E2E_ENV_FILE:-.env.e2e.local}"

if [[ ! -f "$DIST" ]]; then
  echo "Missing ${DIST} — this repository must version the E2E env template." >&2
  exit 1
fi

E2E_COMPOSE_PROJECT="${E2E_COMPOSE_PROJECT:-nowo-shell-e2e}"
E2E_HTTP_PORT="${E2E_HTTP_PORT:-9087}"
E2E_HTTPS_PORT="${E2E_HTTPS_PORT:-9461}"
E2E_MYSQL_DATABASE="${E2E_MYSQL_DATABASE:-nowo_shell_e2e}"
E2E_REDIS_DB="${E2E_REDIS_DB:-3}"
E2E_VITE_PORT="${E2E_VITE_PORT:-5179}"
E2E_SCHEME="${E2E_SCHEME:-https}"
E2E_FRANKENPHP_MODE="${E2E_FRANKENPHP_MODE:-worker}"
E2E_FRANKENPHP_WORKER_NUM="${E2E_FRANKENPHP_WORKER_NUM:-4}"
E2E_FRANKENPHP_RESET_KERNEL="${E2E_FRANKENPHP_RESET_KERNEL:-false}"

cp "$DIST" "$OUT"

upsert() {
  local key="$1"
  local value="$2"
  local file="$3"
  local escaped
  escaped="$(printf '%s' "$value" | sed -e 's/[&\\]/\\&/g')"
  if grep -qE "^${key}=" "$file"; then
    sed -i -E "s|^${key}=.*$|${key}=${escaped}|" "$file"
  else
    printf '\n%s=%s\n' "$key" "$value" >>"$file"
  fi
}

is_isolation_key() {
  case "$1" in
    COMPOSE_PROJECT_NAME|HTTP_PORT|HTTPS_PORT|HTTP3_PORT|DEFAULT_URI|MYSQL_DATABASE|VITE_PORT|REDIS_URL|MESSENGER_TRANSPORT_DSN|BEACON_DSN|DATABASE_URL|DATABASE_URL_RO)
      return 0
      ;;
  esac
  return 1
}

if [[ -f .env.local ]]; then
  while IFS= read -r line || [[ -n "$line" ]]; do
    [[ "$line" =~ ^[[:space:]]*# ]] && continue
    [[ "$line" =~ ^[[:space:]]*$ ]] && continue
    [[ "$line" != *=* ]] && continue
    key="${line%%=*}"
    value="${line#*=}"
    is_isolation_key "$key" && continue
    upsert "$key" "$value" "$OUT"
  done < .env.local
else
  echo "Note: no .env.local — ${OUT} uses ${DIST} placeholders. Run make ensure-env if shared infra secrets differ." >&2
fi

upsert COMPOSE_PROJECT_NAME "$E2E_COMPOSE_PROJECT" "$OUT"
upsert HTTP_PORT "$E2E_HTTP_PORT" "$OUT"
if grep -qE '^HTTPS_PORT=' "$OUT"; then
  upsert HTTPS_PORT "$E2E_HTTPS_PORT" "$OUT"
fi
if grep -qE '^HTTP3_PORT=' "$OUT"; then
  upsert HTTP3_PORT "$E2E_HTTPS_PORT" "$OUT"
fi
if [[ "$E2E_SCHEME" == "http" ]]; then
  upsert DEFAULT_URI "http://localhost:${E2E_HTTP_PORT}" "$OUT"
else
  upsert DEFAULT_URI "https://localhost:${E2E_HTTPS_PORT}" "$OUT"
fi
upsert MYSQL_DATABASE "$E2E_MYSQL_DATABASE" "$OUT"
if grep -qE '^VITE_PORT=' "$OUT"; then
  upsert VITE_PORT "$E2E_VITE_PORT" "$OUT"
fi
if grep -qE '^FRANKENPHP_MODE=' "$OUT"; then
  upsert FRANKENPHP_MODE "$E2E_FRANKENPHP_MODE" "$OUT"
fi
if grep -qE '^FRANKENPHP_WORKER=' "$OUT"; then
  if [[ "$E2E_FRANKENPHP_MODE" == "worker" ]]; then
    upsert FRANKENPHP_WORKER "1" "$OUT"
  else
    upsert FRANKENPHP_WORKER "0" "$OUT"
  fi
fi
if grep -qE '^FRANKENPHP_WORKER_NUM=' "$OUT"; then
  upsert FRANKENPHP_WORKER_NUM "$E2E_FRANKENPHP_WORKER_NUM" "$OUT"
fi
if grep -qE '^FRANKENPHP_RESET_KERNEL=' "$OUT"; then
  upsert FRANKENPHP_RESET_KERNEL "$E2E_FRANKENPHP_RESET_KERNEL" "$OUT"
fi

env_file_value() {
  local key="$1"
  local file="$2"
  local line
  line="$(grep -E "^${key}=" "$file" | head -1 || true)"
  [[ -z "$line" ]] && return 0
  printf '%s' "${line#*=}"
}

REDIS_HOST_VALUE="$(env_file_value REDIS_HOST "$OUT")"
REDIS_PORT_VALUE="$(env_file_value REDIS_PORT "$OUT")"
REDIS_HOST_VALUE="${REDIS_HOST_VALUE:-redis-8.10.2}"
REDIS_PORT_VALUE="${REDIS_PORT_VALUE:-6379}"
# Prefer shared Redis hostname when dogfood still points at compose service `redis`.
if [[ "$REDIS_HOST_VALUE" == "redis" ]]; then
  REDIS_HOST_VALUE="redis-8.10.2"
fi
upsert REDIS_URL "redis://${REDIS_HOST_VALUE}:${REDIS_PORT_VALUE}/${E2E_REDIS_DB}" "$OUT"
upsert MESSENGER_TRANSPORT_DSN "redis://${REDIS_HOST_VALUE}:${REDIS_PORT_VALUE}?dbindex=${E2E_REDIS_DB}" "$OUT"
if grep -qE '^BEACON_DSN=' "$OUT"; then
  upsert BEACON_DSN "" "$OUT"
fi

# Prefer shared MySQL for the smoke schema when dogfood still uses Compose service `database`.
MYSQL_HOST_VALUE="$(env_file_value MYSQL_HOST "$OUT")"
if [[ "$MYSQL_HOST_VALUE" == "database" ]] || [[ -z "$MYSQL_HOST_VALUE" ]]; then
  upsert MYSQL_HOST "mysql-9.7.2-primary" "$OUT"
  upsert MYSQL_HOST_RO "mysql-9.7.2-primary" "$OUT"
fi

rewrite_db_url() {
  local key="$1"
  local file="$2"
  local line
  line="$(grep -E "^${key}=" "$file" | head -1 || true)"
  [[ -z "$line" ]] && return 0
  local val="${line#*=}"
  val="${val%\"}"
  val="${val#\"}"
  if [[ "$val" == *'${MYSQL_DATABASE}'* ]]; then
    return 0
  fi
  local rewritten
  rewritten="$(printf '%s' "$val" | sed -E "s|(mysql://[^/]+/)[^?]+|\\1${E2E_MYSQL_DATABASE}|")"
  upsert "$key" "\"${rewritten}\"" "$OUT"
}

rewrite_db_url DATABASE_URL "$OUT"
rewrite_db_url DATABASE_URL_RO "$OUT"

chmod 600 "$OUT" 2>/dev/null || true
echo "Wrote ${OUT} from ${DIST} (project=${E2E_COMPOSE_PROJECT}, DB=${E2E_MYSQL_DATABASE}, port=${E2E_HTTPS_PORT:-$E2E_HTTP_PORT}, Redis DB=${E2E_REDIS_DB})"
