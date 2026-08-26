# Roles and access control

Nowo Shell uses **instance Symfony Security roles** plus an **instance RBAC** layer for Administration capabilities. Product apps may add their own tenancy / membership layers; those do **not** ship in this boilerplate.

| Layer | Where stored | Values | Purpose |
|-------|--------------|--------|---------|
| **Instance (Security)** | `user.roles` JSON + implicit `ROLE_USER` + assigned `role.code` (`InstanceRole`) | `ROLE_USER`, `ROLE_ADMIN`, custom `ROLE_*` | Login gate, Administration UI, kit admin dashboards |
| **Instance RBAC** | `permission` + `role` + `role_permission` + `role_user` (entity matrix) | Permission keys (`admin.users.manage`, …) bundled into roles | Granular Administration capabilities (UI ready; gradual `IsGranted` rollout) |

Shared conventions: workspace `OTHER_FULL_SPECS_DETAILS.md` **REQ-RBAC-004** (shared table names — this app = **Instance** scope).

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
  - `/admin` hub, Users, Groups, Roles, Permissions, Social login, Appearance, Mailer, Instance config.
  - Kit admin UIs (`access_roles: [ROLE_ADMIN]`): dashboard menus, breadcrumb kit, cookie consent, HttpLog, RoutingKit, SiteBackup panel.
  - Setup wizard after users already exist; platform-catalog redirect when catalogs are missing.
  - **All instance permission keys** via `InstancePermissionVoter` (superuser shortcut while RBAC is rolled out).

---

## Instance RBAC (permission keys)

- **Entities:** `InstancePermission` (capability key), `InstanceRole` (named bundle with Symfony `code`), many-to-many to users and permissions.
- **Constants:** `App\Identity\Security\Permission` (typed keys); catalog metadata in `InstancePermissionCatalog`.
- **Admin UI:** Administration → **Roles** (`/admin/roles`) and **Permissions** (`/admin/permissions`). Role detail uses path tabs: Overview `/admin/roles/{id}`, Permissions `/permissions`, Users `/users`.
- **Seed:** `app:seed-platform` upserts the built-in permission catalog (`InstanceRbacSeeder`).
- **Token merge:** Enabled assigned roles contribute their `code` through `User::getRoles()`.
- **Checks:** Prefer `#[IsGranted('admin.users.manage')]` (etc.) once a surface is migrated. **Until then, admin controllers stay on `#[IsGranted('ROLE_ADMIN')]`** (gradual rollout).
- **Voter:** `InstancePermissionVoter` grants known dotted keys when the user has `ROLE_ADMIN` or holds an enabled role that includes the permission. Unknown dotted keys are denied; Symfony `ROLE_*` / `IS_*` and non-dotted attributes abstain.
- **Translatable labels (REQ-RBAC-008):** Roles → `roles.catalog.<slug>.*`. Permissions → `permission_translation` rows (create/edit modal **locale tabs**, `DEFAULT_LOCALE` first; **not** JSON), then `permissions.catalog.<slug>.*`, then entity columns. Categories → `permissions.category.*` + `InstancePermissionCategoryCatalog` selector. Machine `key` / category slug are never translated.

Do not invent new Symfony `ROLE_*` values without either storing them on the user JSON (legacy `ROLE_ADMIN`) or creating an `InstanceRole` with that code. Update this doc when adding catalog permissions.

### Hierarchy

Symfony’s default role hierarchy is **not** customized in this repo. In practice:

- Checks for `ROLE_USER` succeed for any logged-in user (including admins), because `ROLE_USER` is always present on the user object.
- Checks for `ROLE_ADMIN` require that role to be stored on the account.
- Permission-key checks succeed for `ROLE_ADMIN` or matching RBAC grants.

---

## Related

- AuthKit host install: workspace `REQ-AUTH`
- Account privacy / GDPR: `specs/043-gdpr-user-export`
- Constitution Principle III (platform mission)
- `src/Identity/Security/InstancePermissionVoter.php`, `src/Identity/Security/Permission.php`
