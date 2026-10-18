.PHONY: help up up-infra up-shared down down-infra down-shared build build-prod logs shell console seed seed-platform bootstrap ready classic worker restart mysql messenger-logs messenger-ping vite vite-hmr vite-build vite-watch pnpm mailpit mailpit-logs specify-check \
	cs cs-fix twig-cs twig-cs-fix phpstan rector rector-fix test test-coverage test-unit-js test-unit-js-coverage test-e2e test-e2e-isolated test-e2e-smoke test-e2e-mailpit test-e2e-worker-safe test-e2e-worker-safe-classic test-e2e-cold test-e2e-clean docs-manual-screenshots docs-manual-screenshots-setup wiki-push-home up-e2e down-e2e up-e2e-cold down-e2e-cold wipe-e2e-cold ensure-e2e-env ensure-e2e-db ensure-e2e-up ready-e2e ready-e2e-lite seed-e2e kit-smoke qa qa-fix secrets-scan composer-outdated update-deps \
	setup-hooks check-no-cursor-coauthor check-module-boundaries strip-cursor-coauthor-from-history ensure-halite-secrets print-urls deps wait-php bootstrap-shared-db assert-shared-hosts ensure-env

MAKEFLAGS += --no-print-directory

# App Compose (dev). Shared MySQL/Redis: compose.infra.yaml project `shared-infra`.
# REQ-ENV-003: operator working file is `.env.local` (Compose + Make).
ENV_FILE := .env.local
export COMPOSE_ENV_FILES := $(ENV_FILE)
DC := docker compose --env-file $(ENV_FILE)
DC_INFRA := docker compose -p shared-infra -f compose.infra.yaml --env-file $(ENV_FILE)
# Explicit -f disables auto-load of compose.override.yaml — include it so Mailpit stays defined.
DC_SHARED := $(DC) -f compose.yaml -f compose.override.yaml -f compose.shared.yaml

E2E_ENV_DIST := .env.e2e.dist
E2E_ENV_FILE ?= .env.e2e.local
# Extra compose files after compose.yaml + compose.override.yaml + compose.e2e.yaml
E2E_COMPOSE_EXTRA ?=
E2E_COMPOSE_PROJECT ?= nowo-shell-e2e
E2E_HTTP_PORT ?= 9087
E2E_HTTPS_PORT ?= 9461
E2E_MYSQL_DATABASE ?= nowo_shell_e2e
E2E_REDIS_DB ?= 3
E2E_VITE_PORT ?= 5179
E2E_FRANKENPHP_MODE ?= worker
E2E_FRANKENPHP_WORKER_NUM ?= 4
E2E_FRANKENPHP_RESET_KERNEL ?= false
E2E_MAILPIT_UI_PORT ?= 18027
PLAYWRIGHT_E2E_BASE_URL ?= https://localhost:$(E2E_HTTPS_PORT)
# Recursive `=` so recipe / sub-make overrides (cold ports, WORKER_NUM=1) rebind DC_E2E.
DC_E2E = HTTP_PORT=$(E2E_HTTP_PORT) HTTPS_PORT=$(E2E_HTTPS_PORT) HTTP3_PORT=$(E2E_HTTPS_PORT) \
	DEFAULT_URI=https://localhost:$(E2E_HTTPS_PORT) \
	MYSQL_DATABASE=$(E2E_MYSQL_DATABASE) \
	FRANKENPHP_MODE=$(E2E_FRANKENPHP_MODE) \
	FRANKENPHP_WORKER_NUM=$(E2E_FRANKENPHP_WORKER_NUM) \
	FRANKENPHP_RESET_KERNEL=$(E2E_FRANKENPHP_RESET_KERNEL) \
	REDIS_URL=redis://$${REDIS_HOST:-redis-8.10.2}:$${REDIS_PORT:-6379}/$(E2E_REDIS_DB) \
	MESSENGER_TRANSPORT_DSN=redis://$${REDIS_HOST:-redis-8.10.2}:$${REDIS_PORT:-6379}?dbindex=$(E2E_REDIS_DB) \
	COMPOSE_PROJECT_NAME=$(E2E_COMPOSE_PROJECT) COMPOSE_ENV_FILES=$(E2E_ENV_FILE) \
	docker compose -p $(E2E_COMPOSE_PROJECT) --env-file $(E2E_ENV_FILE) \
	-f compose.yaml -f compose.override.yaml -f compose.e2e.yaml $(E2E_COMPOSE_EXTRA)

# Cold-start E2E (empty schema → /setup → login).
E2E_COLD_HTTP_PORT ?= 9093
E2E_COLD_HTTPS_PORT ?= 9472
E2E_COLD_MYSQL_DATABASE ?= nowo_shell_e2e_cold
E2E_COLD_REDIS_DB ?= 13
E2E_COLD_ENV_FILE ?= .env.e2e.cold.local
E2E_COLD_COMPOSE_PROJECT ?= nowo-shell-e2e-cold
PLAYWRIGHT_E2E_COLD_BASE_URL ?= https://localhost:$(E2E_COLD_HTTPS_PORT)

ensure-env:
	@./.scripts/ensure-env-local.sh

