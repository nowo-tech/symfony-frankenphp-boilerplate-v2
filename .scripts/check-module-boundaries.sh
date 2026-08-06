#!/usr/bin/env sh
# Fail on module-boundary regressions for the platform boilerplate (Nowo Shell).
set -eu

ROOT="$(CDPATH= cd -- "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

bad=0

# --- Product domains must not reappear in the boilerplate ---
for domain in Ingest Issues Project Analytics Performance Notifications Ops Api; do
  if [ -d "src/$domain" ]; then
    echo "error: product domain src/$domain must not exist in the platform boilerplate." >&2
    bad=1
  fi
done

for shared in Mercure Metrics; do
  if [ -d "src/Shared/$shared" ]; then
    echo "error: product Shared/$shared must not exist in the platform boilerplate." >&2
    bad=1
  fi
done

# --- Platform domains that must remain ---
for path in \
  src/Identity \
  src/Shared/Appearance \
  src/Shared/Settings \
  src/Shared/Legal \
  src/Setup
do
  if [ ! -d "$path" ]; then
    echo "error: missing platform path $path" >&2
    bad=1
  fi
done

# --- Shared must not import deleted product namespaces ---
if [ -d src/Shared ]; then
  write_hits=$(rg -l --glob '*.php' \
    'use App\\(Ingest|Issues|Project|Notifications|Analytics|Performance|Ops)\\' \
    src/Shared 2>/dev/null || true)
  if [ -n "$write_hits" ]; then
    echo "error: Shared must not import product namespaces:" >&2
    echo "$write_hits" >&2
    bad=1
  fi
fi

# --- Identity must not import product namespaces ---
if [ -d src/Identity ]; then
  id_hits=$(rg -l --glob '*.php' \
    'use App\\(Ingest|Issues|Project|Notifications|Analytics|Performance|Ops)\\' \
    src/Identity 2>/dev/null || true)
  if [ -n "$id_hits" ]; then
    echo "error: Identity must not import product namespaces:" >&2
    echo "$id_hits" >&2
    bad=1
  fi
fi

if [ "$bad" -ne 0 ]; then
  exit 1
fi

echo "OK: module boundaries (platform-only Identity/Shared/Setup)."
