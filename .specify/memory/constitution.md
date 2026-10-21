<!--
Sync Impact Report
- Version change: 1.4.2 → 2.0.0 (MAJOR: platform boilerplate mission; drop Beacon product principles)
- Modified principles: II (module list), III (mission), VI (removed Envelope ingest), technical constraints
- Added sections: none
- Removed sections: Envelope ingest / client DSN product constraints
- Templates: unchanged
- Follow-up: keep docs/ARCHITECTURE.md and specs/001, 083, 085 in sync
-->

# nowo-shell Constitution

## Core Principles

### I. Spec-First (NON-NEGOTIABLE)

No feature is implemented without going through the Spec Kit SDD flow:
**Constitution → Specify → (Clarify) → Plan → Tasks → (Analyze/Checklist) → Implement**.
Artifacts under `specs/` are the source of truth; code must align with them.

### II. Canonical stack

- **PHP 8.5** via `dunglas/frankenphp:1.13.2-php8.5.12` (exact tag, bumped deliberately)
- **Symfony 8.1.\*** (Flex + native Runtime)
- **Docker Compose** as the required local environment
- FrankenPHP in **classic** or **worker** mode, switched only via `FRANKENPHP_MODE`
- **MySQL 9.7**, Symfony Messenger, Pentatrion Vite + **Tailwind CSS**
- Modular Symfony layout under `src/{Identity,Shared,Setup}` — product apps may add further domains beside this baseline
- `Shared` is cross-cutting presentation / instance config only — not domain write ownership (see `083` / `085` and `docs/ARCHITECTURE.md`)
- Prefer official `nowo-tech/*` kits for auth/users/cookies/menus where applicable (AuthKit, UserKit, AuditKit, cookie-consent, etc.)
- Progressive Web App via `nowo-tech/pwa-bundle` for browser installability
- **UI typeface**: Montserrat (self-hosted) for brand and app chrome; IBM Plex Mono for code

Do not introduce alternate stacks (Nginx+FPM, Apache) without amending this constitution.
Do not introduce full DDD/hexagonal layers; keep Symfony modular conventions.
Do not replace the Twig app with a separate mobile-only UI stack without a new spec amending this principle.
Do not reintroduce Hotwire Native / `symfony/ux-native` without amending this constitution (removed; see `docs/dev/NATIVE-MOBILE.md`).

### III. Product mission

**nowo-shell** (`nowo-tech/symfony-frankenphp-boilerplate-v2`) is the **platform starter** for Nowo self-hosted Symfony apps: AuthKit login, users/groups, account preferences, appearance, legal/cookie consent, menus/breadcrumbs, HTTP logging, SiteBackup setup, and Nowo UI/form/audit/mailer/routing/PWA kits.

It is **not** an error-tracking, telemetry, Mercure, or public API product. Product apps clone this tree and add their own domains under `src/`.

Operators use the **Twig UI in the browser** (installable as a **PWA**). Native store-client shells are out of scope for this repository.

### IV. Docker-first

Any runtime, PHP extension, or Caddy change must live in `Dockerfile`, `.docker/`, or `compose*.yaml`.
Do not document or depend on host-installed PHP/Composer.

### V. Classic ↔ worker compatibility

Application code must be safe in **worker mode** (resettable cross-request state; per-request stateful services implement `ResetInterface` when needed).
See `docs/FRANKENPHP-CODING.md`. Default coding target: `FRANKENPHP_MODE=worker` and `FRANKENPHP_RESET_KERNEL=false`. Local Compose defaults may use classic for simpler debugging.

### VI. Platform surface only

The boilerplate MUST ship only platform domains and kits. Do not reintroduce product-only packages (ingest clients, Mercure hubs, Nelmio product API docs, OTLP, …) into this repository. Product sample seeds (`seed-sample`, telemetry fixtures) are out of scope.

### VII. English-only documentation and code comments (NON-NEGOTIABLE)

All project prose MUST be written in **English** (docs, specs, PHPDoc, Twig UI copy, `lang="en"`).

### VIII. Tests required per feature

Every spec that changes behavior MUST ship PHPUnit coverage (unit and/or functional) matching its acceptance scenarios. CI must stay green.

### IX. Env defaults live in `.env.dist` (NON-NEGOTIABLE)

`config/parameters.yaml` MUST NOT define Symfony env default entries of the form `env(VAR_NAME): '…'`.

- Document and version operator-facing defaults **only** in `.env.dist` (and keep secrets out of git).
- Typed aliases such as `app.foo: '%env(int:FOO)%'` remain allowed when the variable is required or always present via `.env.dist` / runtime env.
- Environment-specific overrides (e.g. prod fail-closed flags) belong in `config/packages/*.yaml` under `when@…`, not as `env(…):` defaults in `parameters.yaml`.
- Prefer instance/database settings for tunable ops defaults; migrate operator knobs out of `.env.dist` into Admin UI when they are not boot/infra secrets.

Rationale: `env(NAME):` defaults in `parameters.yaml` hide required configuration, duplicate `.env.dist`, and make FrankenPHP / Compose / upgrade diffs harder to audit.

### X. No Cursor / agent attribution (NON-NEGOTIABLE)

Commits, GitHub **issues**, and **pull requests / merge requests** MUST use only the human author's identity. Do **not** add Cursor Agent (or similar IDE-agent) credit.

Forbidden (non-exhaustive):

- `Co-authored-by: Cursor`, `Co-authored-by: Cursor Agent`, or any `*@cursor.com` / `*@cursor.so` trailer
- `Made-with: Cursor` / `Made with Cursor` (or similar) in commit messages, issue bodies, or PR/MR bodies
- Invented co-authors for the agent in git metadata or GitHub UI

Use `make setup-hooks` locally; CI **Git hygiene** rejects forbidden trailers in git history (`make check-no-cursor-coauthor`). Strip before push if a trailer appears.

Rationale: contribution history and review attribution belong to the people who own the change, not the tooling that assisted.

## Technical constraints

- Entrypoint: `.docker/frankenphp/docker-entrypoint.sh` maps `FRANKENPHP_MODE` → `FRANKENPHP_CONFIG`.
- Document root: `/app/public`.
- Secrets: never in git. Version **only** `.env.dist`.
- Database Compose services MUST NOT publish host ports (REQ-FP-006); use `docker compose exec database …`.
- Mobile: PWA only in this repository (`docs/dev/NATIVE-MOBILE.md`).
- Configuration: see **Principle IX** — no `env(…):` default map entries in `config/parameters.yaml`.
- CSS tokens use the host prefix `--app-*` (not a product brand prefix).

## Development workflow (SDD)

1. `/speckit-constitution` — update principles for structural changes
2. `/speckit-specify` — what & why
3. `/speckit-clarify` — (optional)
4. `/speckit-plan` — how
5. `/speckit-tasks` — actionable tasks
6. `/speckit-implement` — implement per tasks
7. `/speckit-converge` — close gaps

Per-feature artifacts: `specs/NNN-name/{spec,plan,tasks}.md`.

## Governance

- This constitution overrides ad-hoc habits and agent prompts.
- Every significant PR/change must map to a spec under `specs/`.
- Commits, issues, and PRs/MRs MUST comply with **Principle X** (no Cursor / agent attribution).
- Amendments: edit this file, bump **Version**, update **Last Amended**.

**Version**: 2.0.0 | **Ratified**: 2026-07-19 | **Last Amended**: 2026-08-06