help:
	@echo "symfony-frankenphp-boilerplate-v2 — FrankenPHP Symfony boilerplate (Symfony 8.1 + MySQL)"
	@echo ""
	@echo "  make up              Standalone: local MySQL+Redis + app (asserts MYSQL_HOST=database)"
	@echo "  make up-infra        Start shared MySQL + Redis (compose.infra.yaml); coexistence if already running — see docs/ops/SHARED-SERVER.md"
	@echo "  make up-shared       Shared: compose.shared + MYSQL_HOST=mysql-9.7.2-primary / REDIS_HOST=redis-8.10.2"
	@echo "  make down-shared     Stop app started with compose.shared (infra stays)"
	@echo "  make classic         FrankenPHP HTTP in classic mode"
	@echo "  make worker          FrankenPHP HTTP in worker mode"
	@echo "  make down            Stop app containers (shared infra stays for siblings)"
	@echo "  make down-infra      Stop shared infra (MySQL/Redis); warn if other apps use it"
	@echo "  make bootstrap-shared-db  Create schema/user on shared MySQL primary"
	@echo "  make build           Rebuild the php image (dev)"
	@echo "  make build-prod      Build frankenphp_prod image (see docs/PRODUCTION.md)"
	@echo "  make logs            Follow php service logs"
	@echo "  make deps            composer + pnpm install inside php (idempotent)"
	@echo "  make vite-hmr        Start Vite HMR (compose profile hmr)"
	@echo "  make vite            Follow Vite HMR logs"
	@echo "  make vite-build      Ensure deps, then pnpm run build → public/build/"
	@echo "  make vite-watch      pnpm run watch (vite build --watch, no HMR)"
	@echo "  make pnpm            pnpm in php container (ARGS='install' / 'add -D …')"
	@echo "  make mailpit         Start Mailpit (compose profile mail) for local SMTP; prints UI URL"
	@echo "  make mailpit-logs    Follow Mailpit logs"
	@echo "  make messenger-logs  Follow Messenger worker logs"
	@echo "  make mysql           mysql CLI shell"
	@echo "  make shell           Shell in the php container"
	@echo "  make console         bin/console (ARGS='...')"
	@echo "  make seed-platform   Upsert menus/breadcrumbs/cookie consent (safe after upgrades)"
	@echo "  make seed            Platform catalogs + demo admin (admin@nowo-shell.local / admin123)"
	@echo "  make bootstrap       Migrate DB + platform seed (after make up)"
	@echo "  make ready           bootstrap + seed (recommended first local run)"
	@echo "  make restart         Restart php + messenger"
	@echo "  make specify-check   Verify Specify CLI"
	@echo ""
	@echo "Quality:"
	@echo "  make cs              PHP-CS-Fixer (check)"
	@echo "  make cs-fix          PHP-CS-Fixer (fix)"
	@echo "  make twig-cs         Twig-CS-Fixer (lint)"
	@echo "  make twig-cs-fix     Twig-CS-Fixer (fix)"
	@echo "  make phpstan         PHPStan analyse"
	@echo "  make rector          Rector dry-run"
	@echo "  make rector-fix      Rector apply"
	@echo "  make test            PHPUnit"
	@echo "  make test-coverage   PHPUnit + Clover/HTML (var/coverage*); COVERAGE_MIN=$(COVERAGE_MIN) (override e.g. COVERAGE_MIN=0)"
	@echo "  make test-unit-js    Vitest unit tests for assets/"
	@echo "  make test-unit-js-coverage  Vitest + V8 coverage → var/coverage-js/"
	@echo "  make test-e2e        Playwright browser E2E (Docker image; needs make up + make seed)"
	@echo "  make test-e2e-isolated   Playwright vs isolated stack (nowo_shell_e2e / :$(E2E_HTTPS_PORT))"
	@echo "  make test-e2e-smoke  Warm smoke lane only (e2e/smoke/ on seeded smoke DB)"
	@echo "  make test-e2e-mailpit  Opt-in Mailpit delivery (password reset → UI :$(E2E_MAILPIT_UI_PORT))"
	@echo "  make test-e2e-worker-safe  FrankenPHP worker Kernel isolation (WORKER_NUM=1, RESET=false; e2e/worker)"
	@echo "  make test-e2e-worker-safe-classic  Same probe under FRANKENPHP_MODE=classic (contrast)"
	@echo "  make test-e2e-cold   Cold-start circuit (empty nowo_shell_e2e_cold / :$(E2E_COLD_HTTPS_PORT); wipe + /setup → login)"
	@echo "  make test-e2e-clean  Alias of test-e2e-cold (clean install lane)"
	@echo "                       Filter/shard (REQ-QA-006): ARGS='e2e/smoke' or ARGS='--shard=1/2'"
	@echo "  make wipe-e2e-cold / up-e2e-cold / down-e2e-cold  Disposable cold Compose stack"
	@echo "  make up-e2e / ready-e2e  Isolated Compose + migrate + seed for E2E"
	@echo "  make ready-e2e-lite  Alias of ready-e2e (enough for worker-safe in this app)"
	@echo "  make down-e2e        Stop isolated E2E stack"
	@echo "  make docs-manual-screenshots  Capture production-like PNGs into docs/manual/images (isolated E2E stack)"
	@echo "  make docs-manual-screenshots-setup  Setup wizard PNGs (cold stack; wipe + up first)"
	@echo "  make wiki-push-home  Push docs/wiki/Home.md to GitHub wiki (index → docs/manual links)"
	@echo "  make kit-smoke       AuthKit smoke (login, magic login, password reset, throttle)"
	@echo "  make secrets-scan    Gitleaks secret scan (same gate as CI)"
	@echo "  make qa              cs + twig-cs + phpstan + rector + check-module-boundaries + test"
	@echo "  make qa-fix          twig-cs-fix + phpstan + rector-fix (+ cs-fix) + test"
	@echo "  make update-deps     composer update + pnpm update (in php container)"
	@echo "  make composer-outdated  Suggest composer require pins (nowo-tech/composer-update-helper)"
	@echo ""
	@echo "Git hygiene:"
	@echo "  make setup-hooks                    Install .githooks (strips Cursor co-authors)"
	@echo "  make check-no-cursor-coauthor       Fail if Cursor trailers exist in history"
	@echo "  make check-module-boundaries         Fail if domain boundary rules are violated"
	@echo "  make strip-cursor-coauthor-from-history  Rewrite local history to remove them"

setup-hooks:
	@chmod +x .githooks/commit-msg .githooks/prepare-commit-msg .scripts/check-no-cursor-coauthor.sh .scripts/strip-cursor-coauthor-from-history.sh .scripts/check-module-boundaries.sh
	@git config core.hooksPath .githooks
	@echo "✅ Git hooks installed (.githooks — strips Cursor Co-authored-by / Made-with trailers)."

check-no-cursor-coauthor:
	@chmod +x .scripts/check-no-cursor-coauthor.sh
	@./.scripts/check-no-cursor-coauthor.sh HEAD

check-module-boundaries:
	@chmod +x .scripts/check-module-boundaries.sh
	@./.scripts/check-module-boundaries.sh


strip-cursor-coauthor-from-history:
	@chmod +x .scripts/strip-cursor-coauthor-from-history.sh
	@./.scripts/strip-cursor-coauthor-from-history.sh main

