FROM php:8.4-cli

RUN apt-get update && apt-get install -y \
    git curl zip unzip libpq-dev libzip-dev libonig-dev \
    && docker-php-ext-install pdo pdo_pgsql zip mbstring bcmath pcntl \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

COPY composer.json composer.lock ./
RUN composer install --no-interaction --no-dev --no-scripts --no-autoloader

COPY artisan ./
COPY app ./app
COPY bootstrap ./bootstrap
COPY config ./config
COPY database ./database
COPY public ./public
COPY resources ./resources
COPY routes ./routes
COPY src ./src
RUN mkdir -p storage/app storage/framework/cache storage/framework/sessions storage/framework/views storage/logs \
    && composer dump-autoload --optimize --no-dev \
    && chown -R www-data:www-data storage bootstrap/cache

COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

USER www-data
EXPOSE 8080
ENTRYPOINT ["/entrypoint.sh"]
