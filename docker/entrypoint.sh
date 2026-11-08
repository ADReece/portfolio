#!/bin/sh
# Prepares the app, then hands over to supervisord. Database setup and
# migrations run under supervisord (docker/db-setup.sh) because during a
# deploy the previous container still holds the MariaDB data files until
# this one passes its health check.
set -eu

cd /var/www/html

: "${APP_KEY:?APP_KEY must be set (from /etc/hydrogen-deploy/env/portfolio.env)}"
: "${DB_PASSWORD:?DB_PASSWORD must be set (from /etc/hydrogen-deploy/env/portfolio.env)}"

# Volumes may arrive empty or root-owned: make sure the directories exist.
mkdir -p storage/app/public storage/app/media storage/framework/cache/data \
         storage/framework/sessions storage/framework/views storage/logs \
         /var/lib/mysql /run/mysqld
chown -R nginx:nginx storage bootstrap/cache
chown -R mysql:mysql /var/lib/mysql /run/mysqld
rm -f /run/portfolio-ready

if [ ! -d /var/lib/mysql/mysql ]; then
    echo "[entrypoint] Initialising MariaDB data directory"
    mariadb-install-db --user=mysql --datadir=/var/lib/mysql --skip-test-db >/dev/null
fi

# Cache config for production (reads the environment, needs no database).
su -s /bin/sh nginx -c "php artisan config:cache && php artisan view:cache && php artisan event:cache"
su -s /bin/sh nginx -c "php artisan route:cache" || echo "[entrypoint] route:cache failed; continuing without it"

echo "[entrypoint] Starting services"
exec /usr/bin/supervisord -c /etc/supervisord.conf