# Print published app ports (from running compose, else .env.local / defaults).
print-urls:
	@HTTP_PUB=$$($(DC) port php 80 2>/dev/null | head -1 | sed 's/.*://'); \
	HTTPS_PUB=$$($(DC) port php 443 2>/dev/null | head -1 | sed 's/.*://'); \
	if [ -z "$$HTTP_PUB" ]; then HTTP_PUB=$$(grep -E '^HTTP_PORT=' .env.local 2>/dev/null | cut -d= -f2-); fi; \
	if [ -z "$$HTTPS_PUB" ]; then HTTPS_PUB=$$(grep -E '^HTTPS_PORT=' .env.local 2>/dev/null | cut -d= -f2-); fi; \
	HTTP_PUB=$${HTTP_PUB:-9081}; \
	HTTPS_PUB=$${HTTPS_PUB:-9444}; \
	echo ""; \
	echo "Nowo Shell is up:"; \
	echo "  HTTP:  http://localhost:$${HTTP_PUB}"; \
	echo "  HTTPS: https://localhost:$${HTTPS_PUB}"; \
	MAILPIT_UI=$$($(DC) --profile mail port mailer 8025 2>/dev/null | head -1 | sed 's/.*://'); \
	if [ -n "$$MAILPIT_UI" ]; then \
		echo "  Mailpit UI: http://localhost:$${MAILPIT_UI}  (SMTP from PHP: smtp://mailer:1025)"; \
	fi

up:
	@$(MAKE) ensure-env
	@chmod +x .scripts/assert-shared-hosts.sh
	@./.scripts/assert-shared-hosts.sh require-standalone
	@$(MAKE) up-infra
	$(DC) up --build -d
	@$(MAKE) wait-php
	@$(MAKE) deps
	@echo "Building frontend assets (static public/build/)…"
	@$(MAKE) vite-build
	@$(MAKE) print-urls
	@echo "Optional local SMTP: make mailpit  (see docs/ops/MAILPIT.md)"
	@echo "Shared mode: set MYSQL_HOST=mysql-9.7.2-primary REDIS_HOST=redis-8.10.2 then make up-shared"

up-shared:
	@$(MAKE) ensure-env
	@chmod +x .scripts/assert-shared-hosts.sh
	@./.scripts/assert-shared-hosts.sh require-shared
	@$(MAKE) up-infra
	$(DC_SHARED) up --build -d
	@$(MAKE) wait-php
	@$(MAKE) deps
	@echo "Building frontend assets (static public/build/)…"
	@$(MAKE) vite-build
	@$(MAKE) print-urls
	@echo "Shared mode: app uses MYSQL_HOST/REDIS_HOST from .env.local; local database/redis services are profiled out (compose.shared.yaml)."

down-shared:
	-$(DC_SHARED) --profile hmr --profile mail down

assert-shared-hosts:
	@chmod +x .scripts/assert-shared-hosts.sh
	@./.scripts/assert-shared-hosts.sh check

# Shared MySQL + Redis (compose.infra.yaml, project shared-infra).
# Coexistence: if mysql-9.7.2-primary already exists (e.g. developer.local.server/server), skip create.
# Standalone local compose still starts its own `database` + `redis` services.
# MYSQL_TOPOLOGY=simple|replica (from .env.local or env) — replica adds profile mysql-replica.
up-infra:
	@$(MAKE) ensure-env
	@set -a; . ./.env.local; set +a; \
	TOPOLOGY="$${MYSQL_TOPOLOGY:-simple}"; \
	PROFILE_ARGS=""; \
	if [ "$$TOPOLOGY" = "replica" ]; then PROFILE_ARGS="--profile mysql-replica"; fi; \
	if docker inspect mysql-9.7.2-primary >/dev/null 2>&1; then \
		echo "Shared infra: mysql-9.7.2-primary already exists — not recreating (coexistence)."; \
		docker start mysql-9.7.2-primary >/dev/null 2>&1 || true; \
		docker start redis-8.10.2 >/dev/null 2>&1 || true; \
		if [ "$$TOPOLOGY" = "replica" ]; then \
			if ! docker inspect mysql-9.7.2-replica >/dev/null 2>&1; then \
				echo "MYSQL_TOPOLOGY=replica but mysql-9.7.2-replica is missing."; \
				echo "Start it from the same infra that owns the primary, or remove the primary and run make up-infra."; \
				exit 1; \
			fi; \
			docker start mysql-9.7.2-replica >/dev/null 2>&1 || true; \
		fi; \
		docker inspect redis-8.10.2 >/dev/null 2>&1 || { \
			echo "redis-8.10.2 is missing while MySQL exists. Start Redis from the same infra owner."; \
			exit 1; \
		}; \
		NET="$${SHARED_DOCKER_NETWORK:-server_network}"; \
		docker network inspect "$$NET" >/dev/null 2>&1 || { \
			echo "Docker network '$$NET' not found."; \
			exit 1; \
		}; \
	else \
		echo "Starting shared infra (MYSQL_TOPOLOGY=$$TOPOLOGY)…"; \
		$(DC_INFRA) $$PROFILE_ARGS up -d; \
	fi

down-infra:
	@echo "Stopping shared-infra (MySQL/Redis). Sibling projects on server_network may break."
	-$(DC_INFRA) --profile mysql-replica down

bootstrap-shared-db:
	@chmod +x .scripts/bootstrap-shared-db.sh
	@./.scripts/bootstrap-shared-db.sh

classic:
	@$(MAKE) ensure-env
	@chmod +x .scripts/assert-shared-hosts.sh
	@./.scripts/assert-shared-hosts.sh require-standalone
	@$(MAKE) up-infra
	FRANKENPHP_MODE=classic $(DC) up --build -d
	@$(MAKE) wait-php
	@$(MAKE) deps
	@$(MAKE) vite-build
	@$(MAKE) print-urls

worker:
	@$(MAKE) ensure-env
	@chmod +x .scripts/assert-shared-hosts.sh
	@./.scripts/assert-shared-hosts.sh require-standalone
	@$(MAKE) up-infra
	FRANKENPHP_MODE=worker $(DC) up --build -d
	@$(MAKE) wait-php
	@$(MAKE) deps
	@$(MAKE) vite-build
	@$(MAKE) print-urls

