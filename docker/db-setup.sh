#!/bin/sh
# Waits for MariaDB, syncs the app user's password, runs migrations, then
# marks the app ready so the queue worker can start.
set -eu
cd /var/www/html

DB_DATABASE="${DB_DATABASE:-portfolio}"
DB_USERNAME="${DB_USERNAME:-portfolio}"

i=0
until mariadb-admin --protocol=socket ping >/dev/null 2>&1; do
    i=$((i + 1))
    [ $((i % 15)) -eq 0 ] && echo "[db-setup] still waiting for MariaDB (${i}s)"
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

echo "[db-setup] Running migrations"
su -s /bin/sh nginx -c "php artisan migrate --force"
touch /run/portfolio-ready
echo "[db-setup] Ready"
