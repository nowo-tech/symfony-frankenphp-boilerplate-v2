# FrankenPHP hot reload (local development)

Local Docker uses FrankenPHP's built-in **hot reload**: when you change PHP, Twig, config, or assets, the browser updates without a manual refresh.

This is **dev-only**. The **server** side is Caddy `php_server { hot_reload }` plus the built-in Mercure hub. The **client** comes from [`nowo-tech/hot-reload-bundle`](https://github.com/nowo-tech/HotReloadBundle) (`auto_inject: true`, `client_mode: shared_worker`).

Official server docs: [frankenphp.dev/docs/hot-reload](https://frankenphp.dev/docs/hot-reload/). Bundle: [CONFIGURATION.md](https://github.com/nowo-tech/HotReloadBundle/blob/main/docs/CONFIGURATION.md) / [USAGE.md](https://github.com/nowo-tech/HotReloadBundle/blob/main/docs/USAGE.md).

This is independent of Vite HMR: Vite still owns frontend module HMR; FrankenPHP hot reload covers PHP/Twig (and rebuilt assets) via DOM morph.

## What is wired

| Piece | Role |
|-------|------|
| `nowo-tech/hot-reload-bundle` (`require-dev`, **≥1.5**) | Injects client + diagnostics; multi-tab modes |
| `config/packages/nowo_hot_reload.yaml` | `when@dev`; `client_mode: shared_worker`; preserves WDT + Twig Inspector |
| `/_nowo/hot-reload/client.js` + `shared-worker.js` | Bundle-served same-origin client (one Mercure SSE for all tabs) |
| `.docker/frankenphp/Caddyfile` | `php_server { hot_reload { watch … } }` + Mercure (dev only) |
| `FRANKENPHP_WORKER_CONFIG=watch` | With `make worker`, restarts PHP workers when watched files change |
| `.docker/frankenphp/Caddyfile.prod` | **No** hot reload |

There is **no** host Vite deferred client. Do **not** add `frankenphp-hot-reload` / `idiomorph` npm packages or a `hot-reload-deferred` Vite entry.

## Multi-tab modes

See the Web Debug Toolbar **Hot Reload** panel → **Multi-tab client modes (help)** for a comparison of `cdn` / `visibility` / `shared_worker` / `always` and HTTP/2 infra.

This project uses **`shared_worker`** so several admin tabs stay connected without exhausting HTTP/1.1 slots.

## How to use

1. Start the stack (`make up` / `make classic` / `make worker`).
2. Run `make vite-build` after changing TypeScript/SCSS (or use the optional Vite HMR profile).
3. Edit a Twig/PHP file under the watched paths — open tabs should morph/reload.
4. Validate: `docker compose exec php bin/console nowo:hot-reload:check`.

Watched paths (see Caddyfile): `src/`, `config/`, `templates/`, `translations/`, `assets/`, `.env`, `composer.json`.

## How it works

FrankenPHP's `hot_reload` directive publishes a Mercure update when watched files change. The **bundle** (`auto_inject: true`, `client_mode: shared_worker`) injects a same-origin SharedWorker so one Mercure SSE is shared by all tabs, preserving Web Debug Toolbar / Twig Inspector nodes across morphs.

## Disable temporarily

Comment out the `hot_reload { … }` block in `.docker/frankenphp/Caddyfile` and recreate the `php` container:

```bash
make restart
```

Or unset `FRANKENPHP_HOT_RELOAD` in the container environment — the bundle skips injection when the env var is absent.

Do **not** enable hot reload in `Caddyfile.prod`.

## Security

Hot reload and anonymous Mercure must **never** ship in production images or prod Caddyfiles. `NowoHotReloadBundle` is registered only for `dev` / `test` in `config/bundles.php`.
