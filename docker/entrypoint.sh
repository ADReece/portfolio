#!/bin/sh
# Prepares the database and app, then hands over to supervisord.
set -eu

cd /var/www/html

: "${APP_KEY:?APP_KEY must be set (from /etc/hydrogen-deploy/env/portfolio.env)}"
: "${DB_PASSWORD:?DB_PASSWORD must be set (from /etc/hydrogen-deploy/env/portfolio.env)}"
DB_DATABASE="${DB_DATABASE:-portfolio}"
DB_USERNAME="${DB_USERNAME:-portfolio}"

# Volumes may arrive empty or root-owned: make sure the directories exist.
mkdir -p storage/app/public storage/app/media storage/framework/cache/data \
         storage/framework/sessions storage/framework/views storage/logs \
         /var/lib/mysql /run/mysqld
chown -R nginx:nginx storage bootstrap/cache
chown -R mysql:mysql /var/lib/mysql /run/mysqld

# --- MariaDB: initialise on first boot, then sync the app user's password.
if [ ! -d /var/lib/mysql/mysql ]; then
    echo "[entrypoint] Initialising MariaDB data directory"
    mariadb-install-db --user=mysql --datadir=/var/lib/mysql --skip-test-db >/dev/null
fi

echo "[entrypoint] Starting MariaDB for setup"
mariadbd --user=mysql --datadir=/var/lib/mysql &
DB_PID=$!
i=0
until mariadb-admin --protocol=socket ping >/dev/null 2>&1; do
    i=$((i + 1))
    [ "$i" -gt 60 ] && { echo "[entrypoint] MariaDB did not start"; exit 1; }
    sleep 1
done

PW_SQL=$(printf '%s' "$DB_PASSWORD" | sed "s/\\\\/\\\\\\\\/g; s/'/\\\\'/g")
mariadb --protocol=socket <<SQL
CREATE DATABASE IF NOT EXISTS \`${DB_DATABASE}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '${DB_USERNAME}'@'localhost' IDENTIFIED BY '${PW_SQL}';
CREATE USER IF NOT EXISTS '${DB_USERNAME}'@'127.0.0.1' IDENTIFIED BY '${PW_SQL}';
ALTER USER '${DB_USERNAME}'@'localhost' IDENTIFIED BY '${PW_SQL}';
ALTER USER '${DB_USERNAME}'@'127.0.0.1' IDENTIFIED BY '${PW_SQL}';
GRANT ALL PRIVILEGES ON \`${DB_DATABASE}\`.* TO '${DB_USERNAME}'@'localhost';
GRANT ALL PRIVILEGES ON \`${DB_DATABASE}\`.* TO '${DB_USERNAME}'@'127.0.0.1';
FLUSH PRIVILEGES;
SQL

# --- Laravel: cache config for production, then migrate.
su -s /bin/sh nginx -c "php artisan config:cache && php artisan view:cache && php artisan event:cache"
su -s /bin/sh nginx -c "php artisan route:cache" || echo "[entrypoint] route:cache failed; continuing without it"

echo "[entrypoint] Running migrations"
su -s /bin/sh nginx -c "php artisan migrate --force"

echo "[entrypoint] Stopping setup MariaDB"
mariadb-admin --protocol=socket shutdown
wait "$DB_PID" || true

echo "[entrypoint] Starting services"
exec /usr/bin/supervisord -c /etc/supervisord.conf
