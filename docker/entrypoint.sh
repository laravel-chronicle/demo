#!/usr/bin/env sh
set -e

cd /app

DB_FILE="${DB_DATABASE:-/data/database.sqlite}"

# Create the SQLite file on the (initially empty) persistent volume.
if [ ! -f "$DB_FILE" ]; then
	mkdir -p "$(dirname "$DB_FILE")"
	touch "$DB_FILE"
	NEEDS_SEED=1
fi

# Cache framework config for production (APP_KEY etc. arrive as Fly secrets).
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Build the deterministic demo dataset on first boot only.
if [ "${NEEDS_SEED:-0}" = "1" ]; then
	php artisan demo:reset --no-interaction
fi

exec "$@"
