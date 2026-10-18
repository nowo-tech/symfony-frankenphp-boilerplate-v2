# Nowo Shell — product UI manual

Operator guide for the self-hosted Shell platform UI. Each chapter explains what a screen is for, what it contributes to day-to-day operation, then shows the screenshot.

**Source of truth:** [`docs/manual/`](https://github.com/nowo-tech/symfony-frankenphp-boilerplate-v2/tree/main/docs/manual) in this repository (versioned with `main`, regenerated via Playwright). This wiki **Home** is an index only — chapter content and screenshots live in the tree.

## Chapters

| Chapter | Topic |
|---------|--------|
| [00 — First-time setup](https://github.com/nowo-tech/symfony-frankenphp-boilerplate-v2/blob/main/docs/manual/00-setup.md) | Cold install wizard |
| [01 — Getting started](https://github.com/nowo-tech/symfony-frankenphp-boilerplate-v2/blob/main/docs/manual/01-getting-started.md) | Sign-in and recovery |
| [02 — Dashboard](https://github.com/nowo-tech/symfony-frankenphp-boilerplate-v2/blob/main/docs/manual/02-dashboard.md) | Authenticated home |
| [03 — Account](https://github.com/nowo-tech/symfony-frankenphp-boilerplate-v2/blob/main/docs/manual/03-account.md) | Profile, security, display, privacy |
| [04 — Administration](https://github.com/nowo-tech/symfony-frankenphp-boilerplate-v2/blob/main/docs/manual/04-admin.md) | Users, RBAC, settings, kits |
| [05 — Legal and privacy](https://github.com/nowo-tech/symfony-frankenphp-boilerplate-v2/blob/main/docs/manual/05-legal-and-privacy.md) | Legal pages and cookies |
| [06 — Theme and language](https://github.com/nowo-tech/symfony-frankenphp-boilerplate-v2/blob/main/docs/manual/06-appearance.md) | Day/night and locale |
| [07 — Branded HTTP errors](https://github.com/nowo-tech/symfony-frankenphp-boilerplate-v2/blob/main/docs/manual/07-errors.md) | Public error pages, mascot, and maintenance preview |

Full index and conventions: [docs/manual/README.md](https://github.com/nowo-tech/symfony-frankenphp-boilerplate-v2/blob/main/docs/manual/README.md).

## Conventions (short)

| Item | Value |
|------|--------|
| Viewport | **1440×900** fixed (tall screens use `*-2.webp` companions) |
| Theme / language | **Day** + **English** for the inventory; preference demos once public + private — see chapter 06 |
| Error previews | `/_error/{code}` in `APP_ENV=dev`; maintenance preview at `/_maintenance_preview` |
| Prose | Each screen: purpose → what it contributes → screenshot (no bare image stacks) |

## Regenerating screenshots

```bash
make up-e2e && make ready-e2e
make docs-manual-screenshots

make wipe-e2e-cold && make up-e2e-cold
make docs-manual-screenshots-setup
```

## Local demo credentials

- Email: `admin@nowo-shell.local`
- Password: `admin123`

Do not reuse on a public instance.

## Sync this wiki Home

```bash
make wiki-push-home
```

Canonical file: [`docs/wiki/Home.md`](https://github.com/nowo-tech/symfony-frankenphp-boilerplate-v2/blob/main/docs/wiki/Home.md).
