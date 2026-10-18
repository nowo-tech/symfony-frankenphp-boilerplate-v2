# Production image (optional)

This repo’s default [`compose.yaml`](../compose.yaml) is a **local/dev** stack (`frankenphp_dev` + bind-mount `./:/app`).  
The Dockerfile also defines a **`frankenphp_prod`** target for baked, no-dev deployments.

## What “prod” means here

| Piece | Dev (`compose.yaml`) | Prod image |
|---|---|---|
| Docker target | `frankenphp_dev` | `frankenphp_prod` |
| Source | Bind-mount `./:/app` | Copied into the image at build |
| Composer | Live `vendor/` on host mount | `composer install --no-dev` in image |
| PHP / Caddy | Xdebug available, `--watch` | Production `php.ini`, no watch |
| Secrets | Local `.env` (gitignored) | Inject at **runtime** (env / orchestrator) |

FrankenPHP HTTP modes (`FRANKENPHP_MODE=classic|worker`, `LOOP_MAX`, `RESET_KERNEL`) work the same in prod — see [`FRANKENPHP-CODING.md`](ops/FRANKENPHP-CODING.md).

## Build

```bash
docker build --target frankenphp_prod -t symfony-frankenphp:prod .
```

CI already builds this target (`.github/workflows/ci.yml`).

`.dockerignore` excludes `.env*` so secrets from your laptop are **not** copied into the image. Runtime must supply at least:

- `APP_SECRET`
- `DATABASE_URL` (or Compose-equivalent MySQL vars)
- `MESSENGER_TRANSPORT_DSN` if you run async workers
- `SITE_SETUP_TOKEN` — unique secret for `/setup?token=…` (not the `.env.dist` local default)
- `SITE_BACKUP_PASSWORD_HASH` — bcrypt/argon hash for `/_site_backup` (not the `.env.dist` local default)
- Optional: `FRANKENPHP_MODE`, `FRANKENPHP_WORKER_NUM`, `FRANKENPHP_LOOP_MAX`, `FRANKENPHP_RESET_KERNEL`

`App\Setup\SiteBackupSecurityDefaultsGuard` **refuses HTTP (and most console) boots outside local `dev`/`test`** (including `prod`, `staging`, and any other `APP_ENV`) if `SITE_SETUP_TOKEN` is empty/local, `SITE_BACKUP_PASSWORD_HASH` is still the documented local hash, `APP_SECRET` is empty / still `ChangeMePleaseUseARealSecret` / shorter than 16 characters, or Redis has no AUTH (`REDIS_PASSWORD` empty and no `pass` in `REDIS_URL`). `compose.prod.yaml` also requires SiteBackup secrets via `${VAR:?…}`. Generate `APP_SECRET` with `openssl rand -hex 32` before exposing the instance.

**SiteBackup panel login throttle (1.17+):** 5 password attempts per client IP per 15 minutes on `/_site_backup`, then HTTP 429 + `Retry-After`. Behind a reverse proxy set `framework.trusted_proxies` (otherwise every client shares the proxy IP); with several app servers keep the limiter pool shared (Redis `cache.app` / `cache.rate_limiter`). After a restore that applied files the bundle writes `var/site-backup/worker-restart.required` — restart workers, then `bin/console nowo:site-backup:worker-restart --clear`.

Image builds (`frankenphp_prod` `composer post-install-cmd` → `cache:clear`) skip the guard for `cache:clear` / `cache:warmup` / `assets:install` only, so the Docker bake can warm caches without embedding runtime SiteBackup secrets. The first real HTTP request still fails closed until operators inject unique secrets.

The prod image runs `pnpm install --frozen-lockfile` and `pnpm run build` so `public/build/` is baked in (no Vite HMR container in production).

## Run (example)

Minimal one-off HTTP process (MySQL must be reachable via `DATABASE_URL` on the Compose/Docker network — DB ports are **not** published on the host):

```bash
docker run --rm -p 8080:80 -p 8443:443 \
  --network <compose-project>_default \
  -e APP_ENV=prod \
  -e APP_SECRET="$(openssl rand -hex 16)" \
  -e DATABASE_URL="mysql://app:CHANGE_ME@database:3306/app?serverVersion=9.7&charset=utf8mb4" \
  -e MESSENGER_TRANSPORT_DSN="doctrine://default?auto_setup=0" \
  -e FRANKENPHP_MODE=worker \
  symfony-frankenphp:prod
```

