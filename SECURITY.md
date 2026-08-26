# Security Policy

## Supported versions

Security fixes target the latest revision of `main` for [symfony-frankenphp-boilerplate-v2](https://github.com/nowo-tech/symfony-frankenphp-boilerplate-v2). Upgrade dependencies and apply database migrations regularly.

## Reporting a vulnerability

Do not file public issues for security vulnerabilities. Report privately through [GitHub Security Advisories](https://github.com/nowo-tech/symfony-frankenphp-boilerplate-v2/security/advisories/new) or email [hectorfranco@nowo.tech](mailto:hectorfranco@nowo.tech) with subject `symfony-frankenphp-boilerplate-v2 security`.

Include the affected commit, impact, reproduction steps, and any proposed disclosure deadline.

## Operational notes

Protect `.env`, `APP_SECRET`, the field-encryption material under `var/secrets/`, Mailer credentials, and SiteBackup credentials. In production, configure unique `SITE_SETUP_TOKEN`, `SITE_BACKUP_PASSWORD_HASH`, and `APP_SECRET`; documented local defaults (including `ChangeMePleaseUseARealSecret`) must never be deployed — `SiteBackupSecurityDefaultsGuard` refuses boot outside `dev`/`test`.

Magic-login and password-reset mail depends on a configured Mailer DSN. Run the project secret scan before publishing changes:

```bash
make secrets-scan
```
