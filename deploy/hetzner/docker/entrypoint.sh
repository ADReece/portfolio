#!/bin/sh
# Usage: entrypoint.sh web | queue | scheduler | <any command>
#   web        caches config, runs migrations (unless RUN_MIGRATIONS=false),
#              then nginx + PHP-FPM on :8080
#   queue      php artisan queue:work
#   scheduler  php artisan schedule:work
set -eu
cd /var/www/html

role="${1:-web}"

case "$role" in
    web|queue|scheduler) ;;
    *) exec "$@" ;;
esac

: "${APP_KEY:?APP_KEY must be set}"

# A fresh named volume may be missing these directories.
mkdir -p storage/app/public storage/app/media storage/framework/cache/data \
         storage/framework/sessions storage/framework/views storage/logs

# Production caches (read the runtime environment; no database needed).
php artisan config:cache --no-interaction >/dev/null
php artisan route:cache --no-interaction >/dev/null
php artisan view:cache --no-interaction >/dev/null
php artisan event:cache --no-interaction >/dev/null

# Wait for the database (Coolify's MariaDB may start after this container).
wait_for_db() {
    i=0
    until php artisan db:show --no-interaction >/dev/null 2>&1; do
        i=$((i + 1))
        if [ "$i" -ge 60 ]; then
            echo "[entrypoint] database not reachable after 120s" >&2
            return 1
        fi
        sleep 2
    done
}

case "$role" in
    web)
        if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
            wait_for_db
            echo "[entrypoint] php artisan migrate --force"
            php artisan migrate --force --no-interaction
        fi
        exec /usr/bin/supervisord -c /etc/supervisord.conf
        ;;
    queue)
        wait_for_db
        exec php artisan queue:work --queue=default,background --sleep=3 --tries=3 --max-time=3600 --memory=96
        ;;
    scheduler)
        wait_for_db
        exec php artisan schedule:work
        ;;
esac
