# Nowo Shell — product UI manual

Operator guide for the self-hosted Shell platform UI (Identity / Shared / Setup). Each chapter walks through the screens in product order: what the page is for, what you can do there, then a day-theme English screenshot at a fixed viewport (development overlays removed).

**Wiki index:** [symfony-frankenphp-boilerplate-v2/wiki](https://github.com/nowo-tech/symfony-frankenphp-boilerplate-v2/wiki) (chapter table linking here). Canonical wiki Home source: [`docs/wiki/Home.md`](../wiki/Home.md) — push with `make wiki-push-home`.

## How to read this manual

1. Start with [First-time setup](00-setup.md) only if you are installing a cold instance.
2. Otherwise begin at [Getting started](01-getting-started.md) (sign-in).
3. Use later chapters as a screen catalog: open the matching section when you need that part of the UI.
4. Companion shots named `*-2.webp` continue the **same** viewport (scroll position), not a different layout. The prose under each image explains what that continuation shows.

## Conventions

| Item | Value |
|------|--------|
| Viewport | **1440×900** fixed. Tall screens use same-size companions (`*-2.webp`), never a stretched full-page image |
| Theme | **Day** for the inventory. Day/night difference is shown once on public + private routes — see [Theme and language](06-appearance.md) |
| Language | **English**. How to switch languages is shown once on public + private routes — see [Theme and language](06-appearance.md) |
| Images | WebP (Chromium re-encode of the Playwright capture, `e2e/support/webp.ts`) under `images/`. Chapters may reference filenames before the first capture — run `make docs-manual-screenshots` to generate them |

## Contents

| Chapter | Topic |
|---------|--------|
| [00 — First-time setup](00-setup.md) | Cold install wizard |
| [01 — Getting started](01-getting-started.md) | Sign-in and recovery |
| [02 — Dashboard](02-dashboard.md) | Authenticated home |
| [03 — Account](03-account.md) | Profile, security, display, privacy |
| [04 — Administration](04-admin.md) | Users, RBAC, settings, kits |
| [05 — Legal and privacy](05-legal-and-privacy.md) | Legal pages and cookies |
| [06 — Theme and language](06-appearance.md) | How to switch day/night and locale |
| [07 — Branded HTTP errors](07-errors.md) | Public error pages, mascot, and maintenance preview |

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
