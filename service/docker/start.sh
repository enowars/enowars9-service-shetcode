#!/usr/bin/env bash
set -e

# 1) Re-fix permissions on any runtime-generated dirs (in case Docker socket mount
#    or other ops changed ownership):
chown -R www-data:www-data var/log var/cache public/submissions

# 2) Wait for Postgres
echo "Waiting for database…"
until php -r "new PDO('pgsql:host=database;dbname=${POSTGRES_DB:-app}', '${POSTGRES_USER:-app}', '${POSTGRES_PASSWORD:-app}');" \
      > /dev/null 2>&1; do
  sleep 1
done
echo "Database ready."

# 3) Run migrations (ignore if none)
echo "Running migrations…"
php bin/console doctrine:migrations:migrate --no-interaction || true

# 4) Spawn cleanup loop in background
(
  while true; do
    php bin/console app:purge-old-data || echo "[!] Purge error"
    sleep 60
  done
) &

# 5) Launch nginx (foreground) and php-fpm (exec = PID 1)
nginx -g 'daemon off;' &
exec php-fpm
