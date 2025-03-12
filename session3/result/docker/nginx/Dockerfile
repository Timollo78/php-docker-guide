FROM nginx:1.27

FROM nginx:alpine

# Set UID and GID as build arguments
ARG UID=1000
ARG GID=1000
ARG USER_NAME=nginxuser

# Create a non-root user
RUN addgroup -g $GID $USER_NAME && adduser -D -u $UID -G $USER_NAME -s /sbin/nologin $USER_NAME

# Adjust directory permissions
RUN mkdir -p /var/cache/nginx /var/run/nginx /etc/nginx/conf.d \
    && chown -R $USER_NAME:$USER_NAME /var/cache/nginx /var/run/nginx /etc/nginx/conf.d
#    \
#    && touch /var/run/nginx.pid && chown $USER_NAME:$USER_NAME /var/run/nginx.pid

# Add configuration file
ADD nginx/nginx.conf /etc/nginx/nginx.conf

# Use a non-privileged port
EXPOSE 8080

# Switch to non-root user
USER $USER_NAME

CMD ["nginx", "-g", "daemon off;"]
