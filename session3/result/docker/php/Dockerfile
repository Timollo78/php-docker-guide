FROM php:8.3-fpm AS php-prod

ARG UID=1000
ARG GID=1000
ARG USER_NAME=phpuser

# Create a new user and group
RUN groupadd -g $GID -o $USER_NAME && useradd -u $UID -g $USER_NAME -s /bin/sh -m -o $USER_NAME

# Install required PHP extensions
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Switch to non-root user for security
USER $USER_NAME

# Start PHP-FPM in foreground
CMD ["php-fpm", "-F"]

# Development image with Xdebug
FROM php-prod AS php-dev

# Switch back to root for installation
USER root

# Install and enable Xdebug
RUN pecl install -o -f xdebug-3.3.2 \
    && docker-php-ext-enable xdebug \
    && rm -rf /tmp/pear

# Copy Xdebug configuration
COPY xdebug.ini /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini

# Switch back to non-root user
USER $USER_NAME

# Start PHP-FPM
CMD ["php-fpm", "-F"]
