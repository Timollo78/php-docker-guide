FROM php:8.3-apache

RUN  docker-php-ext-install mysqli \
     && pecl install xdebug-3.3.2 \
     && docker-php-ext-enable xdebug

COPY xdebug.ini /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini
