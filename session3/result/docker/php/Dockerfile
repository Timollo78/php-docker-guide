FROM php:8.3-fpm AS php-prod

# Arguments for user configuration
ARG UID=1000
ARG GID=1000
ARG USER_NAME=phpuser

# Create a new user and group
RUN groupadd -g $GID phpuser && useradd -u $UID -g phpuser -s /bin/sh -m $USER_NAME

# Install required PHP extensions
RUN docker-php-ext-install mysqli

# Switch to non-root user for security
USER $USER_NAME

# Start PHP-FPM
CMD ["php-fpm"]

# Development image with Xdebug
FROM php-prod AS php-dev

# Switch back to root for installation
USER root

# Install and enable Xdebug
RUN pecl install xdebug-3.3.2 \
    && docker-php-ext-enable xdebug

# Copy Xdebug configuration
COPY xdebug.ini /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini

# Switch back to non-root user
USER $USER_NAME

# Start PHP-FPM
CMD ["php-fpm"]

