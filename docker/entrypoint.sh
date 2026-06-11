#!/usr/bin/env sh
set -e

cd /app

DB_FILE="${DB_DATABASE:-/data/database.sqlite}"

# Ensure the SQLite file exists on the (initially empty) persistent volume.
mkdir -p "$(dirname "$DB_FILE")"
touch "$DB_FILE"

# Cache framework config for production (APP_KEY etc. arrive as Fly secrets).
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Ensure the schema is present (idempotent; repairs a volume left empty by an
# earlier failed boot, otherwise the app crashes with "no such table: sessions").
php artisan migrate --force

# Seed the deterministic demo dataset whenever the ledger is empty. This covers a
# genuine first boot and a volume that was migrated but never seeded, while never
# wiping data on an ordinary restart. The hourly demo:reset handles refreshes.
LEDGER_ENTRIES=$(php artisan tinker --execute 'echo \Chronicle\Entry\Entry::query()->count();' 2>/dev/null | tr -dc '0-9')
if [ -z "$LEDGER_ENTRIES" ] || [ "$LEDGER_ENTRIES" = "0" ]; then
	php artisan demo:reset --no-interaction
fi

exec "$@"
