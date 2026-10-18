# Administration

Requires **`ROLE_ADMIN`**. The admin area combines Shell identity CRUD, instance RBAC, settings, and nowo-tech kit panels (HTTP log, menus, routing, breadcrumbs, cookie consent, maintenance).

Open **Administration** from the operator navigation (hub cards mirror the sidebar groups).

## Hub

**Route:** `/admin`

**What it is.** Card index of admin modules grouped as Overview, Access, Instance, and Navigation & legal.

**What it contributes.** A single map of operator tools so you do not need to memorize routes. Each card opens a dedicated configuration surface described below.

![Administration hub](images/admin.webp)

## Identity and access

### Users

**Route:** `/admin/users`

**What it is.** Directory of accounts on this instance (roles, status, search).

**What it contributes.** Create and manage who can sign in when self-registration is closed or insufficient.

![Users](images/admin-users.webp)

#### New user

**Route:** `/admin/users/new`

**What it is.** Create-user form (identity, roles, initial credentials as configured).

**What it contributes.** Provisions accounts for teammates and service operators under instance policy.

![New user](images/admin-users-new.webp)

#### User activity

**Route:** `/admin/users/{id}/activity`

**What it is.** Activity view for a selected user.

**What it contributes.** Operator visibility into last activity and related signals.

![User activity](images/admin-user-activity.webp)

### Groups {#groups}

**Route:** `/admin/groups`

**What it is.** Groups used to grant access to many users at once in product apps built on Shell.

**What it contributes.** Scales membership without editing every user individually.

![Groups](images/admin-groups.webp)

![New group](images/admin-groups-new.webp)

### Instance roles

**Route:** `/admin/roles`

**What it is.** Custom instance roles beyond Symfony’s built-in `ROLE_USER` / `ROLE_ADMIN`.

**What it contributes.** Fine-grained RBAC when product apps need permission matrices.

![Roles](images/admin-roles.webp)

![New role](images/admin-roles-new.webp)

### Permissions

**Route:** `/admin/permissions`

**What it is.** Permission catalog attached to instance roles.

**What it contributes.** Declares what each role may do before assigning roles to users.

![Permissions](images/admin-permissions.webp)

![New permission](images/admin-permissions-new.webp)

### Social login

**Route:** `/admin/social-login`

**What it is.** OAuth / OIDC provider configuration for AuthKit social sign-in.

**What it contributes.** Enables enterprise IdP or social providers without custom auth code.

![Social login providers](images/admin-social-login.webp)

![New social login provider](images/admin-social-login-new.webp)

## Instance settings

### Mailer {#mailer}

**Route:** `/settings/mailer`

**What it is.** SMTP transport configuration and test send for AuthKit mail (reset, magic link, notifications).

**What it contributes.** Required for password reset and magic login in non-local environments. Local dev often uses Mailpit — see [`docs/ops/MAILPIT.md`](../ops/MAILPIT.md).

![Mailer settings](images/settings-mailer.webp)

### Appearance

**Route:** `/settings/appearance`

**What it is.** Instance branding: themes, brand name, layout, and colour tokens.

**What it contributes.** Controls guest and authenticated chrome colours separate from per-user theme preference ([Theme and language](06-appearance.md)).

![Appearance settings](images/settings-appearance.webp)

### Instance config

**Route:** `/settings/instance-config`

**What it is.** Export / import of instance configuration snapshots.

**What it contributes.** Backup and migration of operator-tuned settings between environments.

![Instance config](images/settings-instance-config.webp)

## Kits and ops

### HTTP log

**Route:** `/admin/http-log`

**What it is.** Request log viewer from `nowo-tech/http-log-bundle`.

**What it contributes.** Debug and audit HTTP traffic without shell access to Caddy logs.

![HTTP log](images/admin-http-log.webp)

### Menus

**Route:** `/admin/menus/`

**What it is.** Dashboard menu editor from `nowo-tech/dashboard-menu-bundle`.

**What it contributes.** Adjust sidebar navigation labels, order, and visibility for operators.

![Menu admin](images/admin-menus.webp)

### Routing kit

**Route:** `/admin/_routing/`

**What it is.** RoutingKit admin for named routes and redirects.

**What it contributes.** Manage friendly URLs and route metadata without editing YAML by hand.

![Routing kit](images/admin-routing.webp)

### Breadcrumb kit

**Route:** `/breadcrumb-kit-admin/collections`

**What it is.** BreadcrumbKit collection editor.

**What it contributes.** Keeps breadcrumb trails consistent across Identity and Shared modules.

![Breadcrumb kit](images/admin-breadcrumbs.webp)

### Cookie consent

**Route:** `/admin/cookie-consent`

**What it is.** Cookie consent category and copy configuration.

**What it contributes.** Pairs with the guest consent modal and [Legal → Cookie policy](05-legal-and-privacy.md#cookie-policy).

![Cookie consent admin](images/admin-cookie-consent.webp)

### Maintenance mode

**Route:** `/admin/maintenance`

**What it is.** Maintenance mode panel (`nowo-tech/maintenance-mode-bundle`) for controlled downtime.

**What it contributes.** Shows a maintenance page to guests while admins can still sign in and preview.

![Maintenance mode](images/admin-maintenance.webp)

## Next steps

- [Account](03-account.md) — end-user preferences and privacy  
- [Legal and privacy](05-legal-and-privacy.md) — public legal copy linked from AuthKit footers  
