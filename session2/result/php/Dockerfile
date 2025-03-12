FROM php:8.3-fpm

RUN  docker-php-ext-install mysqli \
     && pecl install xdebug-3.3.2 \
     && docker-php-ext-enable xdebug

COPY php/xdebug.ini /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini
