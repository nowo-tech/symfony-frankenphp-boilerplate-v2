# Changelog

All notable changes to **symfony-frankenphp-boilerplate-v2** (Nowo Shell platform starter) are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

This repository is the **platform** starter (`Identity` / `Shared` / `Setup`). Product features (ingest, issues, Mercure, OTLP, Ops) belong in apps such as [symfony-beacon](https://github.com/nowo-tech/symfony-beacon), not here.

## [Unreleased]

### Added
- **UiKit 2 page contract (REQ-UI-003):** `nowo_ui_kit.layouts.admin|guest` → thin bridges `templates/layouts/ui_admin.html.twig` / `ui_guest.html.twig` and ONE `nowo_ui_kit.theme` (app slot classes, `--app-*` palette as UiKit tokens, `layouts/shell/*`, `layouts/partials/_pagination.html.twig` and `_toasts.html.twig` as theme partials). Kit page metrics in `assets/styles/kit-admin.css` (Vite entry `kit-admin-styles`). One-block overrides outside `templates/bundles/`: `auth/reset_request` (code-entry link), `maintenance/page` (503 art + countdown), `kit/maintenance_mode_panel_index` (preview link + UiKit form theme), `kit/site_backup_setup_token`, `pwa/install_links` (unavailable hint). Functional `UiPageContractTest` + Playwright `e2e/admin/ui-page-contract.spec.ts`.

- **AuthKit 1.20 security UX** (REQ-AUTH-008): slide-to-confirm, device-intelligence (`di_obs`, Trusted browsers), otp-input on password-reset complete; FormKit **2.5.2**. Device ID is not a credential. `qr_login.mode` stays **disabled** (AUTH-005). Migration `Version20260825190000`.

- **SMS Bridge provider** (`App\Shared\Sms\`): `SMS_PROVIDER=sms_bridge` + `bin/console app:sms:send`. Docs: [docs/product/SMS.md](product/SMS.md).

- **Admin create modals:** `/admin/users` and `/admin/groups` create forms open in a list-page dialog (`templates/shared/_confirm_dialog.html.twig`, `?new=1`); legacy `/new` URLs redirect to the modal.

- **Product UI manual** ([docs/manual/](manual/README.md)) with Playwright-captured screenshots (`make docs-manual-screenshots`) and wiki Home source (`docs/wiki/Home.md`, `make wiki-push-home`).

- Platform **`.checkbox`** / `.checkbox-label` paint (AuthKit, kit-admin, RoutingKit); FormKit `checkbox` field class (`086-platform-form-chrome`).

### Changed

- **UiKit 1.8.2 → 2.4.0** and the kits on the page contract: AuthKit **1.27.2**, BreadcrumbKit **2.7.2**, CookieConsent **1.17.0**, DashboardMenu **2.8.1**, HttpLog **1.9.1**, MaintenanceMode **1.10.1**, Pwa **1.10.0**, RoutingKit **1.10.0**, SiteBackup **1.21.2**; FormKit **2.12.0**, PasswordToggle **2.4.1**, `doctrine/orm` **3.7.5** (kit floor). Kit configs moved to the contract keys (`web_ui.*`, `dialog_sizes`, theme slots); the per-kit `layout_template` / `css_framework` / `icon_set` / `css.button_class` / `install_prompt.*_button_class` keys are gone.
- `nowo-ui.css` is the UiKit 2 layered stylesheet (`@layer theme, base, nowo-ui, components, utilities`, declared at the top of `assets/styles/tailwind.css`); `<html data-nowo-ui-host-tokens>` + `{{ nowo_ui_tokens() }}` in both shells; Tailwind `@source` on `config/packages/nowo_ui_kit.yaml`.
- CSP: the request nonce is also published as the `csp_nonce` request attribute (the attribute the kits read), so the UiKit runtimes, the AuthKit page scripts and the password toggle run under the strict nonce policy.
- Kit tables use icon row actions (`nowo_ui_kit.row_actions_display: icon`, label in `aria-label` / `title`), as the 1.x kit forks did.
- CookieConsent 1.17 sanitizes `preferences_bubble_icon`: the cookie bubble is an inline SVG (config + demo fixture) instead of an `<img>` (external references are rejected); the drop-shadow filter of `public/illustrations/cookie-consent-bubble.svg` is not allowed and was dropped.
- Product UI manual screenshots are WebP (re-captured, `e2e/support/webp.ts`), 48 PNGs → 57 WebP (3.7 → 2.4 MiB).
- CSS: `_auth_kit.scss` 172 → 17 lines (AuthKit BEM skin gone), `_setup.scss` 448 → 213 (SiteBackup 1.x markup restyle gone, token gate only), `_cookie_consent.scss` `!important` 1 → 0, inline `templates/kit/_kit_admin_styles.html.twig` (1141 lines, 46 `!important`) → `kit-admin.css` (500 lines, 1 `!important` for the DashboardMenu tree indent in phone card mode).

- **SiteBackup 1.13.8 → 1.17.0** (`nowo-tech/site-backup-bundle`): panel `security.access_roles: [ROLE_ADMIN]` declared explicitly (1.14.2+ fail-closed on empty list); per-IP panel login throttle on by default (5 / 15 min → HTTP 429 + `Retry-After`, pool `cache.rate_limiter` / `cache.app`), disabled only under `when@test`; `InstanceSettingsDurableSetupDoneStore::clearDone()` (1.15 SPI); host `panel/index.html.twig` override confirms via `data-nowo-confirm` + delegated listener (1.16, no inline `onsubmit`). Restores / setup steps that rewrite files now leave `var/site-backup/worker-restart.required` — restart FrankenPHP workers, then `bin/console nowo:site-backup:worker-restart --clear`.
- **Beacon `send.stack_vars`** (REQ-BEACON-007): documented off in `nowo_beacon.yaml`; beacon-bundle 1.8.0 has no such key (added in 1.10.0) and never sends frame arguments — uncomment `stack_vars: false` when bumping Beacon to ≥ 1.10.
- **Kit-over-shim (OTHER pass 40):** LoginThrottle **3.2.0**, CookieConsent **1.9.7**, Pwa **1.5.0**, HotReload **1.5.2**. Deleted host shims now owned by kits: `AuthKitAwareLoginRateLimiter`, `PwaStatelessCookieSubscriber`. PWA bootstrap `Set-Cookie` strip uses kit `http.strip_set_cookie_on_bootstrap: true`.
- **Hot reload via HotReloadBundle ≥1.5:** `client_mode: shared_worker` (bundle-served `/_nowo/hot-reload/*`); host deferred client removed.
- CookieConsent **1.9.6** + Dashboard Menu **2.1.10**: embed via `nowo_cookie_consent_render()` (no kernel sub-request); kit tags `SearchQueryType` (no host `form.type` override).
- Rector / PHP-CS-Fixer file headers document ownership split (semantic vs PER-CS; always CS after Rector).

- Password toggle: eye on the **right** with **0.5rem** gap; `nowo_password_toggle` uses `input-group-text` (host SCSS owns flex layout).
- Form themes (`app_theme` / `auth_kit_theme`) and kit-admin `.nowo-ui-check` aligned to platform checkbox chrome.

### Security

- The CSP response subscriber no longer stamps the request nonce onto every bare inline `<script>` / `<style>` (it also blessed injected markup); templates and kits print their own nonces. Regression test in `ContentSecurityPolicySubscriberTest`.

### Removed

- Kit forks and bridges the UiKit 2 kits no longer need: `templates/bundles/NowoUiKitBundle/` (shell / burger / pagination / toasts moved to theme partials), `NowoAuthKitBundle/` (layout, QR, reset request), `NowoBreadcrumbKitBundle/`, `NowoCookieConsentBundle/`, `NowoDashboardMenuBundle/`, `NowoHttpLogBundle/`, `NowoRoutingKitBundle/`, `NowoSiteBackupBundle/`, `NowoMaintenanceModeBundle/`, `NowoPwaBundle/` (24 templates), every `templates/kit/*_layout.html.twig` + `_kit_admin_styles` / `_pagination` / `_kit_modal_body_portals` / `site_backup_panel_login` (14 templates), `templates/security/magic_login_confirm.html.twig`.
- Dead host shims: `assets/kit-admin.ts` (1.x kit config islands, filter split, modal portals), `App\Shared\Http\KitInlineConfigScriptSubscriber` (kit inline config scripts → islands), `App\Setup\Form\SetupTokenGateType` (the bundle token page builds its form), `App\Shared\Pagination\PagePagination` (unused copy; use `Nowo\UiKitBundle\View\Pagination::fromTotal()`).

### Notes

- Run `doctrine:migrations:migrate` for Device Intelligence tables (`Version20260825190000`). Run `make vite-build` after pull (OTP / slide host SCSS). Password-manager extension icons on focus (e.g. NordPass) are outside host control.

## [1.0.3] - 2026-08-07

### Fixed

- PHPStan (CI **Quality**): `SeedDemoCommand` uses `InstanceSettingsRepository::getOrCreate()`; `AdminUserController` imports `RuntimeException`; `UserUiPreferences::getPreferredCollapsedIssuePanels()` reads the stored column; remove unused `CookieConsentDemoSeeder::requireNullableString()`.
- PHPStan test hygiene: DomCrawler checkbox via `ChoiceFormField::tick()`, `PrePersistEventArgs` stubs use `EntityManagerInterface`, MenuTwig anonymous `getRouteName(): string`, prefer `$_SERVER` for test DB URL, and ignore FrankenPHP `$_ENV`/`putenv` rules only on `tests/bootstrap.php`.

### Notes

- No database migrations. No Composer or kit pin changes.
- Operators: pull `v1.0.3` (or `main`); no migrate/seed required for this patch. Redeploy application code if you run `app:seed-demo` (setup-completed marking now uses `getOrCreate()`).

## [1.0.2] - 2026-08-07

### Fixed

- Twig-CS-Fixer hash and filter spacing in BreadcrumbKit / CookieConsent / Dashboard Menu admin Twig overrides so CI **Quality** (Twig CS) passes.
- Concurrent Compose entrypoints (`php` + `messenger*`) racing Composer / pnpm installs: serialize with `flock` on `./.deps.lock`, retry Composer when UX `file:` asset packages are missing before `pnpm install`, and harden CI Compose E2E (prefetch Composer, install Playwright deps inside the `php` service).

### Notes

- No database migrations. No Composer or kit pin changes.
- `.deps.lock` is gitignored (runtime lock on the project bind mount).
- Operators: pull `v1.0.2` (or `main`); no migrate/seed required for this patch. Redeploy / recreate containers so the updated FrankenPHP entrypoint is used.

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

[Unreleased]: https://github.com/nowo-tech/symfony-frankenphp-boilerplate-v2/compare/v1.0.3...HEAD
[1.0.3]: https://github.com/nowo-tech/symfony-frankenphp-boilerplate-v2/compare/v1.0.2...v1.0.3
[1.0.2]: https://github.com/nowo-tech/symfony-frankenphp-boilerplate-v2/compare/v1.0.1...v1.0.2
[1.0.1]: https://github.com/nowo-tech/symfony-frankenphp-boilerplate-v2/compare/v1.0.0...v1.0.1
[1.0.0]: https://github.com/nowo-tech/symfony-frankenphp-boilerplate-v2/releases/tag/v1.0.0
