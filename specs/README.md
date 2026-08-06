# Specs index — Nowo Shell

Spec-Driven Development artifacts for `symfony-frankenphp-boilerplate-v2` (brand **Nowo Shell**), using [GitHub Spec Kit](https://github.com/github/spec-kit) (`specify` CLI + Cursor `speckit-*` skills).

Constitution: [`.specify/memory/constitution.md`](../.specify/memory/constitution.md)  
Architecture: [`docs/ARCHITECTURE.md`](../docs/ARCHITECTURE.md)  
Workspace REQs: [`OTHER_FULL_SPECS_DETAILS.md`](../../OTHER_FULL_SPECS_DETAILS.md) (sibling)

Integration: `cursor-agent` (see `.specify/integration.json`). Verify with `make specify-check`.

Numbering is inherited from the Beacon lineage for continuity; **product-only** Beacon specs (ingest, issues, OTLP, …) are **not** shipped here. Folders such as `002-identity-project` keep their historical names but describe the **platform** subset only.

## Platform catalog

| Spec | Topic | Status |
|------|-------|--------|
| [001-bootstrap](001-bootstrap/spec.md) | Compose, AuthKit login, FrankenPHP, module skeleton | Completed |
| [002-identity-project](002-identity-project/spec.md) | Users, groups, account, home (no Project tenancy) | Completed |
| [007-hardening](007-hardening/spec.md) | Security hardening baseline | Completed* |
| [008-ux-native](008-ux-native/spec.md) | Native shell (superseded / removed) | Superseded |
| [033-coverage-ci](033-coverage-ci/spec.md) | Coverage floors in CI | Completed |
| [034-encrypted-mailer-dsn](034-encrypted-mailer-dsn/spec.md) | Encrypted mailer DSN in instance settings | Completed |
| [036-admin-identity-audit](036-admin-identity-audit/spec.md) | Audit timestamps/blame on identity | Completed |
| [037-authkit-identity-migration](037-authkit-identity-migration/spec.md) | AuthKit host identity migration | Completed |
| [043-gdpr-user-export](043-gdpr-user-export/spec.md) | GDPR account export / anonymize | Completed |
| [044-instance-config-export](044-instance-config-export/spec.md) | Instance config export/import | Completed |
| [047-admin-safe-redirect](047-admin-safe-redirect/spec.md) | Safe internal redirects | Completed |
| [048-prod-encrypt-key](048-prod-encrypt-key/spec.md) | Halite / encrypt key handling | Completed |
| [053-security-headers](053-security-headers/spec.md) | Security headers | Completed |
| [055-install-seed-layers](055-install-seed-layers/spec.md) | Platform + demo seed layers | Completed |
| [056-setup-wizard](056-setup-wizard/spec.md) | SiteBackup `/setup` cold-start | Completed |
| [057-product-tour](057-product-tour/spec.md) | driver.js product tour | Completed* |
| [060-authkit-social-login](060-authkit-social-login/spec.md) | Social login admin | Completed |
| [062-sitebackup-nondev-guard](062-sitebackup-nondev-guard/spec.md) | SiteBackup non-dev guard | Completed |
| [063-branded-http-errors](063-branded-http-errors/spec.md) | Branded HTTP error pages | Completed |
| [064-routing-kit](064-routing-kit/spec.md) | RoutingKit dual paths / panel | Completed |
| [065-mailer-dsn-audit](065-mailer-dsn-audit/spec.md) | Mailer DSN audit trail | Completed |
| [066-local-mailpit](066-local-mailpit/spec.md) | Local Mailpit profile | Completed |
| [072-authkit-1.12](072-authkit-1.12/spec.md) | AuthKit upgrade notes | Completed |
| [075-qr-png](075-qr-png/spec.md) | QR PNG helpers | Completed* |
| [077-form-type-field-loop](077-form-type-field-loop/spec.md) | Form type field loop | Completed |
| [078-form-save-restore-actions](078-form-save-restore-actions/spec.md) | Save / Restore form actions | Completed |
| [081-formkit-uikit-kit-sync](081-formkit-uikit-kit-sync/spec.md) | FormKit / UiKit kit admin sync | Completed |
| [082-appearance-theme-presets](082-appearance-theme-presets/spec.md) | Appearance themes (`shell`, …) | Completed |
| [083-module-boundaries](083-module-boundaries/spec.md) | Platform module boundaries | Completed |
| [085-architecture-convergence](085-architecture-convergence/spec.md) | Platform architecture convergence | Completed |

\*May still mention Beacon product examples in older plan/tasks companions; treat `spec.md` + constitution as normative for this repo.

## Adding a feature

Follow Spec Kit: specify → plan → tasks → implement under `specs/NNN-short-name/`. Bump constitution when Principle II/III/VI change.
