FROM php:8.4-cli-alpine

RUN apk add --no-cache \
        git \
        unzip \
        bash \
        linux-headers \
        $PHPIZE_DEPS \
    && pecl install xdebug \
    && docker-php-ext-enable xdebug \
    && apk del $PHPIZE_DEPS \
    && apk add --no-cache make

RUN echo "xdebug.mode=coverage" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

ARG UID=1000
ARG GID=1000

RUN addgroup -g ${GID} app && adduser -D -u ${UID} -G app app

WORKDIR /app

ENV COMPOSER_ALLOW_SUPERUSER=1
ENV DOCKER_ENV=1

USER app
