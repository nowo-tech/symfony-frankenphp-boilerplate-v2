# Upgrading Guide

This guide covers **symfony-frankenphp-boilerplate-v2** (Nowo Shell platform starter: `Identity` / `Shared` / `Setup`).

Product apps built on this tree (e.g. [symfony-beacon](https://github.com/nowo-tech/symfony-beacon)) keep their own upgrade notes for ingest, issues, Mercure, and related domains.

## Table of contents

- [First install (no previous version)](#first-install-no-previous-version)
- [Upgrading from 1.0.0 to 1.0.1](#upgrading-from-100-to-101)
- [Upgrading from an untagged / private fork to 1.0.0](#upgrading-from-an-untagged--private-fork-to-100)
- [After every upgrade](#after-every-upgrade)

---

## First install (no previous version)

```bash
git clone git@github.com:nowo-tech/symfony-frankenphp-boilerplate-v2.git
cd symfony-frankenphp-boilerplate-v2
cp .env.dist .env
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
- Do not commit `.env`, `.env.dev`, or `.env.local`.
- After pulling upstream changes, always merge new keys from `.env.dist` into your local `.env`.

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
