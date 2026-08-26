# Playwright E2E

```bash
make up && make seed   # demo admin: admin@nowo-shell.local / admin123
make test-e2e
```

Shell catalog of use cases: [`docs/product/E2E-USE-CASES.md`](../docs/product/E2E-USE-CASES.md).

## Folder map

| Path | Role |
|------|------|
| `setup/` | Auth storage-state bootstrap (`auth.setup.ts` → `e2e/.auth/admin.json`) |
| `support/` | Shared helpers (login, cookie consent, page loader, tour) |
| `smoke/` | Guest / public surfaces: login, legal, health, cookie consent, misc shell |
| `account/` | Authenticated account area (preferences, security, privacy, display) |
| `admin/` | Admin hub, identity CRUD, settings, kits, navigation UI |

`e2e/` root keeps only this README (and gitignored `.auth/`). Specs live under the folders above.
