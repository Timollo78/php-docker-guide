# Separating Web Server and PHP Containers

In this section, we will enhance our container environment by separating the web server container from the PHP container. By splitting the two services into distinct containers, we can manage different versions and configurations independently.

In the following steps, we will create a web server container that communicates with the PHP container to handle application logic. This structure allows us to develop a more modular architecture that is easier to maintain and scale. Let’s proceed with setting up the separate containers.

## Nginx

In web development, separating the web server from PHP is a best practice for performance and scalability. In the initial setup, we used the official PHP-Apache container for simplicity and to quickly set up a basic working environment. However, as applications grow, using [Nginx](https://www.nginx.com/) as the web server offers several benefits. Nginx excels in handling static files efficiently, reduces memory usage with its event-driven architecture, and is widely used for its reverse proxy capabilities. Nginx also offers Docker images on [Docker Hub](https://hub.docker.com/_/nginx/).

- **nginx:_version_** This is a default image that is a safe choice if you're unsure which one to use. It's also used as a base image for many other variations.


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

### Intermediate Result: Nginx Default Page (Problem)

After setting up this configuration and running `docker-compose up`, visiting `localhost:85` will display the default Nginx welcome page instead of our application’s "Hello World" file. This happens because Nginx is not yet configured to serve our PHP application. We will fix this in the next steps by linking Nginx to the PHP container and defining the correct document root.

## PHP FastCGI

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

This new PHP-FPM container is capable of processing PHP scripts but cannot serve them to the browser on its own. It needs to be accessed through a web server—in our case, **Nginx**.

To make this work, Nginx will act as a **reverse proxy**: it receives incoming requests from the browser, forwards `.php` requests to PHP-FPM for processing, and returns the result to the user.

Here’s what the basic flow looks like:
```
Browser (localhost:85) 
    ↓
[Nginx Container:80] — Reverse Proxy → [PHP-FPM Container:9000]
                                                  ↓          
                                       (executes PHP scripts)
```

## Creating a Custom Nginx Image (Reverse Proxy)

At this point, visiting localhost:85 displays only the default Nginx welcome page. To fix this and serve our PHP application properly, we need to create our own customized Nginx image, configuring it explicitly to forward PHP requests to our PHP container. We'll do this using Docker’s build functionality.

### Prepare Nginx Dockerfile and Configuration

Create a new folder called nginx, containing a Dockerfile and our custom configuration:

```bash
mkdir nginx
vi nginx/Dockerfile
```

Add these lines to the Dockerfile:

```dockerfile
FROM nginx:1.27
ADD nginx/default.conf /etc/nginx/conf.d
```

This tells Docker to base our custom image on the official Nginx image, and copy our own configuration file (default.conf) into the image.

Next, create the default.conf file in the same directory:

```bash
vi nginx/default.conf
```

Add the following Nginx configuration:

```nginx
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

Let’s break down what this configuration does:

- **`listen 0.0.0.0:80;`**  
  Instructs Nginx to listen for HTTP traffic on port 80 (the default HTTP port) on all available network interfaces inside the container.

- **`root /var/www/html;`**  
  Sets the root directory from which files will be served. This should match the volume mounted into the container in `docker-compose.yml`.

- **`location / { ... }`**  
  Defines how requests to the root URL (`/`) should be handled. Nginx will try to serve either `index.php` or `index.html`.

- **`location ~ \.php$ { ... }`**  
  Tells Nginx how to handle PHP files:

    - **`include fastcgi_params;`**  
      Loads default FastCGI parameters provided by Nginx.

    - **`fastcgi_pass php:9000;`**  
      Forwards PHP requests to the service named `php` (our PHP-FPM container), on port `9000`.  
      This requires that both containers share a Docker network.

    - **`fastcgi_index index.php;`**  
      Sets the default file to serve when a directory is requested.

    - **`fastcgi_param SCRIPT_FILENAME ...`**  
      Specifies the full path of the PHP script to execute.  
      `$document_root` refers to the directory defined by the `root` directive, and `$fastcgi_script_name` is the requested file name.

This configuration ensures that:

- Static files (like `.html`, `.css`, images, etc.) are served directly by Nginx.
- `.php` files are handed off to the PHP-FPM container for processing.

This setup tells Nginx to forward any requests ending with .php directly to our PHP-FPM container on port 9000.

### Update Docker Compose to use our custom Nginx image

Now that we have a custom Nginx configuration, update your docker-compose.yml to build and use your own image:
```yaml
services:
  nginx:
    build:                       
      context: .
      dockerfile: nginx/Dockerfile
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
Let’s briefly explain the new `build` section we introduced for the Nginx service:

- **`build`**  
  Instructs Docker Compose to build a Docker image locally instead of pulling it from Docker Hub.

    - **`context: .`**  
      Defines the build context — the folder Docker Compose uses when building the image.  
      The dot (`.`) refers to the current directory, which contains the `nginx/` folder and the `docker-compose.yml` file.

    - **`dockerfile: nginx/Dockerfile`**  
      Tells Docker Compose exactly which Dockerfile to use for building the image.  
      In this case, we point to the `Dockerfile` inside the `nginx/` directory.

With this setup, running

```bash
docker compose up --build
```

will automatically build your custom Nginx image using your Dockerfile, then start all containers based on that freshly built image.

## Private Network for Containers

Currently, our containers communicate using Docker's default network. While this works, it’s safer and cleaner to explicitly define a private network for internal container communication. This enhances security by isolating the containers' internal communication from external access.

Update your `docker-compose.yml` again, adding an internal network:
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

With this configuration:


- All containers connected to the `internal` network can communicate securely using container names as hostnames (e.g., `php:9000` to reach the PHP-FPM container).
- The network is isolated from the outside world, improving security and clearly separating internal communication from external access.

## Next Steps: Mounting Source Code for Development

In Session 1, we used a volume to share the `./web` folder with our container so PHP code changes would be reflected immediately without rebuilding the image.

Now that we’ve switched to a multi-container setup with **separate Nginx and PHP containers**, we’ll configure the same volume again—this time mounting it into **both** containers so that:

- Nginx can serve static files (like HTML or images)
- PHP-FPM can process `.php` scripts from the same source directory

Update your `docker-compose.yml` like this:

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
      - ./web:/var/www/html          # Mount source code
      - ./logs/nginx:/var/log/nginx  # Optional: persist Nginx logs

  php:
    image: php:8.3-fpm
    networks:
      - internal
    volumes:
      - ./web:/var/www/html          # Same source code mount
      - ./logs/php.log:/var/log/fpm-php.www.log  # Optional: persist PHP-FPM logs

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

You can now create the necessary folders and log file using:

```bash
mkdir -p logs/nginx
touch logs/php.log
```
## Custom PHP Image with Xdebug and MySQLi

To connect our application to the MariaDB container and enable debugging, we need to extend our PHP-FPM container with the following PHP extensions:

- **MySQLi**: Allows PHP to connect to MySQL-compatible databases like MariaDB.
- **Xdebug**: A powerful debugging tool for step-by-step inspection in your IDE.

Rather than modifying the official PHP image every time, we'll build our **own custom image** based on `php:8.3-fpm`.

### Step 1: Organize the PHP build context

Let’s move our existing PHP build files (Dockerfile and Xdebug config) into a dedicated folder:

```bash
mkdir php
mv Dockerfile php/
mv xdebug.ini php/
```
Your project structure should now look like this:
```
.
├── docker-compose.yml
├── nginx/
│   ├── Dockerfile
│   └── default.conf
├── php/
│   ├── Dockerfile
│   └── xdebug.ini
├── web/
├── logs/

```

### Step 2: Define the PHP Dockerfile

Open `php/Dockerfile` and add the following contents:

```dockerfile
FROM php:8.3-fpm

RUN docker-php-ext-install mysqli \
    && pecl install xdebug-3.3.2 \
    && docker-php-ext-enable xdebug

COPY xdebug.ini /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini
```

What this does:

- Installs the `mysqli` extension so PHP can talk to our MariaDB container.
- Installs and enables `xdebug` for local debugging.
- Copies our custom `xdebug.ini` config into the container.

### Step 3: Update the Compose File to Use the Custom PHP Image

Now that we’ve defined a custom image, update the `php` service in your `docker-compose.yml` to build from our local `php/` directory:

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
      - ./web:/var/www/html
      - ./logs/nginx:/var/log/nginx

  php:
    build:
      context: ./php           # use the new folder as build context
    networks:
      - internal
    volumes:
      - ./web:/var/www/html
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
Note that we made two important changes in this configuration:

- The `php` service now uses `build.context: ./php`, which instructs Docker Compose to build the image using the Dockerfile located in the `php` directory. This allows the PHP environment to be customized—for example, by installing extensions like `mysqli` and `xdebug`.

- The `mariadb` service has been added to the same internal Docker network as the other containers. As a result, the `php` container can establish a database connection using the hostname `mariadb`, because Docker Compose provides automatic hostname resolution between services within the same network.

### Step 4: Rebuild and Run

Whenever you change the Dockerfile, you need to rebuild the image:

```bash
docker compose down
docker compose up --build
```

Once everything is running, verify the setup as follows:

- Open `phpinfo.php` to confirm that the `mysqli` extension is enabled
- Open `phpinfo.php` to confirm that `xdebug` is correctly installed and active
- Open `hostinfo.php` to confirm that the PHP container can connect to the MariaDB database

## Summary

In this session, we have:

- Replaced the all-in-one PHP-Apache container with a **modular multi-container setup**
- Used **Nginx** as a reverse proxy to forward PHP requests to a separate **PHP-FPM** container
- Created a **custom Nginx image** with FastCGI configuration
- Added **volume mounts** for live development and log persistence
- Built a **custom PHP image** including `mysqli` and `xdebug`

This architecture provides a clean separation of concerns and forms a solid foundation for modern PHP development using Docker.

## What’s Next

In **Session 3**, we’ll take this setup even further by introducing:

- **Environment variables** using `.env` files
- A cleaner **project directory structure**
- **Non-root containers** for better security
- A **multi-stage Docker build** to optimize image size and optionally isolate Xdebug for development only
- Improved **Nginx routing configuration**

