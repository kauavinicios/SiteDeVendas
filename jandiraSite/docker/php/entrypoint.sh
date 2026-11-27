#!/bin/sh
set -eu

cd /var/www/html

mkdir -p \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    storage/app/public \
    bootstrap/cache

if [ ! -L public/storage ] || [ "$(readlink public/storage || true)" != "../storage/app/public" ]; then
    rm -rf public/storage
    ln -s ../storage/app/public public/storage
fi

if [ "$(id -u)" = "0" ]; then
    chown -R www-data:www-data storage bootstrap/cache
fi

if [ "$#" -gt 0 ]; then
    exec "$@"
fi

ROLE="${CONTAINER_ROLE:-app}"

case "$ROLE" in
    app)
        if [ "${APP_ENV:-production}" = "production" ] && [ -f vendor/autoload.php ]; then
            if [ "$(id -u)" = "0" ]; then
                su-exec www-data php artisan optimize
            else
                php artisan optimize
            fi
        fi
        exec php-fpm -F
        ;;
    worker)
        if [ "$(id -u)" = "0" ]; then
            exec su-exec www-data php artisan queue:work --verbose --tries=3 --timeout=90 --sleep=3 --max-time=3600
        fi
        exec php artisan queue:work --verbose --tries=3 --timeout=90 --sleep=3 --max-time=3600
        ;;
    scheduler)
        if [ "$(id -u)" = "0" ]; then
            exec su-exec www-data php artisan schedule:work
        fi
        exec php artisan schedule:work
        ;;
    *)
        echo "CONTAINER_ROLE inválido: '$ROLE' (use: app, worker ou scheduler)" >&2
        exit 1
        ;;
esac
