# Feature Specification: Architecture convergence (platform)

**Feature Branch**: `085-architecture-convergence`  
**Created**: 2026-08-06  
**Status**: Implemented (platform subset)  

**Input**: Close residual structural debt for the platform starter after extracting product domains from the Beacon lineage: honest module ownership, Setup fixtures, Identity UI preferences embeddable, CI boundaries.

## Summary

This boilerplate keeps a **thin platform architecture**: Identity (users/account), Shared (chrome/settings), Setup (first-run + catalog seed). Product apps clone and add domains; this repo must not re-absorb ingest/issues/ops product surfaces.

## Scope (as-built)

| Area | Delivered |
|------|-----------|
| Domains | `Identity`, `Shared`, `Setup` only |
| Identity | `UserUiPreferences` embeddable + account AJAX prefs; GDPR anonymize/export without product project ownership |
| Setup | Demo seeders load `src/Setup/Demo/fixtures/*.json` via `DemoFixtureLoader` |
| Settings | Mailer + instance config export/import (platform keys only) |
| Appearance | `--app-*` tokens + theme presets (`shell`, …) |
| CI | Module boundary script + git hygiene |
| Docs | Constitution **2.0.0**, `ARCHITECTURE.md`, README platform mission |

## Non-goals

- Full DDD / hexagonal / SPA
- Product Messenger splits (`async_ingest` / notify consumers)
- Ops overview / Prometheus / OTLP / Mercure in this repository
- Reintroducing Project/Ingest/Issues modules

## Success criteria

1. `make check-module-boundaries` passes.
2. `make bootstrap` + `make seed` yield a navigable admin shell.
3. Instance config export/import round-trips platform settings without product secrets.
4. Appearance and AuthKit mail gates work without product ops fields on `InstanceSettings`.

## Related

- `083-module-boundaries`, `055-install-seed-layers`, `082-appearance-theme-presets`, `044-instance-config-export`
- Workspace REQs: REQ-BP, REQ-IDENTITY, REQ-APPEAR, REQ-INSTANCE, REQ-HTTPLOG
