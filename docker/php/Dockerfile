# syntax=docker/dockerfile:1

# ---------- Base: PHP-FPM with the extensions the app needs ----------
FROM php:8.4-fpm-alpine AS base

RUN apk add --no-cache icu-libs libzip libpng libjpeg-turbo freetype fcgi mysql-client \
    && apk add --no-cache --virtual .build-deps $PHPIZE_DEPS icu-dev libzip-dev libpng-dev libjpeg-turbo-dev freetype-dev linux-headers \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" pdo_mysql intl zip gd bcmath pcntl opcache \
    && apk del .build-deps

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY docker/php/php.ini /usr/local/etc/php/conf.d/zz-app.ini

WORKDIR /var/www/html

# ---------- Dev: bind-mounted source, PCOV for coverage, host UID ----------
FROM base AS dev

ARG UID=1000
ARG GID=1000

RUN apk add --no-cache --virtual .build-deps $PHPIZE_DEPS \
    && pecl install pcov && docker-php-ext-enable pcov \
    && apk del .build-deps \
    && addgroup -g "${GID}" app && adduser -D -u "${UID}" -G app app \
    && sed -i 's/^user = www-data/user = app/; s/^group = www-data/group = app/' /usr/local/etc/php-fpm.d/www.conf

COPY docker/php/entrypoint.sh /usr/local/bin/entrypoint
RUN chmod +x /usr/local/bin/entrypoint

USER app
ENTRYPOINT ["entrypoint"]
CMD ["php-fpm"]

# ---------- Assets: compiled once for the production image ----------
FROM node:22-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json* ./
RUN npm ci || npm install
COPY vite.config.js ./
COPY resources ./resources
RUN npm run build

# ---------- Prod: immutable image with vendor and built assets ----------
FROM base AS prod
COPY . .
RUN composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader \
    && chown -R www-data:www-data storage bootstrap/cache
COPY --from=assets /app/public/build ./public/build
USER www-data
CMD ["php-fpm"]
