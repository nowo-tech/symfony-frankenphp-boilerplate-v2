# E2E use cases (Nowo Shell)

Shell catalog for Playwright coverage in `symfony-frankenphp-boilerplate-v2`. This is **platform** behaviour (Identity / Shared / Setup) — not Beacon product domains (projects, issues, Mercure product UI, …).

Actors:

| Actor | Meaning |
|-------|---------|
| **Guest** | No session (`storageState` empty) |
| **User** | Authenticated non-admin (covered where the demo admin session is enough to open account routes) |
| **Admin** | Demo admin from `e2e/setup/auth.setup.ts` (`e2e/.auth/admin.json`) |

Status legend: **Covered** = exercised by a listed spec; **Partial** = smoke only / best-effort; **Gap** = no dedicated assertion yet.

Folder layout: see [`e2e/README.md`](../../e2e/README.md).

---

## Auth

| Use case | Actor | Status | Spec |
|----------|-------|--------|------|
| Root redirects toward login | Guest | Covered | `e2e/smoke/public.spec.ts` |
| Login form usable | Guest | Covered | `e2e/smoke/public.spec.ts` |
| Localized login (`en`/`es`/`de`/`fr`) | Guest | Covered | `e2e/smoke/public.spec.ts` |
| Magic login + QR login pages load | Guest | Covered | `e2e/smoke/public.spec.ts` |
| Password reset page loads | Guest | Covered | `e2e/smoke/public.spec.ts` |
| Invalid credentials stay on login | Guest | Covered | `e2e/smoke/public.spec.ts` |
| Demo admin authenticate → storage state | Admin | Covered | `e2e/setup/auth.setup.ts` |
| Dashboard after auth | Admin | Covered | `e2e/smoke/misc.spec.ts`, `e2e/admin/navigation-ui.spec.ts` |
| Logout via user menu | Admin | Gap | — (helper `logout` exists in `e2e/support/helpers.ts`) |

---

## Legal / cookies

| Use case | Actor | Status | Spec |
|----------|-------|--------|------|
| Legal pages (`notice`/`privacy`/`terms`/`cookies`) localized + bare | Guest | Covered | `e2e/smoke/public.spec.ts` |
| Consent modal accept-all | Guest | Covered | `e2e/smoke/cookie-consent.spec.ts` |
| Consent persists across navigation | Guest | Covered | `e2e/smoke/cookie-consent.spec.ts` |
| Cookie consent config endpoint | Guest | Covered | `e2e/smoke/cookie-consent.spec.ts` |
| Localized consent config | Guest | Covered | `e2e/smoke/cookie-consent.spec.ts` |

---

## Ops / health / MM

| Use case | Actor | Status | Spec |
|----------|-------|--------|------|
| `/health/live` public 200 | Guest | Covered | `e2e/smoke/public.spec.ts`, `e2e/smoke/misc.spec.ts` |
| `/health/ready` responds (&lt;500) | Guest | Covered | `e2e/smoke/public.spec.ts` |
| Messenger / MM admin UI | Admin | Gap | — (platform may expose kit/admin panels only) |

---

## Account

| Use case | Actor | Status | Spec |
|----------|-------|--------|------|
| Account area pages load (profile, prefs, groups, security, display, privacy) | User/Admin | Covered | `e2e/account/account.spec.ts` |
| Privacy export download reachable | User/Admin | Covered | `e2e/account/account.spec.ts` |
| Account area nav marker | User/Admin | Partial | `e2e/account/account.spec.ts` |
| Profile overview identity meta | User/Admin | Covered | `e2e/account/account-deep.spec.ts` |
| Account nav section switch | User/Admin | Covered | `e2e/account/account-deep.spec.ts` |
| Security / history / activity panels | User/Admin | Covered | `e2e/account/account-deep.spec.ts` |
| Groups list | User/Admin | Covered | `e2e/account/account-deep.spec.ts` |
| Privacy export + anonymize panels | User/Admin | Covered | `e2e/account/account-deep.spec.ts` |
| Display preference forms | User/Admin | Covered | `e2e/account/account-deep.spec.ts` |
| Authenticated locale switch | User/Admin | Covered | `e2e/account/account-deep.spec.ts` |

---

## Admin hub / identity / RBAC / settings / kits

| Use case | Actor | Status | Spec |
|----------|-------|--------|------|
| Admin hub loads | Admin | Covered | `e2e/admin/admin.spec.ts` |
| Hub deep cards navigate (instance config, HTTP log, cookie consent) | Admin | Covered | `e2e/admin/settings-deep.spec.ts` |
| Users / groups / social-login admin pages | Admin | Covered | `e2e/admin/admin.spec.ts` |
| First user activity link when present | Admin | Partial | `e2e/admin/admin.spec.ts` |
| Settings: mailer, appearance, instance-config | Admin | Covered | `e2e/admin/admin.spec.ts` |
| Appearance tabs + color subtabs | Admin | Covered | `e2e/admin/settings-deep.spec.ts` |
| Instance config export/import panels + download | Admin | Covered | `e2e/admin/settings-deep.spec.ts` |
| Kit admin: HTTP log, menus, routing, breadcrumb, cookie consent | Admin | Covered | `e2e/admin/admin.spec.ts` |
| Dashboard ↔ account ↔ admin navigation | Admin | Covered | `e2e/admin/navigation-ui.spec.ts` |
| Instance RBAC role/permission CRUD UI | Admin | Gap | — (see [`ROLES.md`](ROLES.md); no dedicated e2e yet) |

---

## How to run

```bash
make up && make seed
make test-e2e
```

Credentials default to `admin@nowo-shell.local` / `admin123` (override via `PLAYWRIGHT_*` or `e2e/.demo-client.env.cache`).
