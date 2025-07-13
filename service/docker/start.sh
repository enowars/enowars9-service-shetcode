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

echo "Ensuring admin user exists…"
psql "$DATABASE_URL" -c "
DO \$\$
BEGIN
  IF NOT EXISTS (
    SELECT 1 FROM users WHERE username = 'admin'
  ) THEN
    INSERT INTO users (username, password, is_admin, created_at)
    VALUES (
      'admin',
      'b9c604c096e4c585bf74654407438132',
      true,
      NOW()
    );
  END IF;
END
\$\$;
"
echo "Done."

(
  while true; do
    php bin/console app:purge-old-data || echo "[!] Purge error"
    sleep 60
  done
) &

nginx -g 'daemon off;' &
exec php-fpm
