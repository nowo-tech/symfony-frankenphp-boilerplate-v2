# Upgrading Guide

This guide covers **symfony-frankenphp-boilerplate-v2** (Nowo Shell platform starter: `Identity` / `Shared` / `Setup`).

Product apps built on this tree (e.g. [symfony-beacon](https://github.com/nowo-tech/symfony-beacon)) keep their own upgrade notes for ingest, issues, Mercure, and related domains.

## Table of contents

- [First install (no previous version)](#first-install-no-previous-version)
- [Unreleased (main after 1.0.3)](#unreleased-main-after-103)
- [Upgrading from 1.0.2 to 1.0.3](#upgrading-from-102-to-103)
- [Upgrading from 1.0.1 to 1.0.2](#upgrading-from-101-to-102)
- [Upgrading from 1.0.0 to 1.0.1](#upgrading-from-100-to-101)
- [Upgrading from an untagged / private fork to 1.0.0](#upgrading-from-an-untagged--private-fork-to-100)
- [After every upgrade](#after-every-upgrade)

---

## First install (no previous version)

```bash
git clone git@github.com:nowo-tech/symfony-frankenphp-boilerplate-v2.git
cd symfony-frankenphp-boilerplate-v2
cp .env.dist .env.local
git config core.hooksPath .githooks   # optional: block Cursor co-author trailers
make up
make bootstrap   # migrate + app:seed-platform
```

Then either:

- Register the first admin at the AuthKit register URL (empty DB), or
- `make seed` for demo admin `admin@nowo-shell.local` / `admin123`

Open the HTTPS URL printed by `make up` (default port **9444**). Details: [INSTALL.md](INSTALL.md).

### Breaking expectations for consumers

This is a **self-hosted application**, not a Composer library. “Upgrading” usually means:

- **Pulling** tagged releases into your deployment clone, or
- **Rebasing / cherry-picking** upstream changes into your fork.

There is no `composer update nowo-tech/symfony-frankenphp-boilerplate-v2` path for application code.

### Env file policy

- Only `.env.dist` is versioned.
- Do not commit `.env`, `.env.local`, or `.env.dev` / `.env.prod`. Operator working file is `.env.local` (REQ-ENV-003).
- After pulling upstream changes, always merge new keys from `.env.dist` into your local `.env`.

---

## Unreleased (main after 1.0.3)

**No migrations.**

**UiKit 2 page contract (REQ-UI-003).** UiKit **2.4.0** and the kits on the contract (AuthKit 1.27.2, BreadcrumbKit 2.7.2, CookieConsent 1.17.0, DashboardMenu 2.8.1, HttpLog 1.9.1, MaintenanceMode 1.10.1, Pwa 1.10.0, RoutingKit 1.10.0, SiteBackup 1.21.2, FormKit 2.12.0, PasswordToggle 2.4.1, `doctrine/orm` 3.7.5):

```bash
composer install
php bin/console assets:install public
make vite-build    # new entry kit-admin-styles; kit-admin entry removed
php bin/console cache:clear
```

- Kit pages render in `templates/layouts/ui_admin.html.twig` / `ui_guest.html.twig` (`nowo_ui_kit.layouts`), styled by `nowo_ui_kit.theme` (`config/packages/nowo_ui_kit.yaml`). Apps cloned from this starter: delete `templates/kit/*_layout.html.twig`, `_kit_admin_styles.html.twig` and the `templates/bundles/Nowo*Bundle/` forks, and move product chrome into theme slots / partials instead of forks.
- Config keys renamed (old keys are deprecated aliases in the kits): `nowo_breadcrumb_kit.dashboard.*` → `web_ui.*` (`modals` → `dialog_sizes`; the route import uses `%nowo_breadcrumb_kit.web_ui.path_prefix%`), `nowo_dashboard_menu.dashboard.{enabled,layout_template}` → `web_ui.*`, `nowo_maintenance_mode.panel.*` → `web_ui.*`, `nowo_routing_kit.panel.{enabled,path_prefix}` → `web_ui.*`, `nowo_site_backup.panel.*` / `templates.*` → `web_ui.*`, `nowo_auth_kit.profiles.*.templates` / `css` → `web_ui.form_themes` / `web_ui.templates` + theme slots, `nowo_pwa.install_prompt.*_button_class` → theme slots (`theme.bundles.NowoPwaBundle`).
- Tests / E2E selectors: the AuthKit `.nowo-auth-kit__*` markup is gone — use `[data-nowo-ui-page="auth"]`, `[data-testid="auth-kit-<page>"]`.
- SiteBackup 1.21 panel: sign-in and panel are UiKit pages in the guest bridge; `data-nowo-confirm` is gone (UiKit confirm dialogs).

Kit-over-shim floors (OTHER pass 40): HotReload **1.5.2**, LoginThrottle **3.2.0**, CookieConsent **1.9.7**, Pwa **1.5.0**.

```bash
composer install   # kit bumps above
make vite-build    # required for .checkbox + password-toggle CSS
```

**Host shims removed** (kits own the behaviour now):

- LoginThrottle nested AuthKit username (`AuthKitAwareLoginRateLimiter`)
- PWA bootstrap `Set-Cookie` strip (`PwaStatelessCookieSubscriber` → `nowo_pwa.http.strip_set_cookie_on_bootstrap: true`)

**SiteBackup 1.13.8 → 1.17.0** ([bundle UPGRADING](https://github.com/nowo-tech/SiteBackupBundle/blob/main/docs/UPGRADING.md)):

```bash
composer install   # nowo-tech/site-backup-bundle 1.17.0 (symfony/rate-limiter is now a hard dependency)
php bin/console cache:clear
```

- **1.14.0 worker restart:** after a restore that applied files, or setup `cache_clear` / `database_url` writing `.env.local`, the bundle writes `var/site-backup/worker-restart.required`. Restart FrankenPHP workers (container restart or Caddy admin `POST /frankenphp/workers/restart`), then `bin/console nowo:site-backup:worker-restart --clear`.
- **1.14.2 access roles:** an empty `security.access_roles` denies the panel. `nowo_site_backup.yaml` now declares `access_roles: [ROLE_ADMIN]` (password gate stays additional).
- **1.15.0 SPI:** `InstanceSettingsDurableSetupDoneStore` implements `clearDone()` (clears `setup_completed_at`). `setup.reopen_when_detector_requires` stays off.
- **1.16.0:** worker memos on by default (`schema_probe_ttl: 60`); if you drop the database under running workers, restart them or set `setup.worker_memo.schema_probe_ttl: 0`. The host `panel/index.html.twig` override uses `data-nowo-confirm` instead of inline `onsubmit` (CSP); re-apply if you customised it.
- **1.17.0 login throttle:** 5 panel password attempts per client IP per 15 minutes, then HTTP 429 + `Retry-After`; success resets the counter and rotates the session id. Uses `cache.rate_limiter` (fallback `cache.app`). With several app servers point `security.login_throttle.cache_pool` at a shared pool; behind a proxy configure `framework.trusted_proxies`. Disabled only under `when@test`.

Password-manager icons on focus (NordPass, etc.) are extension UI, not a host layout regression.

## Upgrading from 1.0.2 to 1.0.3

```bash
git fetch --tags
git checkout v1.0.3   # or pull main at the release commit
composer install   # optional; lockfile unchanged
```

**1.0.3** is a patch: PHPStan / CI Quality (including a `SeedDemoCommand` repository API fix). **No migrations**, no kit bumps, no `.env.dist` changes. No `seed-platform` or asset rebuild required solely for this release.

---

## Upgrading from 1.0.1 to 1.0.2

```bash
git fetch --tags
git checkout v1.0.2   # or pull main at the release commit
composer install   # optional; lockfile unchanged
docker compose up -d --force-recreate   # pick up FrankenPHP entrypoint flock fix
```

**1.0.2** is a patch: Twig-CS-Fixer overrides + Docker/CI dependency install race. **No migrations**, no kit bumps, no `.env.dist` changes. No `seed-platform` or asset rebuild required solely for this release. Recreate containers so the updated entrypoint (`.deps.lock` / `flock`) is active.

---

## Upgrading from 1.0.0 to 1.0.1

```bash
git fetch --tags
git checkout v1.0.1   # or pull main at the release commit
composer install   # optional; lockfile unchanged
```

**1.0.1** is a patch: PHP-CS-Fixer / CI Quality only. **No migrations**, no kit bumps, no `.env.dist` changes. Redeploy application code (or pull the tag); no `seed-platform` or asset rebuild required solely for this release.

---

## Upgrading from an untagged / private fork to 1.0.0

If you already run a copy of this platform tree without a public tag:

```bash
git fetch --tags
git checkout v1.0.0   # or merge main at the release commit
composer install
docker compose up -d
php bin/console doctrine:migrations:migrate --no-interaction
php bin/console app:seed-platform
pnpm install
make vite-build
php bin/console assets:install
```

### Kit / AuthKit notes (1.0.0 pins)

Host pins include FormKit **2.2.0**, AuthKit **1.15.0**, UiKit **1.7.0**, RoutingKit **1.3.0**, Menu / Breadcrumb / CookieConsent / SiteBackup / HttpLog as in `composer.lock`. Smoke menus, breadcrumbs, cookie admin, `/admin/_routing/`, `/setup`, and `/_site_backup` after upgrade.

After AuthKit **1.15**, if you have existing social OAuth rows in plaintext, run once:

```bash
php bin/console doctrine:encrypt:database --force
```

Re-run platform seed so breadcrumb trails include cookie-consent settings sections. Clear UX Icons cache if needed (`bin/console cache:clear`).

### Appearance theme presets

Migrations add `site_appearance` theme preset / layout columns when present in this tree. After migrate, open **Administration → Appearance → Themes** and confirm light/dark cards.

### HttpLog

Ensure Messenger workers consume HttpLog messages (routed to `async`), open **Administration → HTTP log**, and schedule `nowo:http-log:purge` if you enable request auditing. Document retention in operator privacy copy — [LEGAL-AND-COOKIES.md](product/LEGAL-AND-COOKIES.md).

### PHPUnit / E2E (developers)

Tests live under `tests/Unit/`, `tests/Functional/`, `tests/Integration/`, helpers in `tests/Support/`. Playwright: `make test-e2e` after `make up` + seed. See [CONTRIBUTING.md](CONTRIBUTING.md).

---

## After every upgrade

1. Diff `.env.dist` → merge new keys into `.env`.
2. `composer install` + `pnpm install` + `make vite-build`.
3. `doctrine:migrations:migrate --no-interaction`.
4. `app:seed-platform` (idempotent catalogs).
5. Smoke AuthKit login, Appearance, Mailer (if used), menus/breadcrumbs, SiteBackup panel.
