FROM php:8.2-fpm

RUN apt-get update && apt-get install -y \
    git \
    zip \
    unzip \
    libpq-dev \
    libzip-dev \
    nginx \
    && docker-php-ext-install pdo pdo_pgsql zip

# Xdebug
RUN pecl install xdebug \
    && docker-php-ext-enable xdebug

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

RUN rm -f /etc/nginx/sites-enabled/default
COPY docker/api/nginx.conf /etc/nginx/conf.d/default.conf

# PHP ini overrides (incluye xdebug config)
COPY docker/api/php.ini /usr/local/etc/php/conf.d/99-overrides.ini

COPY docker/api/start.sh /start.sh
RUN chmod +x /start.sh

EXPOSE 80
CMD ["/start.sh"]