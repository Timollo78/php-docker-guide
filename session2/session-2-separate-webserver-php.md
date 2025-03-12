# Separating Web Server and PHP Containers

In this section, we will enhance our container environment by separating the web server container from the PHP container. By splitting the two services into distinct containers, we can manage different versions and configurations independently.

In the following steps, we will create a web server container that communicates with the PHP container to handle application logic. This structure allows us to develop a more modular architecture that is easier to maintain and scale. Let’s proceed with setting up the separate containers.

## Nginx

In web development, separating the web server from PHP is a best practice for performance and scalability. In the initial setup, we used the official PHP-Apache container for simplicity and to quickly set up a basic working environment. However, as applications grow, using [Nginx](https://www.nginx.com/) as the web server offers several benefits. Nginx excels in handling static files efficiently, reduces memory usage with its event-driven architecture, and is widely used for its reverse proxy capabilities. Nginx also offers Docker images on [Docker Hub](https://hub.docker.com/_/nginx/).

- **nginx:_version_** This is a default image that is a safe choice if you're unsure which one to use. It's also used as a base image for many other variations.


```yaml
services:
  web:
    image: nginx:1.27
    ports:
      - 85:80
  
  mariadb:
    image: mariadb:10.6
    volumes:
      - mariadb-data:/var/lib/mysql
    environment:
      - MARIADB_ROOT_PASSWORD=1234
      - MARIADB_DATABASE=mydb
      - MARIADB_USER=myuser
      - MARIADB_PASSWORD=mY-s3cr3t
    ports:
      - 3378:3306

volumes:
  mariadb-data:

```

### Intermediate Result

After setting up this configuration and running `docker-compose up`, visiting `localhost:85` will display the default Nginx welcome page instead of our application’s "Hello World" file. This happens because Nginx is not yet configured to serve our PHP application. We will fix this in the next steps by linking Nginx to the PHP container and defining the correct document root.

### PHP FastCGI

In this section, we will introduce **PHP-FPM** ([FastCGI](https://en.wikipedia.org/wiki/FastCGI) Process Manager), a PHP container designed for high-performance PHP processing. Unlike the previous setup using the Apache container, which directly handled PHP requests, PHP-FPM operates as a separate service that listens for incoming requests and processes PHP scripts efficiently.

We will modify our `docker-compose.yml` to include the PHP-FPM service alongside Nginx:

```yaml
services:
  nginx:
    image: nginx:1.27
    ports:
      - 85:80

  php:
    image: php:8.3-fpm
  
  mariadb:
    image: mariadb:10.6
    volumes:
      - mariadb-data:/var/lib/mysql
    environment:
      - MARIADB_ROOT_PASSWORD=1234
      - MARIADB_DATABASE=mydb
      - MARIADB_USER=myuser
      - MARIADB_PASSWORD=mY-s3cr3t
    ports:
      - 3378:3306

volumes:
  mariadb-data:

```

This new PHP-FPM container is capable of processing PHP scripts but needs to be accessed from the Nginx container, as it cannot operate independently. We will connect it to the Nginx container through a **Reverse Proxy**.

## Reverse Proxy

To connect these independent containers, we will use a reverse proxy configuration. The **Nginx** container serves as the web server, while the **PHP-FPM** container handles the PHP script processing. This connection will allow Nginx to pass requests to PHP-FPM and receive the processed responses. We will connect these two containers using a [reverse proxy](https://en.wikipedia.org/wiki/Reverse_proxy).

To ensure our **PHP-FPM** container, which processes PHP scripts, is only accessible to Nginx within the internal network (not visible from the outside), we will create a virtual network between the containers. Let's update our `docker-compose.yml` file to include this network.

```yaml
services:
  nginx:
    build:
      context: .
      dockerfile: nginx/Dockerfile
    ports: 
      - 85:80
    networks:
      - internal
  php:
    image: php:8.3-fpm
    networks:
      - internal

  mariadb:
    image: mariadb:10.6
    volumes:
      - mariadb-data:/var/lib/mysql
    environment:
      - MARIADB_ROOT_PASSWORD=1234
      - MARIADB_DATABASE=mydb
      - MARIADB_USER=myuser
      - MARIADB_PASSWORD=mY-s3cr3t
    ports:
      - 3378:3306

volumes:
  mariadb-data:

networks:
  internal:
    driver: bridge
```

In this configuration, we have defined a new private network with the `bridge` driver, which is only visible to these two containers and is not accessible from the outside. This setup enhances security and ensures efficient communication between the web server and the PHP processor, allowing Nginx and PHP-FPM to interact directly while remaining isolated from external access. The `bridge` network also enables simple DNS resolution by container name, which is ideal for internal communication in a single-host environment.

Note that we have customized our web service. Instead of using a pre-built image from Docker Hub, we’ll create our own using a `Dockerfile` within the `nginx` folder. This approach allows us to modify Nginx settings to function as a reverse proxy for the PHP container. Start by creating the `nginx` folder and adding a `Dockerfile` inside, which will look like this:

```bash
mkdir nginx
vi nginx/Dockerfile
```

```dockerfile
FROM nginx:1.27
ADD nginx/default.conf /etc/nginx/conf.d
```

Then create `default.conf` inside the same directory:
```bash
vi nginx/default.conf
```

```conf
server {
    listen 0.0.0.0:80;
    root /var/www/html;
    location / {
        index index.php index.html;
    }
    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_pass php:9000;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root/$fastcgi_script_name;
    }
}
```

With this settings, every time nginx wants to process files ending with `.php`, it will send a request to **php-fpm** container with fast-cgi protocol on port `9000`, default port, on which this container should be listening.

Now, we have separated containers for nginx and php.

need to provide our containers with some data to process. But before that, let's talk a little bit about deployment environments because it will affect the way we will provide this data to containers.

### Volumes for Development environment

As we discussed before, volumes are very useful for development environment and we will use them now. Edit `docker-compose.yml` again, by adding one global volume for source codes linked to both containers.


```yaml
services:
  nginx:
    build:
      context: .
      dockerfile: nginx/Dockerfile
    ports: 
      - 85:80
    networks:
      - internal
    volumes:
      - ./web/:/var/www/html/
      - ./logs/nginx:/var/log/nginx/

  php:
    image: php:8.3-fpm
    networks:
      - internal
    volumes:
      - ./web/:/var/www/html/
      - ./logs/php.log:/var/log/fpm-php.www.log

  mariadb:
    image: mariadb:10.6
    volumes:
      - mariadb-data:/var/lib/mysql
    environment:
      - MARIADB_ROOT_PASSWORD=1234
      - MARIADB_DATABASE=mydb
      - MARIADB_USER=myuser
      - MARIADB_PASSWORD=mY-s3cr3t
    ports:
      - 3378:3306

volumes:
  mariadb-data:

networks:
  internal:
    driver: bridge
```

```bash
mkdir logs
mkdir logs/nginx
touch logs/php.log
```


....



connection to mariadb & using our own php image with xdebug and mysqli

move php Dockerfile to its own directory

```bash
mkdir php
mv Dockerfile php/.
mv xdebug.ini php/.
```

use own php image

```bash
vi php/Dockerfile
```

```dockerfile
FROM php:8.3-fpm

RUN  docker-php-ext-install mysqli \
     && pecl install xdebug-3.3.2 \
     && docker-php-ext-enable xdebug

COPY php/xdebug.ini /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini
```

```yaml
services:
  nginx:
    build:
      context: .
      dockerfile: nginx/Dockerfile
    ports: 
      - 85:80
    networks:
      - internal
    volumes:
      - ./web/:/var/www/html/
      - ./logs/nginx:/var/log/nginx/

  php:
    build:
      context: .
      dockerfile: php/Dockerfile
    networks:
      - internal
    volumes:
      - ./web/:/var/www/html/
      - ./logs/php.log:/var/log/fpm-php.www.log

  mariadb:
    image: mariadb:10.6
    networks:
      - internal
    volumes:
      - mariadb-data:/var/lib/mysql
    environment:
      - MARIADB_ROOT_PASSWORD=1234
      - MARIADB_DATABASE=mydb
      - MARIADB_USER=myuser
      - MARIADB_PASSWORD=mY-s3cr3t
    ports:
      - 3378:3306

volumes:
  mariadb-data:

networks:
  internal:
    driver: bridge
```
