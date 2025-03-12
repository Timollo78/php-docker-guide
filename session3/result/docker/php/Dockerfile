FROM php:8.3-fpm

# Arguments
ARG UID=1000
ARG GID=1000
ARG USER_NAME=phpuser

RUN groupadd -g $GID phpuser && useradd -u $UID -g phpuser -s /bin/sh -m $USER_NAME
#
#RUN  docker-php-ext-install mysqli \
#     && pecl install xdebug-3.3.2 \
#     && docker-php-ext-enable xdebug

#COPY php/xdebug.ini /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini

USER $USER_NAME

CMD ["php-fpm"]
