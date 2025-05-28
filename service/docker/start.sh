#!/usr/bin/env bash
set -e

chown -R www-data:www-data var/log var/cache public/submissions

echo "Waiting for database…"
until php -r "new PDO('pgsql:host=database;dbname=${POSTGRES_DB:-app}', '${POSTGRES_USER:-app}', '${POSTGRES_PASSWORD:-app}');" \
      > /dev/null 2>&1; do
  sleep 1
done
echo "Database ready."

echo "Running migrations…"
php bin/console doctrine:migrations:migrate --no-interaction || true

(
  while true; do
    php bin/console app:purge-old-data || echo "[!] Purge error"
    sleep 60
  done
) &

nginx -g 'daemon off;' &
exec php-fpm
