#!/bin/sh
set -e

# =============================================================================
# FrankenPHP mode switch (classic ↔ worker)
# =============================================================================
# Inputs (compose / .env):
#   FRANKENPHP_MODE          classic | worker   (default: classic)
#   FRANKENPHP_WORKER_NUM    worker process count (optional; default = 2×CPU)
#   FRANKENPHP_LOOP_MAX      requests per worker before restart (Symfony Runtime)
#   FRANKENPHP_RESET_KERNEL  true|false — clone Kernel between requests
#
# Output:
#   FRANKENPHP_CONFIG  → injected into the `frankenphp { }` Caddyfile block.
#
# Worker flow with Symfony 8.1 (native, no runtime/frankenphp-symfony):
#   1) FrankenPHP boots public/index.php as a worker and sets FRANKENPHP_WORKER=1
#   2) Symfony\Component\Runtime\SymfonyRuntime detects FRANKENPHP_WORKER
#   3) Uses FrankenPhpWorkerRunner → frankenphp_handle_request() loop
# =============================================================================
configure_frankenphp_mode() {
	mode="$(printf '%s' "${FRANKENPHP_MODE:-classic}" | tr '[:upper:]' '[:lower:]')"

	# Front controller relative to WORKDIR (/app). Must match php_server.
	worker_file="./public/index.php"

	case "$mode" in
		worker)
			# Full Caddy worker block: path + optional num.
			workers="${FRANKENPHP_WORKER_NUM:-}"
			if [ -n "$workers" ]; then
				export FRANKENPHP_CONFIG="worker {
	file ${worker_file}
	num ${workers}
}"
			else
				export FRANKENPHP_CONFIG="worker {
	file ${worker_file}
}"
			fi
			;;
		classic)
			# No worker: full bootstrap per request (PHP-FPM-like).
			export FRANKENPHP_CONFIG=""
			;;
		*)
			echo "Invalid FRANKENPHP_MODE: '${FRANKENPHP_MODE}'. Use 'classic' or 'worker'." >&2
			exit 1
			;;
	esac

	echo "FrankenPHP mode: ${mode}"
	if [ -n "$FRANKENPHP_CONFIG" ]; then
		echo "FRANKENPHP_CONFIG:"
		echo "$FRANKENPHP_CONFIG"
	else
		echo "FRANKENPHP_CONFIG: (empty — classic)"
	fi
}

