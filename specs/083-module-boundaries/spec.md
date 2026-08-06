# Feature Specification: Module boundary hardening (platform)

**Feature Branch**: `083-module-boundaries`  
**Created**: 2026-08-06  
**Status**: Implemented (platform subset)  

**Input**: Keep the deliberate modular Symfony shape for the **platform boilerplate**; document and guard `Identity` / `Shared` / `Setup` boundaries. Beacon product modules (Project, Ingest, Issues, …) are **not** part of this repository.

## Summary

Contributors need a **stable module map** that matches the codebase. Success is: docs ↔ code alignment, dependency direction, bounded Shared growth, and CI boundary checks that fail when product domains reappear.

## Scope (as-built)

| Area | Delivered |
|------|-----------|
| Module map | Constitution **2.0.0** + `docs/ARCHITECTURE.md` list `Identity` / `Shared` / `Setup` |
| Shared rules | Cross-cutting presentation / instance config only — not domain write ownership |
| Setup | SiteBackup gate + `Setup\Demo\*` + `Setup\Command\SeedPlatform` / `SeedDemo` |
| CI guardrail | `.scripts/check-module-boundaries.sh` (+ `make check-module-boundaries` / CI git-hygiene) |

## User Scenarios & Testing

### User Story 1 - Module map matches the platform (Priority: P1)

As a maintainer, I see every top-level package under `src/` with a clear responsibility.

**Acceptance Scenarios**:

1. **Given** `ls src/`, **When** I open `docs/ARCHITECTURE.md`, **Then** only Identity, Shared, and Setup are documented as platform domains.
2. **Given** a PR that adds `src/Ingest` (or similar product domain) to this boilerplate, **When** review/CI runs boundary checks, **Then** the change is rejected unless the constitution is amended and the mission changes.
3. **Given** Spec Kit directories, **When** feature numbers are listed, **Then** each numeric prefix is unique.

### User Story 2 - Shared stays thin (Priority: P1)

As a contributor, new domain enums/seeders belong in the owning module (or a new product module in a clone), not Shared.

**Acceptance Scenarios**:

1. **Given** appearance/mailer/legal/locale/menu helpers, **When** placed under Shared, **Then** they remain cross-cutting instance chrome.
2. **Given** demo JSON fixtures, **When** loaded, **Then** loaders live under `Setup`, not Shared.

## Requirements

- **FR-001**: Platform domains are exactly Identity, Shared, Setup.
- **FR-002**: CI script fails on forbidden product path prefixes when configured for this repo.
- **FR-003**: Constitution Principle II/III/VI stay aligned with ARCHITECTURE.md.

## Success Criteria

- `make check-module-boundaries` passes on a clean platform tree.
- Docs do not list Beacon product modules as present in this repo.

## Related

- `085-architecture-convergence`, `001-bootstrap`, REQ-BP-001
