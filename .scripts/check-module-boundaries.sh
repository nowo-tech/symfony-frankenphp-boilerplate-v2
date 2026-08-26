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

# --- Migrations must not reference product tokens ---
# Precise product tokens: ingest_ column names are product-only (beacon ingest pipeline).
# The initial schema migration predates the boilerplate split and is excluded from this
# gate; guard against NEW product additions via column-name prefixes.
if [ -d migrations ]; then
  mig_hits=$(rg -l \
    "seed.sample|beacon.bundle|'ingest_rate_limit|'ingest_enabled" \
    migrations 2>/dev/null || true)
  if [ -n "$mig_hits" ]; then
    echo "error: migrations must not add product columns (ingest_, seed-sample):" >&2
    echo "$mig_hits" >&2
    bad=1
  fi
fi

# --- e2e must not reference product tokens ---
for e2e_dir in e2e tests/e2e; do
  if [ -d "$e2e_dir" ]; then
    e2e_hits=$(rg -l \
      'seed.sample|/projects|mercure.*product|ingest|beacon.bundle' \
      "$e2e_dir" 2>/dev/null || true)
    if [ -n "$e2e_hits" ]; then
      echo "error: $e2e_dir must not reference product tokens:" >&2
      echo "$e2e_hits" >&2
      bad=1
    fi
  fi
done

# --- translations must not reference clearly product-only tokens ---
# Use narrow tokens (beacon-bundle, seed-sample) to avoid false positives from
# platform-adjacent terms. Translation files are cleaned of product sections but
# may still contain overlap; add tokens here when product strings are verified absent.
if [ -d translations ]; then
  trans_hits=$(rg -l \
    'beacon.bundle|seed.sample' \
    translations 2>/dev/null || true)
  if [ -n "$trans_hits" ]; then
    echo "error: translations must not reference product tokens (beacon-bundle, seed-sample):" >&2
    echo "$trans_hits" >&2
    bad=1
  fi
fi

# --- composer.json must not require product-only dependencies ---
if [ -f composer.json ]; then
  if rg -q '"nowo-tech/beacon-bundle"' composer.json 2>/dev/null; then
    echo "error: composer.json must not require nowo-tech/beacon-bundle (product dep)." >&2
    bad=1
  fi
  # Mercure as a required server-side dep is product scope; allow in dev/test only
  if rg -q '"symfony/mercure-bundle"' composer.json 2>/dev/null; then
    # Only fail if it's in the main require block, not require-dev
    if rg -A5 '"require"' composer.json 2>/dev/null | grep -q '"symfony/mercure-bundle"'; then
      echo "error: composer.json must not require symfony/mercure-bundle in require (product dep; dev-only OK)." >&2
      bad=1
    fi
  fi
fi

if [ "$bad" -ne 0 ]; then
  exit 1
fi

echo "OK: module boundaries (platform-only Identity/Shared/Setup)."
