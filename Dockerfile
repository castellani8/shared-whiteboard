FROM php:8.2-fpm-alpine

WORKDIR /var/www/html

RUN apk --update --no-cache add \
    libsodium \
    libsodium-dev \
    libzip-dev \
    postgresql-dev

RUN docker-php-ext-install zip mysqli pdo_mysql
RUN docker-php-ext-install sodium zip intl
RUN docker-php-ext-install pdo_pgsql

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

RUN chown -R www-data:www-data /var/www/html

RUN composer install --no-dev --optimize-autoloader
RUN cp .env.example .env
RUN php artisan key:generate

# Rodar migrações e otimizações
RUN php artisan migrate --force
RUN php artisan config:cache
RUN php artisan route:cache
RUN php artisan view:cache
