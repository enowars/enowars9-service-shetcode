FROM php:8.2-fpm

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

RUN usermod -aG docker www-data
ENV DOCKER_HOST=unix:///var/run/docker.sock

WORKDIR /var/www/html

COPY . .

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
RUN composer install --no-dev --optimize-autoloader --no-interaction

RUN mkdir -p var/log var/cache public/submissions \
 && chown -R www-data:www-data var \
 && chmod -R 777 var/log var/cache public/submissions

RUN php bin/console cache:warmup --env=prod \
 && chown -R www-data:www-data var/cache

COPY docker/nginx.conf /etc/nginx/sites-available/default
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

COPY docker/start.sh /usr/local/bin/start.sh
RUN chmod +x /usr/local/bin/start.sh

EXPOSE 80
ENTRYPOINT ["start.sh"]
CMD []