down:
	$(DC) --profile hmr --profile mail down
	@echo "App stack stopped. Shared infra (if any) still running — make down-infra to stop MySQL/Redis."

build:
	$(DC) build --no-cache

build-prod:
	docker build --target frankenphp_prod -t ${IMAGES_PREFIX:-}symfony-frankenphp-boilerplate-v2:prod .

logs:
	$(DC) logs -f php

# Wait until the php container accepts exec (entrypoint may still be installing).
wait-php:
	@echo "Waiting for php container…"
	@i=0; \
	until $(DC) exec -T php true >/dev/null 2>&1; do \
		i=$$((i+1)); \
		if [ $$i -ge 90 ]; then echo "php container not ready after 90s"; $(DC) ps; exit 1; fi; \
		sleep 1; \
	done

# Idempotent: fill vendor/ + node_modules/ when missing or incomplete.
deps:
	@$(MAKE) wait-php
	@if ! $(DC) exec -T php test -f vendor/autoload_runtime.php 2>/dev/null; then \
		echo "Installing PHP dependencies (composer)…"; \
		$(DC) exec -T php composer install --prefer-dist --no-progress --no-interaction; \
	fi
	@if ! $(DC) exec -T php test -x node_modules/.bin/vite 2>/dev/null; then \
		echo "Installing JS dependencies (pnpm)…"; \
		$(DC) exec -T -e CI= php pnpm install --no-frozen-lockfile; \
	fi

vite-hmr:
	$(DC) --profile hmr up -d vite
	@echo "Vite HMR is on — entrypoints use viteServer (browser shows a pending HMR WebSocket)."
	@echo "For stable UI without HMR: $(DC) --profile hmr stop vite && make vite-build"

vite:
	$(DC) logs -f vite

vite-build:
	@$(MAKE) deps
	$(DC) exec -T php pnpm run build

vite-watch:
	$(DC) exec php pnpm run watch

pnpm:
	$(DC) exec -T php pnpm $(ARGS)

# Local SMTP catcher (Mailpit). Dev only — not started by `make up`; not in compose.prod.yaml.
# Docs: docs/ops/MAILPIT.md — save smtp://mailer:1025 under Administration → Mailer, then Send sample.
mailpit:
	@$(MAKE) ensure-env
	$(DC) --profile mail up -d mailer
	@UI_PUB=$$($(DC) --profile mail port mailer 8025 2>/dev/null | head -1 | sed 's/.*://'); \
	UI_PUB=$${UI_PUB:-18025}; \
	echo ""; \
	echo "Mailpit is up (dev/test only — not used in production):"; \
	echo "  UI:   http://localhost:$${UI_PUB}"; \
	echo "  SMTP (from PHP container): smtp://mailer:1025"; \
	echo "  Save that DSN under Administration → Mailer, then use Send sample email."; \
	echo "  Docs: docs/ops/MAILPIT.md"

mailpit-logs:
	$(DC) --profile mail logs -f mailer

messenger-logs:
	$(DC) logs -f messenger

shell:
	$(DC) exec php sh

console:
	$(DC) exec php bin/console $(ARGS)

# Halite key file lives under var/secrets/; the encrypt bundle does not mkdir for you.
ensure-halite-secrets:
	$(DC) exec -T php mkdir -p var/secrets

bootstrap: migrate
	@$(MAKE) seed-platform
	@$(DC) exec -T php sh -c 'mkdir -p var/site-backup && touch var/site-backup/setup.done'
	@echo "Next: make seed (or make ready) for demo admin — or open /setup / register"

migrate: ensure-halite-secrets
	$(DC) exec -T php bin/console doctrine:migrations:migrate -n

seed-platform: ensure-halite-secrets
	$(DC) exec -T php bin/console app:seed-platform

seed: seed-platform
	$(DC) exec -T php bin/console app:seed-demo
	@echo "Demo admin: admin@nowo-shell.local / admin123"

ready: bootstrap seed
	@echo "Ready: platform catalogs + demo admin seeded"
	@echo "Ops panel: /_site_backup  |  Setup wizard: /setup | Maintenance: /admin/maintenance"

restart:
	$(DC) restart php messenger
	@$(MAKE) vite-build

mysql:
	$(DC) exec database mysql -u$${MYSQL_USER:-app} -p$${MYSQL_PASSWORD:-!ChangeMe!} $${MYSQL_DATABASE:-app}

specify-check:
	@command -v specify >/dev/null || { echo "Install Specify: uv tool install specify-cli --from git+https://github.com/github/spec-kit.git"; exit 1; }
	specify check
	specify integration status
	@echo "Constitution: .specify/memory/constitution.md"
	@echo "Cursor skills: .cursor/skills/speckit-*"
	@echo "Specs: specs/ (see specs/README.md)"

cs:
	$(DC) exec -T php vendor/bin/php-cs-fixer check --diff

cs-fix:
	$(DC) exec -T php vendor/bin/php-cs-fixer fix

twig-cs:
	$(DC) exec -T php vendor/bin/twig-cs-fixer lint --config=.twig-cs-fixer.dist.php

twig-cs-fix:
	$(DC) exec -T php vendor/bin/twig-cs-fixer fix --config=.twig-cs-fixer.dist.php

phpstan:
	$(DC) exec -T php bin/console cache:warmup --env=dev
	$(DC) exec -T php vendor/bin/phpstan analyse --memory-limit=512M -c phpstan.neon.dist

rector:
	$(DC) exec -T php vendor/bin/rector process --dry-run

# Always re-run CS Fixer after Rector: rules like blank_line_before_statement leave diffs
# that fail `php-cs-fixer check` in CI (qa-fix previously ran CS before Rector only).
rector-fix:
	$(DC) exec -T php vendor/bin/rector process
	@$(MAKE) cs-fix

test:
	$(DC) exec -T php sh -c 'rm -rf var/cache/test/* && vendor/bin/phpunit $(ARGS)'

test-unit-js:
	$(DC) exec -T php pnpm run test:unit $(ARGS)

test-unit-js-coverage:
	$(DC) exec -T php pnpm run test:unit:coverage $(ARGS)

