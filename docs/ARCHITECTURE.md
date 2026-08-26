# Architecture

**Nowo Shell** (`symfony-frankenphp-boilerplate-v2`) is a **platform starter**: Identity, Shared shell, and Setup seed — not a product backlog app. Clone this tree for a new product; add product domains under `src/`. Do **not** copy Beacon product domains (projects, issues, ingest, Mercure product UI, Envelope DSN manuals, …) back into this boilerplate.

## Module layout

| Module | Path (typical) | Responsibility |
|--------|----------------|----------------|
| **Identity** | `src/Identity/` | Users, groups, account preferences, AuthKit host wiring, demo seed, security activity |
| **Shared** | `src/Shared/` | Appearance, mailer / instance settings, legal + cookie consent bridge, locale, menus, navigation, audit/user-action trail, health probes, HTTP helpers, Twig |
| **Setup** | `src/Setup/` | SiteBackup gate, platform catalog seeders (menus, breadcrumbs, cookie consent profiles) |

Symfony bundles and Nowo kits live under `config/packages/` and vendor; application code stays in the three modules above plus thin controllers/routes.

## Authentication (AuthKit + session)

- **AuthKit** (`nowo-tech` AuthKit) owns login UI (password, magic link, QR), remember-me, social OAuth credentials (DB-backed), and host install hooks.
- **Symfony Security** firewall `main` uses AuthKit authenticators; login throttling is shared across FrankenPHP workers via DB-backed counters.
- **HTTP sessions** use Redis (`framework.session.handler_id` → `REDIS_URL`) with a `prefix_seed` so multiple apps can share one Redis without key collisions.
- Account area (`/account/*`) is the end-user surface for profile, security history, privacy export/anonymize, display prefs, and locale.

## Instance RBAC

Two layers (see [`product/ROLES.md`](product/ROLES.md)):

1. **Symfony roles** — at least `ROLE_USER` / `ROLE_ADMIN` on the user.
2. **Instance RBAC** — `permission` + `role` + join tables; permission keys such as `admin.users.manage`. `ROLE_ADMIN` is a superuser shortcut while key-level `IsGranted` rolls out.

Product apps may add tenancy or membership RBAC; that does **not** ship here.

## Health

Public probes for operators and Compose/K8s:

| Endpoint | Intent |
|----------|--------|
| `/health/live` | Process up |
| `/health/ready` | Dependencies / queue depth signal (may be non-200 when degraded) |

No product “ops dashboard” is required in the shell; kit admin panels cover HTTP log and similar.

## FormKit and kit admin

- **FormKit** (and related Nowo form/UI kits) power platform forms (login, settings, account).
- **Kit admin** surfaces (menus, breadcrumbs, cookie consent, HTTP log, routing kit) are mounted under `/admin/...` or kit-specific prefixes for operators. Seeders in Setup install default catalogs so a fresh instance has working nav and consent without manual DB edits.

## Runtime: FrankenPHP classic vs worker

| Mode | Behaviour |
|------|-----------|
| **classic** | Boot per request (PHP-FPM-like). Best for local debug / Xdebug. |
| **worker** | Kernel stays warm; services must be request-safe (`ResetInterface` / `kernel.reset`). Optional `FRANKENPHP_RESET_KERNEL=true` clones the Kernel each request for stronger isolation at lower throughput. |

Coding rules and the mode matrix live in [`ops/FRANKENPHP-CODING.md`](ops/FRANKENPHP-CODING.md). Messenger consumers are a **separate** long-running process and are not the HTTP worker loop.

## Data and cache

- **MySQL** — primary persistence (users, settings, RBAC, kit catalogs). Compose supports standalone embedded DB or shared infra hosts (`docs/ops/SHARED-SERVER.md`).
- **Redis** — sessions + `cache.app` (and optional Messenger transport). Prefer Redis sessions so classic/worker/multi-replica share login state.
- **Mailpit** (dev) — catch SMTP for AuthKit mail flows.

## Platform vs product

| Platform (this repo) | Product (e.g. Beacon) |
|----------------------|------------------------|
| Identity / Shared / Setup | Domain modules (projects, issues, …) |
| AuthKit, instance RBAC, appearance, mailer, legal/cookies | Product menus, Mercure product channels, analytics |
| Health + kit admin | Product ops / ingest / Envelope docs |
| Clone BP-v2 and extend `src/` | Do not merge product domains back into BP-v2 |

When documenting or seeding, keep translation top-level keys and menus aligned with platform surfaces only.

## Related docs

- Roles: [`product/ROLES.md`](product/ROLES.md)
- Legal / cookies: [`product/LEGAL-AND-COOKIES.md`](product/LEGAL-AND-COOKIES.md)
- E2E catalog: [`product/E2E-USE-CASES.md`](product/E2E-USE-CASES.md)
- Install / prod: [`INSTALL.md`](INSTALL.md), [`PRODUCTION.md`](PRODUCTION.md)
