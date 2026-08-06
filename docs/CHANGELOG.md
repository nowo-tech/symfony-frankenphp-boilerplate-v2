# Changelog

All notable changes to **symfony-frankenphp-boilerplate-v2** (Nowo Shell platform starter) are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

This repository is the **platform** starter (`Identity` / `Shared` / `Setup`). Product features (ingest, issues, Mercure, OTLP, Ops) belong in apps such as [symfony-beacon](https://github.com/nowo-tech/symfony-beacon), not here.

## [Unreleased]

## [1.0.1] - 2026-08-06

### Fixed

- PHP-CS-Fixer style so the CI **Quality** job passes: import order, `@deprecated` phrasing, native function escapes (`\sprintf` / `\is_array`), shell-style `#` comment → `//` in `tests/bootstrap.php`, and related tidy in Setup demo seeders / Identity / Appearance tests.

### Notes

- No database migrations. No Composer or kit pin changes.
- Operators: pull `v1.0.1` (or `main`); no migrate/seed required for this patch.

## [1.0.0] - 2026-08-06

First **stable** release of the Nowo Shell platform boilerplate: Symfony 8.1 + PHP 8.5 + FrankenPHP host with AuthKit, users/groups, appearance, legal/cookies, menus, HTTP logging, SiteBackup, and Nowo kits. Spec-Driven Development via GitHub Spec Kit.

### Added

- **Stack:** Symfony **8.1**, PHP **≥8.5**, FrankenPHP (classic / worker via `FRANKENPHP_MODE`), MySQL **9.7**, Messenger, Vite + TypeScript + SCSS + Tailwind **4**, Compose + Make workflows.
- **Identity:** AuthKit first-user registration, login / logout (CSRF), remember-me, password reset (link + OTP), magic login (Mailer-gated), social login admin, QR login PNG (`endroid/qr-code`), login throttle, password strength / policy / toggle, UserKit enable/disable + presence, users and groups admin, account Profile / Security / Display / Privacy, product tours, GDPR export/anonymize hooks where applicable to platform entities.
- **Shared:** Administration hub; encrypted instance Mailer DSN/From; appearance (theme presets light/dark, brand, layout, colors, corner/border/footer); legal pages; CookieConsent (DB profile + admin UI); Dashboard Menu + BreadcrumbKit; RoutingKit dual bare/`/{locale}` paths; HTTP log admin (`nowo-tech/http-log-bundle`); CSP via PHP subscriber + kit JSON islands; branded HTTP error pages; health probes `/health/live` / `/health/ready`; PWA kit.
- **Setup:** SiteBackup cold-start wizard `/setup` + panel `/_site_backup`; platform seed (`app:seed-platform` — menus, breadcrumbs, cookie consent); demo seed (`app:seed-demo`); setup redirect when catalogs are empty.
- **Kits (pinned):** AuthKit **1.15.0**, FormKit **2.2.0**, UiKit **1.7.0**, RoutingKit **1.3.0**, Dashboard Menu **2.0.1**, BreadcrumbKit **2.1.3**, CookieConsent **1.6.0**, HttpLog **1.1.0**, SiteBackup **1.10.0**, plus audit / encrypt / migrations / user / password / PWA / select-all / tag-input / login-throttle.
- **Form conventions:** shared `templates/form/_fields.html.twig` field loop; FormKit Types on admin create-edit forms; UiKit shell / toasts / tabs / pagination / thinking orb chrome.
- **QA / DX:** PHPUnit layout `tests/Unit|Functional|Integration/` + `tests/Support/`; Playwright E2E (`make test-e2e`); CI (PHPUnit, coverage soft gate, Gitleaks, git hygiene / no Cursor co-author); Spec Kit constitution + skills under `.cursor/skills/speckit-*`; docs index under [`docs/`](README.md).

### Notes

- This is the first tag on [`nowo-tech/symfony-frankenphp-boilerplate-v2`](https://github.com/nowo-tech/symfony-frankenphp-boilerplate-v2). Lineage was extracted from the former Nowo Shell / Beacon product tree; historical product-only changelog entries are intentionally omitted.
- Clone and follow [INSTALL.md](INSTALL.md). Upgrade path for consumers of older private forks: see [UPGRADING.md](UPGRADING.md).

[Unreleased]: https://github.com/nowo-tech/symfony-frankenphp-boilerplate-v2/compare/v1.0.1...HEAD
[1.0.1]: https://github.com/nowo-tech/symfony-frankenphp-boilerplate-v2/compare/v1.0.0...v1.0.1
[1.0.0]: https://github.com/nowo-tech/symfony-frankenphp-boilerplate-v2/releases/tag/v1.0.0