# Browser E2E via official Playwright image (WSL-friendly Chromium deps).
# Override: PLAYWRIGHT_BASE_URL=https://localhost:9444 make test-e2e
# Filter:  make test-e2e ARGS='e2e/public.spec.ts'
# Host run (needs `pnpm exec playwright install-deps`): PLAYWRIGHT_ON_HOST=1 make test-e2e
# CI sets PLAYWRIGHT_REQUIRE_SAMPLE=1 so issue-dependent tests fail instead of skip.
PLAYWRIGHT_IMAGE ?= mcr.microsoft.com/playwright:v1.62.1-jammy
PLAYWRIGHT_BASE_URL ?= https://localhost:9444
test-e2e:
	@$(DC) exec -T php bin/console dbal:run-sql "DELETE FROM login_attempts" >/dev/null 2>&1 || true
ifeq ($(PLAYWRIGHT_ON_HOST),1)
	pnpm exec playwright test $(ARGS)
else
	docker run --rm --network=host \
		--user "$(shell id -u):$(shell id -g)" \
		-v "$(CURDIR):/work" -w /work \
		-e PLAYWRIGHT_BASE_URL="$(PLAYWRIGHT_BASE_URL)" \
		-e PLAYWRIGHT_REQUIRE_SAMPLE="$(PLAYWRIGHT_REQUIRE_SAMPLE)" \
		-e CI="$(CI)" \
		-e HOME=/tmp \
		-e XDG_CACHE_HOME=/tmp/.cache \
		$(PLAYWRIGHT_IMAGE) \
		bash -lc 'mkdir -p /tmp/.cache && ./node_modules/.bin/playwright test $(ARGS)'
endif

# --- Isolated Playwright E2E (nowo_shell_e2e; does not mutate dogfood MYSQL_DATABASE) ---
# Isolated: make up-e2e && make ready-e2e && make test-e2e-isolated
ensure-e2e-env: ensure-env
	@chmod +x .scripts/ensure-e2e-env.sh
	@E2E_COMPOSE_PROJECT="$(E2E_COMPOSE_PROJECT)" E2E_HTTP_PORT="$(E2E_HTTP_PORT)" E2E_HTTPS_PORT="$(E2E_HTTPS_PORT)" \
		E2E_MYSQL_DATABASE="$(E2E_MYSQL_DATABASE)" E2E_REDIS_DB="$(E2E_REDIS_DB)" E2E_VITE_PORT="$(E2E_VITE_PORT)" \
		E2E_FRANKENPHP_MODE="$(E2E_FRANKENPHP_MODE)" E2E_FRANKENPHP_WORKER_NUM="$(E2E_FRANKENPHP_WORKER_NUM)" E2E_FRANKENPHP_RESET_KERNEL="$(E2E_FRANKENPHP_RESET_KERNEL)" \
		E2E_ENV_DIST="$(E2E_ENV_DIST)" E2E_ENV_FILE="$(E2E_ENV_FILE)" ./.scripts/ensure-e2e-env.sh

ensure-e2e-db: up-infra ensure-e2e-env
	@set -a; . ./$(ENV_FILE); set +a; \
	DB="$(E2E_MYSQL_DATABASE)"; \
	echo "Ensuring MySQL schema \`$$DB\` on mysql-9.7.2-primary…"; \
	docker exec -i -e MYSQL_PWD="$$MYSQL_ROOT_PASSWORD" mysql-9.7.2-primary \
		mysql -uroot -e "CREATE DATABASE IF NOT EXISTS \`$$DB\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci; \
GRANT ALL PRIVILEGES ON \`$$DB\`.* TO '$${MYSQL_USER}'@'%'; FLUSH PRIVILEGES;"

up-e2e: ensure-e2e-db
	$(DC_E2E) up -d php messenger messenger-notify
	@echo "Isolated E2E stack: DB=$(E2E_MYSQL_DATABASE)  HTTPS=$(PLAYWRIGHT_E2E_BASE_URL)"
	@echo "Next: make ready-e2e   then   make test-e2e-isolated"

ensure-e2e-up: ensure-e2e-env
	@$(MAKE) up-infra
	@$(DC_E2E) exec -T php true >/dev/null 2>&1 || { \
		echo "E2E stack is down — starting…"; \
		$(MAKE) up-e2e; \
	}

down-e2e:
	@if [ -f "$(E2E_ENV_FILE)" ]; then \
		$(DC_E2E) down; \
	else \
		echo "No $(E2E_ENV_FILE) — nothing to stop (run make up-e2e first)."; \
	fi

seed-e2e: ensure-e2e-up
	@$(DC_E2E) exec -T php sh -c 'mkdir -p var/secrets && chmod 770 var/secrets'
	$(DC_E2E) exec -T php bin/console app:seed-platform
	$(DC_E2E) exec -T php bin/console app:seed-demo
	@printf 'PLAYWRIGHT_DEMO_EMAIL=admin@nowo-shell.local\nPLAYWRIGHT_DEMO_PASSWORD=admin123\n' > .demo-client.e2e.env
	@chmod 600 .demo-client.e2e.env 2>/dev/null || true
	@$(DC_E2E) exec -T php sh -c 'mkdir -p var/site-backup && rm -f var/site-backup/setup.required var/site-backup/setup-progress.json && touch var/site-backup/setup.done' 2>/dev/null || true

ready-e2e: ensure-e2e-up
	@$(DC_E2E) exec -T php sh -c 'mkdir -p var/secrets && chmod 770 var/secrets'
	$(DC_E2E) exec -T php bin/console doctrine:migrations:migrate -n
	$(DC_E2E) exec -T php bin/console messenger:setup-transports --no-interaction
	@$(MAKE) seed-e2e
	@echo "E2E DB ready. Run: make test-e2e-isolated"

ready-e2e-lite: ready-e2e
	@echo "E2E DB lite ready. Run: make test-e2e-worker-safe"

test-e2e-isolated: ensure-e2e-up
	@$(DC_E2E) exec -T php bin/console dbal:run-sql "DELETE FROM login_attempts" >/dev/null 2>&1 || true
