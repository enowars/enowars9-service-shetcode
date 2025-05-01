#!/bin/bash

# Wait for database to be ready
echo "Waiting for database connection..."
until php -r "try { new PDO('pgsql:host=database;dbname=${POSTGRES_DB:-app}', '${POSTGRES_USER:-app}', '${POSTGRES_PASSWORD:-app}'); echo 'Connected successfully'; } catch (PDOException \$e) { echo \$e->getMessage(); exit(1); }" > /dev/null 2>&1; do
  echo -n "."
  sleep 1
done
echo ""

# Run migrations
echo "Running database migrations..."
php bin/console doctrine:migrations:migrate --no-interaction || true

# Start services
echo "Starting services..."
service nginx start
php-fpm 