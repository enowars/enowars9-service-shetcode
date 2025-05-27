#!/bin/bash

if [ -S /var/run/docker.sock ]; then
  chmod 666 /var/run/docker.sock
fi

# Wait for database to be ready
echo "Waiting for database connection..."
until php -r "try { new PDO('pgsql:host=database;dbname=${POSTGRES_DB:-app}', '${POSTGRES_USER:-app}', '${POSTGRES_PASSWORD:-app}'); echo 'Connected successfully'; } catch (PDOException \$e) { echo \$e->getMessage(); exit(1); }" > /dev/null 2>&1; do
  echo -n "."
  sleep 1
done
echo ""

echo "Running database migrations..."
php bin/console doctrine:migrations:migrate --no-interaction || true

chown -R www-data:www-data /var/www/html/var/cache /var/www/html/var/log /var/www/html/public/submissions
chmod -R 777 /var/www/html/var/cache /var/www/html/var/log /var/www/html/public/submissions

echo "Starting services..."
service nginx start
php-fpm &

while true; do
  echo "Cleanup Database..."
  php bin/console app:purge-old-data || echo "Purge error"
  sleep 60
done