ifeq ($(PLAYWRIGHT_ON_HOST),1)
	PLAYWRIGHT_ISOLATED=1 \
	PLAYWRIGHT_BASE_URL="$(PLAYWRIGHT_E2E_BASE_URL)" \
	PLAYWRIGHT_WORKERS="$(PLAYWRIGHT_WORKERS)" \
	PLAYWRIGHT_WORKER_SUITE="$(PLAYWRIGHT_WORKER_SUITE)" \
	PLAYWRIGHT_EXPECT_FRANKENPHP_MODE="$(PLAYWRIGHT_EXPECT_FRANKENPHP_MODE)" \
	PLAYWRIGHT_EXPECT_RESET_KERNEL="$(PLAYWRIGHT_EXPECT_RESET_KERNEL)" \
	PLAYWRIGHT_EXPECT_WORKER_NUM="$(PLAYWRIGHT_EXPECT_WORKER_NUM)" \
	pnpm exec playwright test $(ARGS)
else
	docker run --rm --network=host \
		--user "$(shell id -u):$(shell id -g)" \
		-v "$(CURDIR):/work" -w /work \
		-e PLAYWRIGHT_ISOLATED=1 \
		-e PLAYWRIGHT_BASE_URL="$(PLAYWRIGHT_E2E_BASE_URL)" \
		-e PLAYWRIGHT_WORKERS="$(PLAYWRIGHT_WORKERS)" \
		-e PLAYWRIGHT_WORKER_SUITE="$(PLAYWRIGHT_WORKER_SUITE)" \
		-e PLAYWRIGHT_EXPECT_FRANKENPHP_MODE="$(PLAYWRIGHT_EXPECT_FRANKENPHP_MODE)" \
		-e PLAYWRIGHT_EXPECT_RESET_KERNEL="$(PLAYWRIGHT_EXPECT_RESET_KERNEL)" \
		-e PLAYWRIGHT_EXPECT_WORKER_NUM="$(PLAYWRIGHT_EXPECT_WORKER_NUM)" \
		-e PLAYWRIGHT_REQUIRE_SAMPLE="$(PLAYWRIGHT_REQUIRE_SAMPLE)" \
		-e CI="$(CI)" \
		-e HOME=/tmp \
		-e XDG_CACHE_HOME=/tmp/.cache \
		$(PLAYWRIGHT_IMAGE) \
		bash -lc 'mkdir -p /tmp/.cache && ./node_modules/.bin/playwright test $(ARGS)'
endif

test-e2e-worker-safe: ensure-e2e-up
	@$(MAKE) ensure-e2e-env \
		E2E_FRANKENPHP_MODE=worker \
		E2E_FRANKENPHP_WORKER_NUM=1 \
		E2E_FRANKENPHP_RESET_KERNEL=false
	$(DC_E2E) up -d --force-recreate php
	@echo "Waiting for E2E worker /health/live…"
	@for i in 1 2 3 4 5 6 7 8 9 10 11 12 13 14 15; do \
		if curl -kfsS "$(PLAYWRIGHT_E2E_BASE_URL)/health/live" >/dev/null 2>&1; then break; fi; \
		sleep 2; \
	done
	$(MAKE) test-e2e-isolated \
		PLAYWRIGHT_WORKERS=1 \
		PLAYWRIGHT_WORKER_SUITE=1 \
		PLAYWRIGHT_EXPECT_FRANKENPHP_MODE=worker \
		PLAYWRIGHT_EXPECT_RESET_KERNEL=false \
		PLAYWRIGHT_EXPECT_WORKER_NUM=1 \
		ARGS='e2e/worker $(ARGS)'

test-e2e-worker-safe-classic: ensure-e2e-up
	@$(MAKE) ensure-e2e-env \
		E2E_FRANKENPHP_MODE=classic \
		E2E_FRANKENPHP_WORKER_NUM=1 \
		E2E_FRANKENPHP_RESET_KERNEL=false
	$(DC_E2E) up -d --force-recreate php
	@for i in 1 2 3 4 5 6 7 8 9 10 11 12 13 14 15; do \
		if curl -kfsS "$(PLAYWRIGHT_E2E_BASE_URL)/health/live" >/dev/null 2>&1; then break; fi; \
		sleep 2; \
	done
	$(MAKE) test-e2e-isolated \
		PLAYWRIGHT_WORKERS=1 \
		PLAYWRIGHT_WORKER_SUITE=1 \
		PLAYWRIGHT_EXPECT_FRANKENPHP_MODE=classic \
		PLAYWRIGHT_EXPECT_RESET_KERNEL=false \
		PLAYWRIGHT_EXPECT_WORKER_NUM= \
		ARGS='e2e/worker $(ARGS)'

# Warm smoke lane only (seeded smoke DB). Prereq: make up-e2e && make ready-e2e.
test-e2e-smoke: ensure-e2e-up
	$(MAKE) test-e2e-isolated ARGS='e2e/smoke $(ARGS)'

# Opt-in Mailpit SMTP trap on the warm E2E stack (MAILPIT-002 / AuthKit password reset).
test-e2e-mailpit: ensure-e2e-up
	@$(DC_E2E) --profile mail up -d mailer
	@echo "Waiting for Mailpit API on :$(E2E_MAILPIT_UI_PORT)…"
	@for i in 1 2 3 4 5 6 7 8 9 10 11 12 13 14 15; do \
		if curl -fsS "http://localhost:$(E2E_MAILPIT_UI_PORT)/api/v1/info" >/dev/null 2>&1; then \
			echo "Mailpit live"; \
			break; \
		fi; \
		if [ "$$i" -eq 15 ]; then echo "Mailpit failed to become live"; exit 1; fi; \
		sleep 1; \
	done
	@$(DC_E2E) exec -T php bin/console dbal:run-sql "DELETE FROM login_attempts" >/dev/null 2>&1 || true
ifeq ($(PLAYWRIGHT_ON_HOST),1)
	PLAYWRIGHT_MAILPIT=1 PLAYWRIGHT_ISOLATED=1 PLAYWRIGHT_WORKERS=1 \
	PLAYWRIGHT_BASE_URL="$(PLAYWRIGHT_E2E_BASE_URL)" \
	MAILPIT_UI_PORT="$(E2E_MAILPIT_UI_PORT)" \
	pnpm exec playwright test $(ARGS)