Optional Compose overlay for a full stack without bind-mounts:

```bash
docker compose -f compose.prod.yaml --env-file .env.local up --build -d
```

See [`compose.prod.yaml`](../compose.prod.yaml). Prefer a real secrets manager in production; do not reuse the `!ChangeMe!` placeholders from `.env.dist`.

## Field encryption key (Halite)

The platform encrypts **AuthKit OAuth client secrets + linked-account tokens** (AuthKit **≥ 1.15**) and **instance Mailer settings** (DSN, From) with [`nowo-tech/doctrine-encrypt-bundle`](https://packagist.org/packages/nowo-tech/doctrine-encrypt-bundle) (Halite).

After upgrading AuthKit to **1.15+**, encrypt any existing plaintext social rows:

```bash
docker compose exec php bin/console doctrine:encrypt:database --force
```

(Plaintext without the `<ENC>` marker still loads; the next flush or the command above writes ciphertext.)

| Approach | Notes |
|----------|--------|
| **Volume `php_secrets`** (default in [`compose.prod.yaml`](../compose.prod.yaml)) | Mounts `/app/var/secrets` so `.Halite.default.key` survives container recreates. Share the same volume with the `messenger` service. |
| **`APP_ENCRYPT_KEY`** | Optional: switch the encrypt profile to `secret_key_env_var` (see `config/packages/nowo_doctrine_encrypt.yaml` comments) and inject the key from your secret manager. |

Without a durable key, recreating the PHP container generates a new Halite key and **breaks decryption** of existing ciphertext.

Generate a key once (then back it up with other secrets):

```bash
docker compose -f compose.prod.yaml exec php bin/console doctrine:encrypt:generate-secret-key
```

Admin changes to the instance Mailer DSN / From are recorded as `UserAction` `instance.mailer_updated` with **redacted** `scheme` and `host` only (never the DSN secret). Plaintext DSN input must use an allowlisted Mailer scheme (`smtp` / `smtps` and common provider schemes). **`sendmail` / `native` are rejected** — Symfony’s sendmail transport accepts a free-form `?command=` that would allow host process execution from the admin UI.

**Mailer in production:** configure a real SMTP or provider DSN under **Administration → Mailer**. The local **Mailpit** catcher (`make mailpit`, Compose profile `mail` in `compose.override.yaml`) is for development only and is **absent** from [`compose.prod.yaml`](../compose.prod.yaml) — see [MAILPIT.md](ops/MAILPIT.md).

## Messenger in production

Keep the **HTTP** container separate from Messenger consumers (same as local Compose). Scale `messenger` independently; do not confuse it with `FRANKENPHP_MODE=worker`.

```bash
docker compose up -d --scale messenger=2
```

Monitor queue depth via `GET /health/ready` → `checks.messenger_async_pending` (Doctrine transport).

**Failed transport:** messages that exhaust retries land in the Doctrine `failed` queue (`queue_name=failed`). Treat the failed table as sensitive, restrict DB access, and purge periodically:

```bash
docker compose exec php bin/console messenger:failed:show
# After review / replay:
docker compose exec php bin/console messenger:failed:remove --all   # or remove by id
```

## Health probes

| Path | Auth | Purpose |
|------|------|---------|
| `GET /health/live` | Public | Process is up |
| `GET /health/ready` | Public | Database reachable + async queue depth |

Use `/health/live` for liveness and `/health/ready` for readiness in Kubernetes/Compose healthchecks.

Schedule HTTP audit-log retention (HttpLogBundle, default 30 days):

```bash
php bin/console nowo:http-log:purge
# or
make console ARGS='nowo:http-log:purge'
```

## Security headers (Caddy)

The FrankenPHP `Caddyfile` sets baseline headers on the HTTPS site: `X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy`, `Permissions-Policy`, and **HSTS** (skipped for `localhost` / `127.0.0.1` so local self-signed HTTPS is not sticky-pinned). **`Content-Security-Policy`** is set in PHP (`ContentSecurityPolicySubscriber`) with `object-src 'none'` and **`script-src 'self'`** (no `'unsafe-inline'`), so the Web Debug Toolbar can merge its script/style **nonces** into the same header. In **`kernel.debug`**, CSP also allows `'unsafe-eval'` because the toolbar `eval()`s scripts from the `/_wdt` AJAX fragment (prod stays without `'unsafe-eval'` except Swagger UI). Theme boot uses blocking IIFE from `assets/theme-boot.ts` (`public/build/theme-boot.js`); kit admin uses Vite `kit-admin` (self-hosted Bootstrap); confirm dialogs / password toggle / selects use Stimulus. Vendor kit page `window.*Config` scripts are rewritten to JSON islands (`KitInlineConfigScriptSubscriber`). `style-src` still allows `'unsafe-inline'` for operator appearance CSS overrides and small kit layout `<style>` blocks.

- **HSTS:** default `max-age=31536000; includeSubDomains` on non-loopback hosts. Override or extend via `CADDY_SERVER_EXTRA_DIRECTIVES` if you terminate TLS elsewhere (or need `preload`).
- Do not ship analytics cookies without cookie-consent kit UX.
- **Swagger UI** (`/admin/api/doc`) still needs `script-src 'unsafe-eval'` (JSON Schema compile). The PHP CSP subscriber uses a path-specific policy (no `'unsafe-inline'`); Swagger assets are same-origin (`nelmio_api_doc.html_config.assets_mode: bundle`) and boot via Vite `swagger-ui-boot`.

`/admin/api/doc` and `/admin/api/doc.json` require **`ROLE_ADMIN`**.

## Login throttling

`nowo-tech/login-throttle-bundle` + Symfony `login_throttling` on the `main` firewall (default: 5 attempts / 15 minutes). **Default storage is `database`** so counters are shared across FrankenPHP workers and multi-pod deployments (`login_attempts` table). Tune `config/packages/nowo_login_throttle.yaml` and keep `security.yaml` in sync (`nowo:login-throttle:configure-security --force`).

## Backups

Minimum operator checklist:

1. **MySQL**: scheduled `mysqldump` (or volume snapshots) of the Compose `database` data directory / managed DB.
2. **Secrets**: backup `.env` / secret manager entries (`APP_SECRET`, DB passwords, webhook URLs) **separately** from SiteBackup archives — SiteBackup `include_paths` intentionally **omits `.env`**.
3. **Encrypt key**: include `var/secrets/.Halite.default.key` (or `APP_ENCRYPT_KEY`) with secret backups — see [Field encryption key](#field-encryption-key-halite).
4. **After restore**: run `doctrine:migrations:migrate`, restart `php` + `messenger`, confirm `/health/ready`.

## Operational inventory (deploy checklist)

Use this list before exposing an instance beyond a trusted network. Details live in the sections above.

| Area | Must configure / verify | Notes |
|------|-------------------------|--------|
| **SiteBackup / setup** | Unique `SITE_SETUP_TOKEN`, unique `SITE_BACKUP_PASSWORD_HASH` | Fail-closed outside `dev`/`test` if local defaults remain |
| **App secrets** | `APP_SECRET`, DB credentials, Halite key volume or `APP_ENCRYPT_KEY` | Losing the encrypt key breaks ciphertext |
| **Messenger** | Separate `messenger:consume`; watch `/health/ready` queue depth; purge `messenger:failed` periodically | Do not conflate with `FRANKENPHP_MODE=worker` |
| **HTTP audit log** | Admin → HTTP log (`/admin/http-log`); schedule `nowo:http-log:purge` | Default retention 30 days; may store IPs / user ids |
| **Health** | `/health/live` + `/health/ready` for probes | Ready exposes queue depth (operational signal) |
| **Trusted proxies** | If TLS terminates **in front of** Caddy/FrankenPHP, set Symfony `trusted_proxies` / `SYMFONY_TRUSTED_PROXIES` so client IP (login throttle, audit) is not spoofable | Default Compose terminates TLS in Caddy — only needed for an outer proxy |
| **Twig `\|raw`** | Appearance CSS overrides, breadcrumb HTML, kit JSON islands, Swagger boot JSON, `json_encode` in `onsubmit` | Controlled sources only; do not `|raw` user/event payload HTML |
| **Mailer / Mercure** | Real DSN/hub in Admin; never ship Mailpit in prod Compose | Encrypted at rest via Halite |
| **Legal / cookies** | Privacy/terms/cookies pages; consent kit for non-essential cookies | Required for public operator UX |

## Out of scope (intentionally)

This boilerplate does **not** ship Kubernetes manifests, TLS termination in front of Caddy, or a managed database. Use the prod image as the application unit inside your own platform.
