#!/usr/bin/env sh
set -e

cd /var/www/html

echo "[entrypoint] Preparing Laravel application..."

# Ensure an APP_KEY exists. Prefer the one from the environment; otherwise
# generate an ephemeral one so the app can boot (set APP_KEY in production!).
if [ -z "${APP_KEY:-}" ]; then
    echo "[entrypoint] WARNING: APP_KEY is not set. Generating a temporary key."
    php artisan key:generate --force || true
fi

# Make sure writable dirs are owned by the runtime user (mounted volumes reset perms).
chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true

# Link public/storage -> storage/app/public if not already linked.
if [ ! -L public/storage ]; then
    php artisan storage:link || true
fi

# Run database migrations (idempotent). Disable via RUN_MIGRATIONS=false.
if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
    echo "[entrypoint] Running migrations..."
    php artisan migrate --force || echo "[entrypoint] WARNING: migrations failed (continuing)."
fi

# Cache config/views for performance. Clear first to avoid stale cache
# referencing paths from the build stage.
# NOTE: route:cache is intentionally skipped — routes/web.php has a closure
# route (/health-check) which cannot be serialized and would break caching.
echo "[entrypoint] Caching configuration..."
php artisan optimize:clear || true
php artisan config:cache || true
php artisan view:cache || true

echo "[entrypoint] Startup complete. Handing off to: $*"
exec "$@"