else
	docker run --rm --network=host \
		--user "$(shell id -u):$(shell id -g)" \
		-v "$(CURDIR):/work" -w /work \
		-e PLAYWRIGHT_MAILPIT=1 \
		-e PLAYWRIGHT_ISOLATED=1 \
		-e PLAYWRIGHT_WORKERS=1 \
		-e PLAYWRIGHT_BASE_URL="$(PLAYWRIGHT_E2E_BASE_URL)" \
		-e MAILPIT_UI_PORT="$(E2E_MAILPIT_UI_PORT)" \
		-e CI="$(CI)" \
		-e HOME=/tmp \
		-e XDG_CACHE_HOME=/tmp/.cache \
		$(PLAYWRIGHT_IMAGE) \
		bash -lc 'mkdir -p /tmp/.cache && ./node_modules/.bin/playwright test $(ARGS)'
endif

# Capture production-like PNGs for docs/manual/ (hides WDT / Twig Inspector). Needs seeded E2E stack.
# Fixed viewport 1440×900 (no fullPage stretch). Prereq: make up-e2e && make ready-e2e
docs-manual-screenshots: ensure-e2e-up
	@mkdir -p docs/manual/images
	@$(DC_E2E) exec -T php bin/console dbal:run-sql "DELETE FROM login_attempts" >/dev/null 2>&1 || true
ifeq ($(PLAYWRIGHT_ON_HOST),1)
	PLAYWRIGHT_MANUAL=1 PLAYWRIGHT_ISOLATED=1 PLAYWRIGHT_WORKERS=1 \
	PLAYWRIGHT_BASE_URL="$(PLAYWRIGHT_E2E_BASE_URL)" \
	pnpm exec playwright test e2e/manual/capture-screens.spec.ts --retries=0
else
	docker run --rm --network=host \
		--user "$(shell id -u):$(shell id -g)" \
		--shm-size=1g \
		-m 4g \
		-v "$(CURDIR):/work" -w /work \
		-e PLAYWRIGHT_MANUAL=1 \
		-e PLAYWRIGHT_ISOLATED=1 \
		-e PLAYWRIGHT_WORKERS=1 \
		-e PLAYWRIGHT_BASE_URL="$(PLAYWRIGHT_E2E_BASE_URL)" \
		-e HOME=/tmp \
		-e XDG_CACHE_HOME=/tmp/.cache \
		$(PLAYWRIGHT_IMAGE) \
		bash -lc 'mkdir -p /tmp/.cache docs/manual/images && ./node_modules/.bin/playwright test e2e/manual/capture-screens.spec.ts --retries=0'
endif
	@echo "Screenshots written to docs/manual/images/ — see docs/manual/README.md"

# Push docs/wiki/Home.md to the GitHub wiki Home (chapter index linking into docs/manual/).
# One-time: open https://github.com/nowo-tech/symfony-frankenphp-boilerplate-v2/wiki and Create the first page if clone fails.
wiki-push-home:
	@chmod +x .scripts/wiki-push-home.sh
	@sh .scripts/wiki-push-home.sh

# Setup wizard shots against disposable cold stack (never against warm nowo_shell_e2e / dogfood).
# Prereq: make wipe-e2e-cold && make up-e2e-cold
docs-manual-screenshots-setup:
	@mkdir -p docs/manual/images
	@curl -kfsS "$(PLAYWRIGHT_E2E_COLD_BASE_URL)/health/live" >/dev/null || { \
		echo "Cold stack not live at $(PLAYWRIGHT_E2E_COLD_BASE_URL) — run: make wipe-e2e-cold && make up-e2e-cold"; \
		exit 1; \
	}
	docker run --rm --network=host \
		--user "$(shell id -u):$(shell id -g)" \
		--shm-size=1g \
		-m 3g \
		-v "$(CURDIR):/work" -w /work \
		-e PLAYWRIGHT_MANUAL=1 \
		-e PLAYWRIGHT_COLD=1 \
		-e PLAYWRIGHT_WORKERS=1 \
		-e PLAYWRIGHT_BASE_URL="$(PLAYWRIGHT_E2E_COLD_BASE_URL)" \
		-e HOME=/tmp \
		-e XDG_CACHE_HOME=/tmp/.cache \
		$(PLAYWRIGHT_IMAGE) \
		bash -lc 'mkdir -p /tmp/.cache docs/manual/images && ./node_modules/.bin/playwright test e2e/manual/capture-setup.spec.ts --retries=0'
	@echo "Setup screenshots written to docs/manual/images/ — see docs/manual/00-setup.md"

# --- Cold-start E2E (empty schema → /setup → login) ---
# Never call ready-e2e / seed-e2e on this stack.
E2E_COLD_MAKE_VARS = \
	E2E_ENV_FILE=$(E2E_COLD_ENV_FILE) \
	E2E_HTTP_PORT=$(E2E_COLD_HTTP_PORT) \
	E2E_HTTPS_PORT=$(E2E_COLD_HTTPS_PORT) \
	E2E_MYSQL_DATABASE=$(E2E_COLD_MYSQL_DATABASE) \
	E2E_REDIS_DB=$(E2E_COLD_REDIS_DB) \
	E2E_COMPOSE_PROJECT=$(E2E_COLD_COMPOSE_PROJECT) \
	E2E_COMPOSE_EXTRA="-f compose.e2e.cold.yaml" \
	E2E_FRANKENPHP_MODE=classic \
	E2E_FRANKENPHP_WORKER_NUM=1 \
	E2E_FRANKENPHP_RESET_KERNEL=true

