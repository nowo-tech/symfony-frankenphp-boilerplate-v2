#!/usr/bin/env bash
# Refuse mixed standalone/shared MySQL + Redis hostnames (REQ-INFRA-SHARED / REQ-REV SCALE-001).
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

if [[ ! -f .env.local ]]; then
  echo "assert-shared-hosts: .env.local missing — run: cp .env.dist .env.local (or make ensure-env)." >&2
  exit 1
fi

set -a
# shellcheck disable=SC1091
source ./.env.local
set +a

MYSQL_HOST="${MYSQL_HOST:-database}"
REDIS_HOST="${REDIS_HOST:-redis}"
MODE="${1:-check}" # check | require-shared | require-standalone

shared_mysql='mysql-9.7.2-primary'
shared_redis='redis-8.10.2'
standalone_mysql='database'
standalone_redis='redis'

is_shared_mysql=0
is_standalone_mysql=0
[[ "$MYSQL_HOST" == "$shared_mysql" ]] && is_shared_mysql=1
[[ "$MYSQL_HOST" == "$standalone_mysql" ]] && is_standalone_mysql=1

is_shared_redis=0
is_standalone_redis=0
[[ "$REDIS_HOST" == "$shared_redis" ]] && is_shared_redis=1
[[ "$REDIS_HOST" == "$standalone_redis" ]] && is_standalone_redis=1

fail() {
  echo "assert-shared-hosts: $*" >&2
  echo "  Standalone: MYSQL_HOST=database REDIS_HOST=redis" >&2
  echo "  Shared:     MYSQL_HOST=mysql-9.7.2-primary REDIS_HOST=redis-8.10.2" >&2
  echo "  See docs/ops/SHARED-SERVER.md" >&2
  exit 1
}

if (( is_shared_mysql && ! is_shared_redis )); then
  fail "MYSQL_HOST is shared ($MYSQL_HOST) but REDIS_HOST is not ($REDIS_HOST)."
fi
if (( is_shared_redis && ! is_shared_mysql )); then
  fail "REDIS_HOST is shared ($REDIS_HOST) but MYSQL_HOST is not ($MYSQL_HOST)."
fi
if (( is_standalone_mysql && ! is_standalone_redis )); then
  fail "MYSQL_HOST is standalone ($MYSQL_HOST) but REDIS_HOST is not ($REDIS_HOST)."
fi
if (( is_standalone_redis && ! is_standalone_mysql )); then
  fail "REDIS_HOST is standalone ($REDIS_HOST) but MYSQL_HOST is not ($MYSQL_HOST)."
fi

case "$MODE" in
  require-shared)
    (( is_shared_mysql && is_shared_redis )) || fail "make up-shared requires MYSQL_HOST=$shared_mysql and REDIS_HOST=$shared_redis (got MYSQL_HOST=$MYSQL_HOST REDIS_HOST=$REDIS_HOST)."
    ;;
  require-standalone)
    (( is_standalone_mysql && is_standalone_redis )) || fail "make up (standalone) expects MYSQL_HOST=$standalone_mysql and REDIS_HOST=$standalone_redis (got MYSQL_HOST=$MYSQL_HOST REDIS_HOST=$REDIS_HOST). Use make up-shared for shared hosts."
    ;;
  check) ;;
  *)
    echo "Usage: $0 [check|require-shared|require-standalone]" >&2
    exit 2
    ;;
esac

echo "assert-shared-hosts: OK (MYSQL_HOST=$MYSQL_HOST REDIS_HOST=$REDIS_HOST mode=$MODE)"
