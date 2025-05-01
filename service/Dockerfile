FROM php:8.2-fpm

RUN apt-get update && apt-get install -y \
    libpq-dev \
    git \
    unzip \
    nginx \
    && docker-php-ext-install pdo pdo_pgsql

WORKDIR /var/www/html

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Configure Nginx
COPY docker/nginx.conf /etc/nginx/sites-available/default

# Make sure PHP-FPM and Nginx can write to var/log
RUN mkdir -p var/log && chmod -R 777 var/log

# Set recommended PHP.ini settings
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

# Start Nginx and PHP-FPM
COPY docker/start.sh /usr/local/bin/start
RUN chmod +x /usr/local/bin/start

EXPOSE 80

CMD ["/usr/local/bin/start"] 