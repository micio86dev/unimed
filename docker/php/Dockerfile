# syntax=docker/dockerfile:1
FROM php:8.4-fpm-alpine

# System dependencies for the PHP extensions we need.
RUN apk add --no-cache \
    bash \
    git \
    icu-dev \
    libzip-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    postgresql-dev \
    oniguruma-dev \
    $PHPIZE_DEPS

RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        pdo_pgsql \
        pgsql \
        bcmath \
        intl \
        zip \
        gd \
        opcache \
        pcntl

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Reasonable PHP defaults for a dev container.
RUN { \
    echo 'memory_limit=512M'; \
    echo 'upload_max_filesize=16M'; \
    echo 'post_max_size=16M'; \
    echo 'opcache.enable=1'; \
    echo 'opcache.enable_cli=0'; \
    } > /usr/local/etc/php/conf.d/zz-unimed.ini

COPY docker/php/entrypoint.sh /usr/local/bin/entrypoint
RUN chmod +x /usr/local/bin/entrypoint

EXPOSE 9000
ENTRYPOINT ["entrypoint"]
CMD ["php-fpm"]
