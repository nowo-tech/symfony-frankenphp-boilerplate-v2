# Shared infra (in-repo MySQL + Redis)

Nowo Shell (symfony-frankenphp-boilerplate-v2) supports **two** local topologies:

1. **Standalone (default)** — `compose.yaml` embeds `database` (MySQL) + `redis` (`REDIS_HOST=redis`). Ports: HTTP **9081**, HTTPS **9444**, Mailpit UI **18025**.
2. **Shared** — data services live in [`compose.infra.yaml`](../../compose.infra.yaml) (Compose project `shared-infra`) on a fixed Docker network so several `repositories/other` apps can reuse one MySQL and one Redis — the same hostname contract as `developer.local.server/server` and little-vps (`server_internal`).

`make up` / `classic` / `worker` assert **standalone** hosts (`MYSQL_HOST=database`, `REDIS_HOST=redis`), then call `make up-infra` (coexistence: start shared MySQL/Redis if missing for siblings) and start the **local** `database` + `redis` services.

`make up-shared` asserts **shared** hosts (`MYSQL_HOST=mysql-9.7.2-primary`, `REDIS_HOST=redis-8.10.2`), starts infra, and brings the app up with [`compose.shared.yaml`](../../compose.shared.yaml) so local `database` / `redis` stay profiled out. Mixed host pairs fail fast via [`.scripts/assert-shared-hosts.sh`](../../.scripts/assert-shared-hosts.sh).

## Topology switch (`MYSQL_TOPOLOGY`)

| Value | Infra services | Recommended hosts |
|-------|----------------|--------------------|
| `simple` (default) | `mysql-9.7.2-primary` + `redis-8.10.2` | `MYSQL_HOST` / `MYSQL_HOST_RO` → `mysql-9.7.2-primary` |
| `replica` | primary + Redis + `mysql-9.7.2-replica` (Compose profile `mysql-replica`) | `MYSQL_HOST_RO=mysql-9.7.2-replica` |

Set in `.env.local` (see `.env.dist`):

```env
MYSQL_TOPOLOGY=simple
# or: MYSQL_TOPOLOGY=replica
```

Primary is always GTID-ready and creates a `replicator` user on first empty datadir, so enabling `replica` later does not require recreating the primary data directory.

## Quick start (this repo owns infra)

```bash
cp .env.dist .env.local   # once — standalone defaults: MYSQL_HOST=database, REDIS_HOST=redis
make up             # standalone app stack (asserts host pair)
# Shared mode:
#   set MYSQL_HOST=mysql-9.7.2-primary REDIS_HOST=redis-8.10.2 in .env
make up-shared      # up-infra + compose.shared (no local DB/Redis containers)
make bootstrap-shared-db   # optional: CREATE DATABASE (+ app user) on shared primary
make bootstrap             # migrate + platform seed
make down           # stops app only; shared MySQL/Redis stay up for siblings
make down-shared    # stops app started via compose.shared
make down-infra     # stops shared-infra (warn: other apps may break)
```

Published app ports (standalone defaults):

| Service | Host |
|---------|------|
| HTTP | http://localhost:9081 |
| HTTPS | https://localhost:9444 |
| Mailpit UI | http://localhost:18025 (`make mailpit`) |

## Coexistence with `developer.local.server/server`

Same container names and network:

| Role | Hostname |
|------|----------|
| Network | `${SHARED_DOCKER_NETWORK:-server_network}` |
| Write MySQL | `mysql-9.7.2-primary` |
| Read MySQL | `mysql-9.7.2-replica` (when topology/replica is up) |
| Redis | `redis-8.10.2` |

`make up-infra` **does not recreate** containers if `mysql-9.7.2-primary` already exists (e.g. started from `server/`). It starts them if stopped and requires `redis-8.10.2` + the Docker network. Do **not** run both owners’ compose files against empty names at once — pick one owner for the containers.

Alternative (infra only from `server/`):

```bash
cd /path/to/developer.local.server/server
docker compose up -d redis-8.10.2 mysql-9.7.2-primary mysql-9.7.2-replica
# then in symfony-frankenphp-boilerplate-v2:
make up
```

On little-vps set `SHARED_DOCKER_NETWORK=server_internal` (infra already present; `up-infra` is a coexistence no-op). Prefer a dedicated app user there instead of root.

## Configure `.env`

`DATABASE_URL` / `DATABASE_URL_RO` always use the same template:

