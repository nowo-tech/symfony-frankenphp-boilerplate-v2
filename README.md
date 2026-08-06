# Nowo Shell

[![CI](https://github.com/nowo-tech/symfony-frankenphp-boilerplate-v2/actions/workflows/ci.yml/badge.svg)](https://github.com/nowo-tech/symfony-frankenphp-boilerplate-v2/actions/workflows/ci.yml)
[![GitHub release](https://img.shields.io/github/v/release/nowo-tech/symfony-frankenphp-boilerplate-v2.svg?style=flat)](https://github.com/nowo-tech/symfony-frankenphp-boilerplate-v2/releases)
[![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)
[![PHP](https://img.shields.io/badge/PHP-8.5%2B-777BB4?logo=php)](https://php.net)
[![Symfony](https://img.shields.io/badge/Symfony-8.1-000000?logo=symfony)](https://symfony.com)

`nowo-tech/symfony-frankenphp-boilerplate-v2` is a Symfony 8.1 and FrankenPHP starter for self-hosted applications.

It includes AuthKit login, password reset and social login; users and groups; account preferences; configurable appearance; localized legal and cookie-consent pages; menu and breadcrumb administration; HTTP logging; site backup/setup; and Nowo UI, form, audit, mailer, routing, PWA, and security kits.

Built on **Symfony 8.1**, **PHP 8.5**, **FrankenPHP** (classic/worker), **MySQL 9.7**, **Messenger**, **AuthKit**, **Vite + TypeScript + SCSS + Tailwind 4**, and **Spec-Driven Development** via [GitHub Spec Kit](https://github.com/github/spec-kit).

Docs: [INSTALL](docs/INSTALL.md) · [UPGRADING](docs/UPGRADING.md) · [CHANGELOG](docs/CHANGELOG.md) · [ARCHITECTURE](docs/ARCHITECTURE.md)

## Start locally

Requirements: Docker and Docker Compose.

```bash
cp .env.dist .env
make up
make bootstrap
```

Open the HTTPS URL printed by `make up`. Register the first user, then configure mail and appearance from Administration.

Useful commands:

```bash
make down
make logs
make shell
make test
make vite-build
```

The default Compose stack uses FrankenPHP in classic mode. Set `FRANKENPHP_MODE=worker` in `.env` to exercise worker mode.

## Development

Do not commit `.env`, encryption keys, database files, or backups. Run migrations before seeding platform catalogs:

```bash
make bootstrap
make seed-platform
```

This repository is a boilerplate, not an error-tracking, telemetry, Mercure, or API-server product.

Optional host tools for Spec Kit:

- [uv](https://docs.astral.sh/uv/) + `specify-cli` (`uv tool install specify-cli --from git+https://github.com/github/spec-kit.git`)
- Cursor (skills under `.cursor/skills/speckit-*`)

```bash
make specify-check
```

## Spec-Driven Development (Spec Kit)

Canonical flow (Cursor skills / slash commands):

1. `/speckit-constitution` — project principles (`.specify/memory/constitution.md`)
2. `/speckit-specify` — what & why → `specs/NNN-feature/spec.md`
3. `/speckit-clarify` — (optional) disambiguate
4. `/speckit-plan` — how / architecture → `plan.md`
5. `/speckit-tasks` — tasks → `tasks.md`
6. `/speckit-analyze` / `/speckit-checklist` — (optional) quality
7. `/speckit-implement` — implement
8. `/speckit-converge` — close gaps

Artifacts: [`specs/`](specs/) ([index](specs/README.md)). Constitution: [`.specify/memory/constitution.md`](.specify/memory/constitution.md).

Re-scaffold / upgrade Spec Kit in this repo:

```bash
specify integration upgrade --force --script sh
# or full re-init (review diffs; restore constitution if overwritten):
# specify init --here --force --integration cursor-agent --script sh --ignore-agent-tools
```
