FROM php:8.2-fpm

# 1) Install system packages & PHP extensions
RUN apt-get update \
 && apt-get install -y \
      libpq-dev \
      git \
      unzip \
      nginx \
      bash \
      docker.io \
      python3 \
 && docker-php-ext-install pdo pdo_pgsql \
 && rm -rf /var/lib/apt/lists/*

# 2) Add www-data to the docker group (for socket use)
RUN usermod -aG docker www-data

ENV DOCKER_HOST=unix:///var/run/docker.sock

WORKDIR /var/www/html

# 3) Cache Composer install early
COPY composer.json composer.lock ./
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
 && composer install --no-dev --optimize-autoloader --no-interaction \
 && rm -rf ~/.composer/cache

# 4) Copy the rest of your application
COPY . .

# 5) Ensure runtime dirs exist & are owned by www-data
RUN mkdir -p var/log var/cache public/submissions \
 && chown -R www-data:www-data var \
 && chmod -R 777 var/log var/cache public/submissions

# 6) Pre-warm Symfony prod cache (as root) and re-chown
RUN php bin/console cache:clear --env=prod --no-warmup \
 && php bin/console cache:warmup --env=prod \
 && chown -R www-data:www-data var/cache

# 7) Replace php.ini with production settings
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

# 8) Install your custom nginx vhost
COPY docker/nginx.conf /etc/nginx/sites-available/default

# 9) Add entrypoint
COPY docker/start.sh /usr/local/bin/start.sh
RUN chmod +x /usr/local/bin/start.sh

EXPOSE 80

# ENTRYPOINT will do migrations, start nginx+php-fpm, cleanup loop, etc.
ENTRYPOINT ["start.sh"]
CMD []
