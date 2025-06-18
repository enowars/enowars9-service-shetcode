#!/usr/bin/env bash
set -e

chown -R www-data:www-data var/log var/cache public/submissions

echo "Waiting for database…"
until php -r "new PDO('pgsql:host=database;dbname=${POSTGRES_DB:-app}', '${POSTGRES_USER:-app}', '${POSTGRES_PASSWORD:-app}');" \
      > /dev/null 2>&1; do
  sleep 1
done
echo "Database ready."

echo "Waiting for code executor…"
until timeout 5 bash -c "</dev/tcp/${CODE_EXECUTOR_HOST:-code-executor}/${CODE_EXECUTOR_PORT:-2376}" 2>/dev/null; do
  echo "Waiting for code executor to be ready..."
  sleep 2
done
echo "Code executor ready."

echo "Running migrations…"
php bin/console doctrine:migrations:migrate --no-interaction || true

echo "Deleting existing admin user if exists…"
psql "$DATABASE_URL" -c \
  "DELETE FROM users WHERE username = 'admin';"

echo "Creating admin user…"
psql "$DATABASE_URL" -c \
  "INSERT INTO users(username, password, is_admin, created_at) \
   VALUES('admin', 'b9c604c096e4c585bf74654407438132', true, NOW()) \
  ;"
echo "Admin user setup completed."

(
  while true; do
    php bin/console app:purge-old-data || echo "[!] Purge error"
    sleep 60
  done
) &

nginx -g 'daemon off;' &
exec php-fpm
