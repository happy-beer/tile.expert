FROM php:8.3-fpm-alpine

RUN apk add --no-cache \
        bash \
        git \
        icu-dev \
        libpq-dev \
        libxml2-dev \
        oniguruma-dev \
        ${PHPIZE_DEPS} \
        unzip \
        zip \
    && docker-php-ext-install -j"$(nproc)" \
        intl \
        opcache \
        pdo \
        pdo_pgsql \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del --no-network ${PHPIZE_DEPS} \
    && rm -rf /tmp/pear

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY docker/php/conf.d/app.ini /usr/local/etc/php/conf.d/app.ini

WORKDIR /var/www/html

CMD ["php-fpm"]