```env
DATABASE_URL="mysql://${MYSQL_USER}:${MYSQL_PASSWORD}@${MYSQL_HOST}:${MYSQL_PORT}/${MYSQL_DATABASE}?serverVersion=${MYSQL_VERSION}&charset=utf8mb4"
DATABASE_URL_RO="mysql://${MYSQL_USER}:${MYSQL_PASSWORD}@${MYSQL_HOST_RO}:${MYSQL_PORT_RO}/${MYSQL_DATABASE}?serverVersion=${MYSQL_VERSION}&charset=utf8mb4"
```

Redis (sessions + `cache.app`):

```env
REDIS_PASSWORD=
REDIS_URL=redis://${REDIS_HOST}:${REDIS_PORT}
# When server/.env (or this compose.infra) sets REDIS_PASSWORD:
# REDIS_URL=redis://:${REDIS_PASSWORD}@${REDIS_HOST}:${REDIS_PORT}
```

`REDIS_PASSWORD` is the **shared Redis** secret (REQ-INFRA-SHARED-003). Empty = no AUTH. Do not interpolate an empty password into the URL.

Defaults from `.env.dist` / `.env.local` (standalone):

```env
SHARED_DOCKER_NETWORK=server_network
MYSQL_TOPOLOGY=simple
MYSQL_DATABASE=app
MYSQL_USER=app
MYSQL_PASSWORD=!ChangeMe!
MYSQL_ROOT_PASSWORD=!ChangeMeRoot!
MYSQL_HOST=database
MYSQL_HOST_RO=database
REDIS_HOST=redis
```

Shared-mode overrides (comments in `.env.dist`):

```env
MYSQL_HOST=mysql-9.7.2-primary
MYSQL_HOST_RO=mysql-9.7.2-primary
REDIS_HOST=redis-8.10.2
```

Optional schema/user helper: `make bootstrap-shared-db` (CREATE DATABASE; optional non-root user). Cold start can also use SiteBackup `/setup`.

**Messenger:** this app still defaults to `doctrine://default` for async transport. Redis Messenger is optional (`MESSENGER_TRANSPORT_DSN=redis://…`); drain `messenger_messages` before switching.

## Compose layout

| File | Role |
|------|------|
| `compose.infra.yaml` | Project `shared-infra`: network + MySQL (+ optional replica) + Redis; data under `./.data/infra/` |
| `compose.yaml` | Dev app + local `database` + `redis` + messenger (+ Mailpit / HMR profiles) |
| `compose.shared.yaml` | Profiles out local `database`/`redis`; attaches app to `${SHARED_DOCKER_NETWORK}` |

No host-published MySQL/Redis ports (REQ-FP-006): use `make mysql` or `docker compose exec database mysql …` / `docker exec -it mysql-9.7.2-primary mysql …`.

## Join from another project

Sibling apps only need the external network + hostnames:

```yaml
networks:
  shared:
    name: ${SHARED_DOCKER_NETWORK:-server_network}
    external: true
```

Start infra once:

```bash
make -C /path/to/symfony-frankenphp-boilerplate-v2 up-infra
# or use developer.local.server/server / symfony-beacon
```

## What Redis is used for

| Concern | Adapter |
|---------|---------|
| HTTP sessions | `framework.session.handler_id` = `REDIS_URL` |
| `cache.app` | `cache.adapter.redis` |

`prefix_seed: nowo-shell` isolates keys when several apps share one Redis.

## SiteBackup notes

- Dump host follows `MYSQL_*` / `DATABASE_URL` (standalone `database` or shared primary).
- YAML enables `durable_done`, `cold_start`, and `progress_storage: cache_doctrine` (SiteBackup ≥ 1.13).
- Durable done is wired via `InstanceSettingsDurableSetupDoneStore` (`config/services/site_backup.yaml`).

## Contract (local ↔ VPS)

| Role | Local (this repo or `server/`) | little-vps |
|------|--------------------------------|------------|
| Network | `server_network` | `server_internal` |
| Write MySQL | `mysql-9.7.2-primary` (`MYSQL_HOST`) or standalone `database` | Ansible primary hostname |
| Read MySQL | `mysql-9.7.2-replica` (`MYSQL_HOST_RO`) when replica topology is on | Ansible replica hostname |
| Redis | `redis-8.10.2` or standalone `redis` (`REDIS_HOST`) | Redis role container |

Doctrine reads are not routed to the replica yet; apps write to the primary. `DATABASE_URL_RO` is documented for a future adapter.