if [ "$1" = 'frankenphp' ] || [ "$1" = 'php' ] || [ "$1" = 'bin/console' ] || [ "$1" = 'pnpm' ] || [ "$1" = 'node' ]; then
	# FrankenPHP mode only applies to the HTTP app server.
	if [ "$1" = 'frankenphp' ] || [ "$1" = 'php' ] || [ "$1" = 'bin/console' ]; then
		configure_frankenphp_mode
	fi

	# Bind-mount ./:/app is owned by the host UID; the container often runs as root.
	# Git 2.35.2+ refuses that mismatch unless /app is marked safe.
	if command -v git >/dev/null 2>&1; then
		git config --global --add safe.directory /app 2>/dev/null || true
	fi

	# Ensure the Symfony front controller exists (not the image welcome page).
	if [ "$1" = 'frankenphp' ] && [ ! -f public/index.php ]; then
		echo "ERROR: missing public/index.php. Is the code mounted? (compose.yaml volumes: ./:/app)" >&2
		exit 1
	fi

	# php + messenger* share ./:/app; serialize Composer so concurrent entrypoints
	# cannot race on an empty vendor/. Lock on the project bind mount (var/ is an
	# anonymous volume per container).
	#
	# Do NOT install pnpm here for frankenphp/php/bin/console: a cold pnpm install
	# can take several minutes and blocks HTTPS (/health/live) and Messenger until
	# done. Frontend deps are installed by `make deps` / `make up`, the vite HMR
	# service (command pnpm …), or CI prefetch. Opt in with INSTALL_JS_DEPS=1.
	(
		flock 9
		# Install Composer deps when vendor autoload is missing (empty or partial failed install).
		# Do not abort startup on network blips — PHP may still serve; re-run composer later.
		if [ -f composer.json ] && [ ! -f vendor/autoload_runtime.php ]; then
			echo 'Installing PHP dependencies (composer)…'
			composer install --prefer-dist --no-progress --no-interaction \
				|| echo "WARNING: composer install failed (check DNS/network). Re-run: docker compose exec php composer install" >&2
		fi

		install_js=0
		if [ "$1" = 'pnpm' ] || [ "$1" = 'node' ] || [ "${INSTALL_JS_DEPS:-0}" = '1' ]; then
			install_js=1
		fi
		# Install JS deps when Vite binary is missing (Pentatrion Vite / pnpm).
		# Needs Composer file: deps (ux-autocomplete / ux-live-component under vendor/).
		# Image sets CI=1 (Corepack); that forces frozen-lockfile — fall back without CI when needed.
		if [ "$install_js" = '1' ] && [ -f package.json ] && [ ! -x node_modules/.bin/vite ]; then
			if [ ! -d vendor/symfony/ux-autocomplete/assets ] || [ ! -d vendor/symfony/ux-live-component/assets ]; then
				echo 'Composer UX asset packages missing; retrying composer install…'
				composer install --prefer-dist --no-progress --no-interaction \
					|| echo "WARNING: composer install failed (UX file: deps for pnpm). Re-run: docker compose exec php composer install" >&2
			fi
			echo 'Installing JS dependencies (pnpm)…'
			pnpm install --frozen-lockfile \
				|| CI= pnpm install --no-frozen-lockfile \
				|| echo "WARNING: pnpm install failed (check DNS/network, composer vendor for file: deps, or lockfile). Run: make pnpm ARGS='install --no-frozen-lockfile'" >&2
		fi
	) 9>./.deps.lock

	if [ -f bin/console ] && { [ "$1" = 'frankenphp' ] || [ "$1" = 'php' ] || [ "$1" = 'bin/console' ]; }; then
		php bin/console -V || true
	fi

	# Wait for MySQL when Doctrine is available and DATABASE_URL is set (HTTP / console only).
	if { [ "$1" = 'frankenphp' ] || [ "$1" = 'php' ] || [ "$1" = 'bin/console' ]; } \
		&& [ -f bin/console ] && [ -n "${DATABASE_URL:-}" ] \
		&& php bin/console list 2>/dev/null | grep -q 'dbal:run-sql'; then
		echo 'Waiting for database to be ready...'
		ATTEMPTS=60
		until [ "$ATTEMPTS" -eq 0 ] || php bin/console dbal:run-sql -q "SELECT 1" >/dev/null 2>&1; do
			ATTEMPTS=$((ATTEMPTS - 1))
			echo "Still waiting for database... ${ATTEMPTS} attempts left."
			sleep 1
		done
		if [ "$ATTEMPTS" -eq 0 ]; then
			echo 'WARNING: database not reachable yet.' >&2
		else
			echo 'The database is now ready and reachable.'
			if [ -d migrations ] && [ "$(find migrations -iname '*.php' -print -quit 2>/dev/null)" ]; then
				php bin/console doctrine:migrations:migrate --no-interaction --all-or-nothing || true
			fi
			# Create messenger_messages tables when Doctrine transport is configured
			if php bin/console list 2>/dev/null | grep -q 'messenger:setup-transports'; then
				php bin/console messenger:setup-transports --no-interaction || true
			fi
		fi
	fi

	if [ "$1" = 'frankenphp' ] || [ "$1" = 'php' ] || [ "$1" = 'bin/console' ]; then
		echo 'PHP app ready!'
	fi
fi

exec docker-php-entrypoint "$@"