wipe-e2e-cold:
	@$(MAKE) ensure-e2e-env $(E2E_COLD_MAKE_VARS)
	@if [ -f "$(E2E_COLD_ENV_FILE)" ]; then \
		$(MAKE) down-e2e $(E2E_COLD_MAKE_VARS) || true; \
		HTTP_PORT=$(E2E_COLD_HTTP_PORT) HTTPS_PORT=$(E2E_COLD_HTTPS_PORT) HTTP3_PORT=$(E2E_COLD_HTTPS_PORT) \
		DEFAULT_URI=https://localhost:$(E2E_COLD_HTTPS_PORT) \
		MYSQL_DATABASE=$(E2E_COLD_MYSQL_DATABASE) \
		COMPOSE_PROJECT_NAME=$(E2E_COLD_COMPOSE_PROJECT) COMPOSE_ENV_FILES=$(E2E_COLD_ENV_FILE) \
		docker compose -p $(E2E_COLD_COMPOSE_PROJECT) --env-file $(E2E_COLD_ENV_FILE) \
			-f compose.yaml -f compose.override.yaml -f compose.e2e.yaml -f compose.e2e.cold.yaml \
			down -v || true; \
	fi
	@$(MAKE) up-infra
	@set -a; . ./$(ENV_FILE); set +a; \
	DB="$(E2E_COLD_MYSQL_DATABASE)"; \
	echo "Wiping MySQL schema \`$$DB\`…"; \
	docker exec -i -e MYSQL_PWD="$$MYSQL_ROOT_PASSWORD" mysql-9.7.2-primary \
		mysql -uroot -e "DROP DATABASE IF EXISTS \`$$DB\`; CREATE DATABASE \`$$DB\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci; \
GRANT ALL PRIVILEGES ON \`$$DB\`.* TO '$${MYSQL_USER}'@'%'; FLUSH PRIVILEGES;"
	@docker exec redis-8.10.2 redis-cli -n $(E2E_COLD_REDIS_DB) FLUSHDB >/dev/null 2>&1 || true
	@echo "Cold E2E wiped (DB=$(E2E_COLD_MYSQL_DATABASE)). Next: make up-e2e-cold"

up-e2e-cold:
	$(MAKE) up-e2e $(E2E_COLD_MAKE_VARS)
	@echo "Cold E2E stack: DB=$(E2E_COLD_MYSQL_DATABASE)  HTTPS=$(PLAYWRIGHT_E2E_COLD_BASE_URL)"
	@echo "Do NOT run ready-e2e on this stack. Next: make test-e2e-cold"

down-e2e-cold:
	$(MAKE) down-e2e $(E2E_COLD_MAKE_VARS)

test-e2e-cold:
	$(MAKE) wipe-e2e-cold
	$(MAKE) up-e2e-cold
	@echo "Waiting for cold E2E /health/live…"
	@for i in 1 2 3 4 5 6 7 8 9 10 11 12 13 14 15 16 17 18 19 20 21 22 23 24 25 26 27 28 29 30; do \
		if curl -kfsS "$(PLAYWRIGHT_E2E_COLD_BASE_URL)/health/live" >/dev/null 2>&1; then \
			echo "Cold stack live"; \
			break; \
		fi; \
		if [ "$$i" -eq 30 ]; then \
			echo "Cold stack failed to become live"; \
			$(MAKE) down-e2e $(E2E_COLD_MAKE_VARS) || true; \
			exit 1; \
		fi; \
		sleep 2; \
	done
ifeq ($(PLAYWRIGHT_ON_HOST),1)
	PLAYWRIGHT_COLD=1 \
	PLAYWRIGHT_BASE_URL="$(PLAYWRIGHT_E2E_COLD_BASE_URL)" \
	PLAYWRIGHT_WORKERS=1 \
	pnpm exec playwright test $(ARGS)
else
	docker run --rm --network=host \
		--user "$(shell id -u):$(shell id -g)" \
		-v "$(CURDIR):/work" -w /work \
		-e PLAYWRIGHT_COLD=1 \
		-e PLAYWRIGHT_BASE_URL="$(PLAYWRIGHT_E2E_COLD_BASE_URL)" \
		-e PLAYWRIGHT_WORKERS=1 \
		-e CI="$(CI)" \
		-e HOME=/tmp \
		-e XDG_CACHE_HOME=/tmp/.cache \
		$(PLAYWRIGHT_IMAGE) \
		bash -lc 'mkdir -p /tmp/.cache && ./node_modules/.bin/playwright test $(ARGS)'
endif

test-e2e-clean: test-e2e-cold

# Fast AuthKit / identity smoke after kit bumps (see docs/CONTRIBUTING.md).
kit-smoke:
	$(DC) exec -T php vendor/bin/phpunit \
		tests/Functional/Identity/AuthKitBootstrapTest.php \
		tests/Functional/Identity/MagicLoginTest.php \
		tests/Functional/Identity/PasswordResetTest.php \
		tests/Functional/Identity/LoginThrottleTest.php

# REQ-QA-002: hard gate COVERAGE_MIN=100 on includable src/ (Controllers/Demo excluded — docs/COVERAGE.md).
# Override: COVERAGE_MIN=0 make test-coverage (informational).
COVERAGE_MIN ?= 100
test-coverage:
	$(DC) exec -T php sh -c 'rm -rf var/cache/test/* && mkdir -p var/coverage var/coverage-html'
	$(DC) exec -T -e XDEBUG_MODE=coverage php vendor/bin/phpunit \
		--coverage-text \
		--coverage-clover var/coverage/clover.xml \
		--coverage-html var/coverage-html
	$(DC) exec -T -e COVERAGE_MIN="$(COVERAGE_MIN)" php sh .scripts/check-coverage-threshold.sh var/coverage/clover.xml

# Same gate as CI job "Secret scan (Gitleaks)". Requires Docker; pins the CLI version used in .github/workflows/ci.yml.
GITLEAKS_VERSION ?= 8.28.0
secrets-scan:
	docker run --rm -v "$(CURDIR):/repo:ro" -w /repo "zricethezav/gitleaks:v$(GITLEAKS_VERSION)" \
		detect --source . --verbose --redact --exit-code 1

qa: cs twig-cs phpstan rector check-module-boundaries test

# rector-fix re-applies CS Fixer so the tree matches CI `php-cs-fixer check`.
qa-fix: twig-cs-fix phpstan rector-fix test

# Update PHP (Composer) and frontend (pnpm) lockfiles within constraint ranges.
update-deps:
	$(DC) exec -T php composer update
	$(DC) exec -T php pnpm update
	@echo "Dependencies updated. Consider: make vite-build && make qa"

# Suggest pinned composer require commands for outdated direct deps (runs in php container).
# The helper may exit non-zero when outdated packages are found; still print suggestions.
composer-outdated:
	-$(DC) exec -T php bash ./generate-composer-require.sh
