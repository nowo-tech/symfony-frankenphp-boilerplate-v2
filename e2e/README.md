# Playwright E2E

## Isolated DB (recommended for local dogfooding)

`make test-e2e` hits the **same** MySQL schema as `make up` (`MYSQL_DATABASE`) and mutates data there.

To keep development data clean, use a **second Compose project** on schema `nowo_shell_e2e` (Redis DB index `3`, HTTPS `:9461` by default). Dogfood on `:9444` can stay up in parallel.

Versioned template: `.env.e2e.dist` (`make ensure-e2e-env` writes `.env.e2e.local`). Do **not** commit `.env.e2e.local`.

```bash
make up              # dogfood stack stays up (:9444)
make up-e2e          # create nowo_shell_e2e + start nowo-shell-e2e (php/messenger*)
make ready-e2e       # migrate + seed on E2E stack
make test-e2e-isolated
make test-e2e-smoke  # warm smoke lane only (e2e/smoke/)
# optional: make down-e2e   # stop E2E containers; schema nowo_shell_e2e is kept
```

Playwright runs **multiple workers** against this smoke DB (`fullyParallel: true`, default 4 local / 2 CI). Force serial for debugging: `PLAYWRIGHT_WORKERS=1 make test-e2e-isolated`.

### Cold-start circuit (empty DB → `/setup` → login)

Disposable stack for **UC-SETUP-07 / UC-SETUP-01 / UC-AUTH-10** (wizard admin). Uses Compose project `nowo-shell-e2e-cold`, schema `nowo_shell_e2e_cold`, HTTPS `:9472`, HTTP `:9093`, Redis DB `13`, dedicated `var/site-backup` volume, and **`FRANKENPHP_MODE=classic`** (wizard migrate/seed is long-running; classic avoids worker request-timer edge cases). **Never** run `ready-e2e` here.

```bash
make wipe-e2e-cold    # drop schema + remove cold volumes
make up-e2e-cold      # start empty stack (migrate happens inside the wizard)
make test-e2e-cold    # wipe + up + Playwright project `cold` (workers=1)
make test-e2e-clean   # alias of test-e2e-cold (clean install lane)
# optional: make down-e2e-cold
```

Warm product specs `testIgnore` `e2e/cold/`. Do **not** POST `/setup/api/advance` against seeded `nowo_shell_e2e` / dogfood. Dual lanes: **smoke** (`make test-e2e-smoke`) vs **clean** (`make test-e2e-clean`).

Auth state: `e2e/.auth/admin.e2e.json` (does not overwrite dogfood `admin.json`). Credentials: `.demo-client.e2e.env` (does not overwrite `.demo-client.env`).

FrankenPHP on the isolated E2E stack defaults to **`FRANKENPHP_MODE=worker`**, **`FRANKENPHP_WORKER_NUM=4`**, **`FRANKENPHP_RESET_KERNEL=false`**. Use `make test-e2e-worker-safe` to force `WORKER_NUM=1` and run `e2e/worker/` against a shared Kernel, or `make test-e2e-worker-safe-classic` for the classic contrast. `/health/live` exposes a non-secret `runtime` block for those assertions.

### QA-012 HTML/Twig markup smoke

`e2e/smoke/html-twig-standardization.spec.ts` asserts document shell, CSP nonces on inline `<style>`/`<script>` (raw response HTML), and non-bare-text bodies on public paths. Included in the warm smoke lane (`make test-e2e-smoke`).

### Opt-in Mailpit lane (MAILPIT-002)

Password-reset delivery is **not** in the default warm gate. After `make up-e2e && make ready-e2e`:

```bash
make test-e2e-mailpit   # starts Mailpit on :18027, runs e2e/mailpit/
```

See [`docs/ops/MAILPIT.md`](../docs/ops/MAILPIT.md).

## Dogfood stack

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
| `smoke/` | Guest / public surfaces: login, legal, health, cookie consent, HTML/Twig markup (QA-012), misc shell |
| `mailpit/` | Opt-in SMTP delivery (`make test-e2e-mailpit`; Mailpit UI `:18027` on isolated E2E) |
| `cold/` | Cold-start setup circuit (`make test-e2e-cold`; empty `nowo_shell_e2e_cold`) |
| `account/` | Authenticated account area (preferences, security, privacy, display) |
| `admin/` | Admin hub, identity CRUD, settings, kits, navigation UI |
| `security/` | Negative access control: guest redirects, auth gates, CSRF smoke (`e2e/security/`) |
| `worker/` | FrankenPHP shared-Kernel isolation (`make test-e2e-worker-safe`; ignored by warm chromium) |
| `manual/` | Docs screenshot crawl (`make docs-manual-screenshots`; ignored by warm chromium) |
| `cold/` | Cold-start setup circuit (`make test-e2e-cold`; ignored by warm chromium) |
| `mailpit/` | Opt-in SMTP delivery (`make test-e2e-mailpit`; ignored by warm chromium) |

Warm `chromium` `testIgnore`s `e2e/manual/`, `e2e/worker/`, `e2e/cold/`, and `e2e/mailpit/` (dedicated Make targets). **`e2e/security/` is part of the warm product suite.**

### Filters / sharding (REQ-QA-006)

```bash
make test-e2e ARGS='e2e/security'
make test-e2e-isolated ARGS='e2e/smoke/public.spec.ts'
make test-e2e-isolated ARGS='--shard=1/2'   # local split when debugging long runs
PLAYWRIGHT_WORKERS=1 make test-e2e-isolated
```

CI runs a single Playwright job (suite is small). Use `--shard=N/M` locally via `make test-e2e-isolated ARGS='--shard=1/2'` when needed.

`e2e/` root keeps only this README (and gitignored `.auth/`). Specs live under the folders above.
