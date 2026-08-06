# Feature Specification: Bootstrap Nowo Shell

**Feature Branch**: `001-bootstrap`  
**Created**: 2026-07-19  
**Status**: Completed  

**Input**: Platform starter — Symfony 8.1 + FrankenPHP, AuthKit login, modular Identity/Shared/Setup layout.

## User Scenarios & Testing

### User Story 1 - Boot the platform stack (Priority: P1)

As a developer, I can start Docker Compose and reach the AuthKit login page for **Nowo Shell** (`symfony-frankenphp-boilerplate-v2`).

**Acceptance Scenarios**:

1. **Given** a fresh clone with `.env` from `.env.dist`, **When** I run `make up`, **Then** the stack starts on ports **9081** / **9444** (override via `HTTP_PORT` / `HTTPS_PORT`).
2. **Given** the stack is up, **When** I open `/{DEFAULT_LOCALE}/login` (or bare `/login`, which redirects there), **Then** I see the AuthKit UI branded via Appearance / AuthKit config (not a custom SecurityController). `.env.dist` ships `DEFAULT_LOCALE=en`.

### User Story 2 - Modular layout + security skeleton (Priority: P1)

As a developer, the codebase uses modular Symfony packages and Security is registered.

**Acceptance Scenarios**:

1. **Given** the repo, **When** I inspect `src/`, **Then** only `Identity`, `Shared`, and `Setup` exist (plus `Kernel.php`).
2. **Given** an anonymous request to `/`, **When** it is handled, **Then** it redirects to `/{DEFAULT_LOCALE}/login` (or setup when SiteBackup gate applies).
3. **Given** an authenticated user, **When** they open the home route (`dashboard_home`), **Then** the platform welcome home loads (not a product projects list).

### User Story 3 - FrankenPHP classic ↔ worker (Priority: P1)

As a developer, I can switch FrankenPHP mode via `FRANKENPHP_MODE` only.

**Acceptance Scenarios**:

1. **Given** `FRANKENPHP_MODE=classic`, **When** the entrypoint starts, **Then** classic request handling is active.
2. **Given** `FRANKENPHP_MODE=worker`, **When** the entrypoint starts, **Then** worker config points at `./public/index.php` and requests succeed without host PHP.

## Requirements

- Constitution describes the **platform** mission (not error-tracking / Envelope)
- Tailwind via Vite (TypeScript/SCSS asset pipeline)
- Security + AuthKit login surface enabled (locale-prefixed canonical URLs; bare `/login` redirects via `DEFAULT_LOCALE`)
- Docs, specs, and PHPDoc in English; UI may be translated (`DEFAULT_LOCALE` / `en` default, see `docs/CONTRIBUTING.md` and `docs/dev/ADDING-LOCALES.md`)
- Only `.env.dist` is versioned; no `env(VAR):` defaults in `parameters.yaml`
- Database service has no host-published ports (REQ-FP-006)

## Success Criteria

- `make up` + AuthKit login reachable over HTTPS
- `make bootstrap` migrates and seeds platform catalogs
- Module map matches `docs/ARCHITECTURE.md`
