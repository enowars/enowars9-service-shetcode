#!/bin/bash

cd /var/www/html
php bin/console app:purge-old-data

echo "$(date): Executed data purge"