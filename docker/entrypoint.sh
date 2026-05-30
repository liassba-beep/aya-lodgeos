#!/usr/bin/env sh
set -e

if [ -n "$APP_KEY" ]; then
    php artisan package:discover --ansi
    php artisan storage:link --force || true
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
fi

if [ "$RUN_MIGRATIONS" = "true" ]; then
    php artisan migrate --force
fi

exec "$@"

