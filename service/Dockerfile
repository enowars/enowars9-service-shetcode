FROM php:8.2-fpm

RUN apt-get update && apt-get install -y \
    libpq-dev \
    git \
    unzip \
    nginx \
    bash \
    docker.io \
    python3 \
  && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-install pdo pdo_pgsql

RUN usermod -aG docker www-data

ENV DOCKER_HOST=unix:///var/run/docker.sock

WORKDIR /var/www/html

COPY . /var/www/html

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

RUN composer install --no-dev --optimize-autoloader --no-interaction

COPY docker/nginx.conf /etc/nginx/sites-available/default

RUN mkdir -p var/log var/cache public/submissions && chmod -R 777 var/log var/cache public/submissions

RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

COPY docker/start.sh /usr/local/bin/start
RUN chmod +x /usr/local/bin/start

EXPOSE 80
CMD ["/usr/local/bin/start"]