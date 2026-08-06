# Feature Specification: Install & Seed Layers

**Feature Branch**: `055-install-seed-layers`  
**Created**: 2026-07-21  
**Status**: Implemented (platform subset)  

**Input**: Split first-install / upgrade seeding into clear layers: (1) idempotent platform seed (menus/breadcrumbs/cookie consent); (2) demo seed for identity admin only. Visual cold-start UI is **out of scope** here — see `056-setup-wizard` (SiteBackupBundle `/setup`).

**Out of scope for this boilerplate**: product `app:seed-sample`, demo projects, API keys, client DSN files.

## User Scenarios & Testing

### User Story 1 - Platform seed on fresh install and upgrade (Priority: P1)

As an operator installing or upgrading Nowo Shell, I run schema migrations and then a **platform seed** that upserts navigation (menus, breadcrumbs) and cookie-consent catalog data so the UI works without inventing demo users.

**Independent Test**: Empty DB → migrate → platform seed → login via first-user register → Administration sidebar and breadcrumbs are present; no demo user required.

**Acceptance Scenarios**:

1. **Given** a migrated empty database, **When** I run `app:seed-platform` (or `make seed-platform`), **Then** required dashboard menus and breadcrumbs exist (or are updated) for current platform routes.
2. **Given** an existing instance whose menus are missing a newly shipped admin item, **When** I run platform seed again, **Then** the missing item appears and existing customizations that share the same stable keys are updated for position/label/permission without duplicating rows.
3. **Given** a production database with real users, **When** I run platform seed, **Then** no demo admin user is created.

---

### User Story 2 - Demo seed for local identity only (Priority: P1)

As a developer bootstrapping a local stack, I run **demo seed** to get a known admin account — without relying on demo seed for menus (platform seed already ran or is invoked first).

**Independent Test**: After migrate + platform seed, run demo seed twice; second run is a no-op for the existing admin.

**Acceptance Scenarios**:

1. **Given** no demo user, **When** I run `app:seed-demo`, **Then** a demo admin exists (`admin@nowo-shell.local` / documented password).
2. **Given** demo user already exists, **When** I run demo seed again, **Then** the command reports it already exists and does not wipe real data.
3. **Given** demo seed runs, **When** it finishes, **Then** it does **not** create product sample telemetry or client DSN files.

---

### User Story 3 - Documented install and upgrade paths (Priority: P1)

As an operator reading README / UPGRADING, I follow: migrate schema, then platform seed; optionally demo seed.

**Acceptance Scenarios**:

1. **Given** README quick start, **When** I follow first-install steps, **Then** I see migrate + platform seed (`make bootstrap`), then optional `make seed` / `make ready`.
2. **Given** UPGRADING, **When** I upgrade, **Then** I am instructed to run migrations and `app:seed-platform` after pull/install.
3. **Given** Makefile help, **When** I list targets, **Then** `bootstrap`, `seed-platform`, and `seed` are distinguishable.
4. **Given** `var/secrets/` is missing, **When** I run seed/bootstrap Make targets, **Then** Make creates `var/secrets/` first so Halite can write keys (see `048-prod-encrypt-key`).

## Edge Cases

- Platform seed on a DB that never had menu tables migrated → clear failure pointing at migrations first.
- Fixture route names must stay platform-only (no issues/projects/mercure/ops ingest).

## Requirements

- **FR-001**: `app:seed-platform` upserts menus, breadcrumbs, cookie consent from `src/Setup/Demo/fixtures/`.
- **FR-002**: `app:seed-demo` provisions demo admin only (+ optional `--with-platform`).
- **FR-003**: `make bootstrap` = migrate + platform seed; `make seed` = platform + demo.
- **FR-004**: No `app:seed-sample` in this repository.

## Success Criteria

- Fresh DB + bootstrap yields navigable admin shell without product fixtures.
- Seed fixtures omit product route names.

## Related

- `056-setup-wizard`, REQ-BP-003, REQ-SETUP, REQ-MENU, REQ-BC, REQ-CC
