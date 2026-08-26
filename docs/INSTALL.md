# Install

```bash
cp .env.dist .env.local
make up
make bootstrap   # migrate + app:seed-platform (menus, breadcrumbs, cookie consent)
make seed        # demo admin admin@nowo-shell.local / admin123
# or: make ready  # bootstrap + seed
```

Open the HTTPS URL from Compose (`HTTPS_PORT`, default **9444**).

## Seed layers

| Command | Role |
|---------|------|
| `app:seed-platform` / `make seed-platform` | Idempotent catalogs (menus, breadcrumbs, cookie consent) |
| `app:seed-demo` / `make seed` | Demo admin only (no product sample telemetry) |

There is **no** `app:seed-sample` in this boilerplate.

## First-run SiteBackup

When catalogs/schema need bootstrap, the app may redirect to `/setup` (SiteBackup). Profiles run migrations, `app:seed-platform`, and optional admin user creation — see `config/packages/nowo_site_backup.yaml` and `specs/056-setup-wizard`.

See the root `README.md` for day-to-day Make targets and Spec Kit.
