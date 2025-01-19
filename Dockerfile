FROM php:8.2-fpm-alpine

WORKDIR /var/www/html
COPY . /var/www/html

RUN apk --update --no-cache add \
    libsodium \
    libsodium-dev \
    libzip-dev \
    postgresql-dev \
    && docker-php-ext-install zip mysqli pdo_mysql \
    && docker-php-ext-install sodium zip intl pdo_pgsql

RUN apk add --no-cache pcre-dev $PHPIZE_DEPS \
    && pecl install redis \
    && docker-php-ext-enable redis.so

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

RUN chown -R www-data:www-data /var/www/html
RUN chmod 755 /var/www/html/entrypoint.sh

ENTRYPOINT ["/bin/sh", "entrypoint.sh"]
CMD ["php-fpm"]

