# Roles and access control

Nowo Shell uses **instance Symfony Security roles** only. Product apps may add their own tenancy / membership layers; those do **not** ship in this boilerplate.

| Layer | Where stored | Values | Purpose |
|-------|--------------|--------|---------|
| **Instance (Security)** | `user.roles` JSON + implicit `ROLE_USER` | `ROLE_USER`, `ROLE_ADMIN` | Login gate, Administration UI, kit admin dashboards |

Configured in `config/packages/security.yaml`. Controllers use `#[IsGranted(...)]`; kits use `access_roles`.

---

## `ROLE_USER`

- **Meaning:** Any signed-in Nowo Shell account.
- **How it is granted:** Implicit. `User::getRoles()` always appends `ROLE_USER` even when `user.roles` is empty.
- **What it unlocks:**
  - Default `access_control` for authenticated app paths (`^/` → `ROLE_USER` after public exceptions).
  - Authenticated home (`dashboard_home`), account preferences (`/account/*`).
- **What it does *not* unlock:** Administration hub, Appearance / Mailer settings, kit CRUD (menus, breadcrumbs, cookie consent admin), user/group admin.

---

## `ROLE_ADMIN`

- **Meaning:** Instance operator (administration of the deployment).
- **How it is granted:**
  - First registration (`nowo_auth_kit.registration_mode: first_user_only` → `registration_role: ROLE_ADMIN`).
  - Setup / SiteBackup admin provisioning.
  - `app:seed-demo` demo user (`make seed` / `make ready`).
  - Administration → Users → change role (cannot demote the last admin or yourself).
- **What it unlocks:**
  - `/admin` hub, Users, Groups, Social login, Appearance, Mailer, Instance config.
  - Kit admin UIs (`access_roles: [ROLE_ADMIN]`): dashboard menus, breadcrumb kit, cookie consent, HttpLog, RoutingKit, SiteBackup panel.
  - Setup wizard after users already exist; platform-catalog redirect when catalogs are missing.

---

## Related

- AuthKit host install: workspace `REQ-AUTH`
- Account privacy / GDPR: `specs/043-gdpr-user-export`
- Constitution Principle III (platform mission)
