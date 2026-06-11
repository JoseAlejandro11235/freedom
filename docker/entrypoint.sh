#!/bin/sh
set -e

cd /var/www/html

if [ -n "${DB_HOST:-}" ]; then
    echo "Waiting for database at ${DB_HOST}:${DB_PORT:-3306}..."
    until php -r "
        try {
            new PDO(
                'mysql:host=${DB_HOST};port=${DB_PORT:-3306}',
                '${DB_USERNAME}',
                '${DB_PASSWORD}'
            );
            exit(0);
        } catch (Exception \$e) {
            exit(1);
        }
    " 2>/dev/null; do
        sleep 2
    done
    echo "Database is ready."
fi

if [ ! -f .env ]; then
    if [ -f .env.example ]; then
        cp .env.example .env
    fi
fi

if [ -z "${APP_KEY:-}" ]; then
    php artisan key:generate --force --no-interaction 2>/dev/null || true
fi

php artisan storage:link --force --no-interaction 2>/dev/null || true

# Host bootstrap/cache can reference dev-only packages (e.g. Pail) while vendor is --no-dev.
if [ "${APP_ENV:-local}" != "production" ]; then
    rm -f bootstrap/cache/packages.php bootstrap/cache/services.php bootstrap/cache/config.php
    php artisan package:discover --ansi --no-interaction 2>/dev/null || true
fi

# Stale public/hot breaks CSS/JS when Vite is not running (page looks dark / frozen).
if [ -f /var/www/html/public/hot ]; then
    VITE_CHECK_URL="${VITE_DEV_SERVER_URL:-http://host.docker.internal:5173}"
    if ! curl -sf "${VITE_CHECK_URL}/@vite/client" -o /dev/null 2>/dev/null; then
        rm -f /var/www/html/public/hot
        echo "Removed stale public/hot (Vite not reachable at ${VITE_CHECK_URL})."
    fi
fi

mkdir -p storage/app/private/livewire-tmp storage/app/public
chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true
chmod -R ug+rwx storage bootstrap/cache 2>/dev/null || true

if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
    php artisan migrate --force --no-interaction
fi

if [ "${APP_ENV:-local}" = "production" ]; then
    php artisan config:cache --no-interaction
    php artisan route:cache --no-interaction
    php artisan view:cache --no-interaction
fi

exec "$@"
