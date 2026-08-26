# FrankenPHP hot reload (local development)

Local Docker uses FrankenPHP's built-in **hot reload**: when you change PHP, Twig, config, or assets, the browser updates without a manual refresh.

Official docs: [frankenphp.dev/docs/hot-reload](https://frankenphp.dev/docs/hot-reload/). Client: [`nowo-tech/hot-reload-bundle`](https://packagist.org/packages/nowo-tech/hot-reload-bundle) (**dev only** — do **not** wire host `frankenphp-hot-reload` / `idiomorph` npm packages).

## What is wired

| Piece | Role |
|-------|------|
| `.docker/frankenphp/Caddyfile` | `php_server { hot_reload { watch … } }` + FrankenPHP built-in Mercure (dev only) |
| `FRANKENPHP_WORKER_CONFIG=watch` | With `make worker`, restarts PHP workers when watched files change |
| `nowo-tech/hot-reload-bundle` | Auto-injects hot-reload client (`data-nowo-hot-reload`); WDT preserve |
| `config/packages/nowo_hot_reload.yaml` | CSP nonce attribute `_shell_csp_nonce` (aligned with host CSP subscriber) |
| `.docker/frankenphp/Caddyfile.prod` | **No** hot reload (production) |

## How to use

1. Start the stack (`make up` / `make classic` / `make worker`).
2. Run `make vite-build` after changing TypeScript/SCSS (or use the optional Vite HMR profile).
3. Edit a Twig/PHP file under the watched paths — the open tab should morph/reload.

Watched paths (see Caddyfile): `src/`, `config/`, `templates/`, `translations/`, `assets/`, `.env`, `composer.json`.

## How it works

FrankenPHP's `hot_reload` directive publishes a Mercure update when watched files change. **Nowo HotReloadBundle** injects the browser client (Idiomorph + EventSource) and preserves Web Debug Toolbar / Twig Inspector nodes across morphs.

## Disable temporarily

Comment out the `hot_reload { … }` block in `.docker/frankenphp/Caddyfile` and recreate the `php` container:

```bash
make restart
```

Or unset `FRANKENPHP_HOT_RELOAD` in the container environment — the bundle skips injection when the env var is absent.

## Security

Hot reload and anonymous Mercure must **never** ship in production images or prod Caddyfiles. `NowoHotReloadBundle` is registered only for `dev` / `test` in `config/bundles.php`.
