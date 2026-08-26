# Documentation index

English operator and developer manuals for **Nowo Shell** (`symfony-frankenphp-boilerplate-v2`).

This repository is the **platform starter** (Identity / Shared / Setup). Product manuals for ingest, issues, Mercure, and Envelope DSN live in product apps such as [symfony-beacon](https://github.com/nowo-tech/symfony-beacon) — not here.

## Start here (docs root)

| Doc | Topic |
|-----|--------|
| [INSTALL.md](INSTALL.md) | First install, seed layers, SiteBackup setup |
| [PRODUCTION.md](PRODUCTION.md) | Prod image, secrets, health, Messenger |
| [UPGRADING.md](UPGRADING.md) | First install and upgrade notes |
| [CHANGELOG.md](CHANGELOG.md) | Release history |
| [ROADMAP.md](ROADMAP.md) | Platform backlog |
| [RELEASE.md](RELEASE.md) | Cut a tagged release |
| [CONTRIBUTING.md](CONTRIBUTING.md) | PR / QA / Spec Kit conventions |
| [ARCHITECTURE.md](ARCHITECTURE.md) | Modules, AuthKit, RBAC, Health, FrankenPHP, Redis |

## Product (`product/`)

| Doc | Topic |
|-----|--------|
| [ROLES.md](product/ROLES.md) | Instance `ROLE_USER` / `ROLE_ADMIN` |
| [LEGAL-AND-COOKIES.md](product/LEGAL-AND-COOKIES.md) | Legal pages and cookie consent |
| [E2E-USE-CASES.md](product/E2E-USE-CASES.md) | Shell Playwright catalog (Guest / User / Admin) |

## Operations (`ops/`)

| Doc | Topic |
|-----|--------|
| [ENGINEERING-AUDIT.md](ops/ENGINEERING-AUDIT.md) | REQ-REV multi-dimension audit (security / N+1 / refactor / scale / architecture) |
| [FRANKENPHP-CODING.md](ops/FRANKENPHP-CODING.md) | Classic vs worker modes, ResetInterface |
| [FRANKENPHP-HOT-RELOAD.md](ops/FRANKENPHP-HOT-RELOAD.md) | Dev hot reload (Idiomorph + built-in Mercure) |
| [MAILPIT.md](ops/MAILPIT.md) | Local SMTP catcher for development |
| [SHARED-SERVER.md](ops/SHARED-SERVER.md) | Standalone vs shared MySQL/Redis Compose |

## Development (`dev/`)

| Doc | Topic |
|-----|--------|
| [DATABASE.md](dev/DATABASE.md) | Schema notes (see banner — rewrite pending) |
| [ADDING-LOCALES.md](dev/ADDING-LOCALES.md) | Enable a UI language |
| [NATIVE-MOBILE.md](dev/NATIVE-MOBILE.md) | PWA note (Hotwire Native removed) |
| [FUNDING.md](dev/FUNDING.md) | Sponsors / support |

## Also

- Feature specs: [`specs/`](../specs/) ([index](../specs/README.md))
- Security policy: [`SECURITY.md`](../SECURITY.md)
- Constitution: [`.specify/memory/constitution.md`](../.specify/memory/constitution.md)
- FrankenPHP coding (root copy): [FRANKENPHP-CODING.md](FRANKENPHP-CODING.md) — prefer `ops/` as canonical
