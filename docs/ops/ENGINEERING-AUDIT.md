# Engineering audit (REQ-REV)

**App:** Nowo Shell — `symfony-frankenphp-boilerplate-v2`  
**Pass date:** 2026-08-15 (initial) · **Remediation High:** 2026-08-15 · **Close Medium / platform polish:** 2026-08-15 · **Re-audit (pass 21):** 2026-08-15 — revalidated; Critical/High still empty; Low backlog unchanged  
**Scope:** REQ-REV-002…006 + DOCS-APP-002 + QA-003  
**Verdict:** **✅ Pass** — Critical/High empty; Medium closed for CSRF-004, cookie inventory, access_control, architecture doc, Beacon i18n purge; remaining items are explicit Low/deferred non-goals

This document is the REV-007 evidence artifact. Re-run at least once per minor release train.

---

## Summary by dimension

| Dimension | REQ | Status | Notes |
| --------- | --- | ------ | ----- |
| Security | REV-002 | ✅ | APP_SECRET fail-closed; CSRF Choice forms; cookie copy 1d/30d; deep `access_control` |
| N+1 / queries | REV-003 | ✅ | Users list eager `instanceRoles`; roles over-fetch / query budgets = Low backlog |
| Refactor | REV-004 | ✅ | Beacon top-level i18n purged (~8.9k LOC); QA/Rector green |
| Scalability | REV-005 | ✅ | `up-shared` + host-pair assert + `compose.shared` |
| Architecture | REV-006 | ✅ | Platform action catalog; expanded `ARCHITECTURE.md` |
| Evidence / cadence | REV-007 | ✅ | This file |

**Overall REV-001:** ✅

---

## Closed this train

| ID | Title | Resolution |
| -- | ----- | ---------- |
| SEC-001 | APP_SECRET fail-closed | Guard + tests + PRODUCTION/SECURITY |
| N1-001 | Users list N+1 | Eager `instanceRoles` |
| ARCH-001 | Beacon action/i18n residue | `UserActionType` purge + messages top-level KEEP set |
| SCALE-001 | Shared dual-mode | `make up-shared` + `assert-shared-hosts.sh` |
| SEC-002/003 | CSRF-004 role/permissions/add-user | FormKit Choice/checkbox/email Types |
| SEC-004 | Cookie inventory 7d vs 30d | YAML + fixture + LEGAL + i18n → 1 day / 30 days |
| SEC-005 | `access_control` admin depth | `^/admin`, cookie-consent-config, breadcrumb-kit-admin → `ROLE_ADMIN` |
| ARCH-002 | Thin ARCHITECTURE.md | Expanded module/RBAC/health/runtime map |
| DOCS-APP-002 | E2E catalog | `docs/product/E2E-USE-CASES.md` |
| QA-003 | Flat e2e | `e2e/{setup,support,smoke,account,admin}/` |

---

## Deferred Low / non-goals (dated backlog)

| ID | Title | Notes |
| -- | ----- | ----- |
| N1-002 | Roles list cartesian hydrate | **Closed (pass 35):** `InstanceRoleListRow` + batch COUNT in `InstanceRoleRepository::findAllOrderedForIndex()` |
| N1-003 | Profiler query budgets in CI | Manual/spec later |
| REF-001 | Coverage islands (controllers excluded) | Documented in COVERAGE.md; e2e covers shell |
| ARCH-003 | Shared→Identity without Ports | Constitution gradual boundaries |
| SEC-006 | SiteBackup panel hand-rolled CSRF | Kit-owned Twig; document exception |
| SCALE-002 | Redis in `/health/ready` | Optional hardening |

---

## How to re-verify

```bash
docker compose exec php php vendor/bin/phpstan analyse src/Identity --memory-limit=512M
docker compose exec php php vendor/bin/phpunit tests/Unit/Setup/SiteBackupSecurityDefaultsGuardTest.php
./.scripts/assert-shared-hosts.sh check
find e2e -maxdepth 1 -name '*.spec.ts'   # must be empty
test -f docs/product/E2E-USE-CASES.md
```
