
## Purpose

This tutorial, aimed at beginners, demonstrates how to set up a PHP development environment using Docker and Docker Compose. The provided PHP project is a simple example for illustration, not a full-fledged project setup. It covers running PHP in a container with Apache, enabling Xdebug for debugging, and adding a MariaDB container for database integration.

## Prerequisites

- **Docker & Docker Compose**: Ensure these are installed on your system (e.g., Docker Desktop for Windows and Mac, or Docker Engine and Compose for Linux).
- **Basic Terminal Knowledge**: Familiarity with command-line usage is helpful, as we’ll be running Docker commands.
- **Text Editor and Directory Creation**: You can use any editor (e.g., Notepad, Visual Studio Code) to create or modify files and your system’s file explorer for creating directories. The tutorial includes some Linux commands like `vi` and `mkdir`, but feel free to replace these with your preferred tools for file editing and directory management.
- **Compatibility**: If using Windows, ensure Docker Desktop is set up correctly. [WSL2](https://learn.microsoft.com/en-us/windows/wsl/about) is optional and usually not required for this tutorial, especially if using Visual Studio Code or Notepad as recommended.

## Introduction to Docker

Docker is a platform that allows you to package and run applications in isolated environments called **containers**. Containers include all dependencies, libraries, and configurations needed to run your application, making it easy to deploy and run across different systems. Unlike virtual machines, containers are lightweight, allowing multiple to run on a single system with minimal overhead. This approach simplifies development by ensuring that applications run consistently on any system, regardless of the underlying environment. In this tutorial, we’ll use Docker to create a PHP development environment that’s easy to set up and maintain.

## Project Creation

First, we’ll create our initial local PHP project.To do this, open a terminal window and run the commands below. Alternatively, you can create the project folder in your system’s file explorer.

```bash
mkdir myproject
```

While you could theoretically place the web content directly in the project directory, it's good practice to organize it in a subfolder. This keeps the project structured by separating web content from other code and configuration files.

```bash
cd myproject
mkdir web
```

create an index php file (Reminder: Windows users can use Notepad or Visual Studio Code as alternatives to `vi` for file creation and editing)
```bash
vi web/index.php
```

```php
<?php
echo "Hello World!";

```

## Using A Docker Container

Now that we have our project set up, it's time to run the application on a web server for development purposes.

**What is a web server?** A web server is software that serves web content, like HTML or PHP files, over a network. It listens for requests (e.g., when you visit a webpage) and responds by sending the appropriate files to the user's browser. In this tutorial, we'll use [Apache](https://httpd.apache.org/ABOUT_APACHE.html), a popular open-source web server, to serve our PHP content.

We will use Docker to create a local environment that simulates a web server, using the official PHP-Apache Docker image, `php:8.3-apache`. This image includes PHP 8.3 and Apache configured to serve PHP files, so we can run PHP code on a server with minimal setup.

To find the `php:8.3-apache` image, visit [Docker Hub](https://hub.docker.com/_/php), where you can explore many official images provided by Docker.

Use the following command to run a PHP container with Apache:
```bash
docker run --rm --name php83 -p 85:80  -v ./web:/var/www/html php:8.3-apache
```

| Command Part             | Description                                                                                                                                    |
| ------------------------ | ---------------------------------------------------------------------------------------------------------------------------------------------- |
| `docker run`             | Starts a container                                                                                                                             |
| `--rm`                   | Automatically removes the container and any associated anonymous volumes once it exits                                                         |
| `--name php83`           | Optional: Assigns a name (`php83`) to the container                                                                                            |
| `-p 85:80`               | Maps the container's port 80 to host port 85 (You can change the host port to another value if needed; just ensure it's not already in use)    |
| `-v ./web:/var/www/html` | Mounts the local `./web` directory to `/var/www/html` inside the container so Apache can access files from your host, like an `index.php` file |
| `php:8.3-apache`         | Specifies the Docker image you searched for on Docker Hub                                                                                      |

The volume mount `./web:/var/www/html` connects a directory on your host machine (`./web`) to a directory inside the container (`/var/www/html`). This setup means that any files you create or edit in `./web` are immediately available to Apache in the container without needing to rebuild it. This feature is particularly useful for development, as you can make updates to your code and see changes reflected instantly in the running application.

If we now check http://localhost:85 We should see "Hello World!"
Lets stop the container again by typing CTRL+C in the terminal where we started container.

## Docker Compose

The Docker command above can be complex. Docker Compose, a tool that simplifies managing multi-container applications through a YAML file, will make command-line usage easier. Although we’re starting with only a PHP-Apache container, Compose will allow us to add other containers, such as a database, without needing complex commands.

Let’s create the `docker-compose.yaml` file:
```bash
vi docker-compose.yaml
```

```yaml
services:
  myproject:
    image: php:8.3-apache
    volumes:
      - ./web:/var/www/html
    ports:
      - 85:80
```

We now can use docker compose to start and stop the container. The following command can be used instead of the above docker command to start the php-apache container

```bash
docker compose up
```

If we now check http://localhost:85 We should again see "Hello World!" in the browser

To stop the container type CTRL+C.
To remove the container from your system use the following command:

```bash
docker compose down
```

## Debugging

Debugging is an essential part of development, enabling you to identify and fix issues in your code. In this chapter, we will configure Xdebug, a powerful PHP debugging tool, within our Docker container. This will allow us to utilize features like step debugging and error reporting. We'll also create a PHP info file to confirm that Xdebug is installed correctly, ensuring a smooth debugging process for our project.
### Creating the PHP Info File

To check if Xdebug is configured in our PHP version (which it isn't at this point), we can create a simple PHP file that displays `phpinfo()`.

create phpinfo.php
```
vi web/phpinfo.php
```

```php
<?php
phpinfo();
```

If you now visit `localhost:85/phpinfo.php` in your browser, you'll see an overview of your PHP environment. By searching for "xdebug" on the page, you can confirm that Xdebug is not yet installed.

### Creating a Dockerfile and **Installing Xdebug**

Instructions for installing additional PHP extensions can be found on the Docker Hub page. To install Xdebug in our container image, we'll use the `pecl` command. For this, we need to create a Dockerfile to build our own PHP-Apache image with Xdebug enabled. A Dockerfile is a text file that contains a set of instructions for building a Docker image, specifying the base image, environment variables, and any software packages or configurations needed.

```bash
vi Dockerfile
```

```Dockerfile
FROM php:8.3-apache

RUN  pecl install xdebug-3.3.2 \
     && docker-php-ext-enable xdebug
```

* **`FROM php:8.3-apache`**: Specifies the base image for the new image, which is the official PHP 8.3 image with Apache.
* **`RUN pecl install xdebug-3.3.2`**: Installs Xdebug version 3.3.2 using the PECL package manager.
* **`&& docker-php-ext-enable xdebug`**: Enables the Xdebug extension in the PHP configuration.

### Configuring Xdebug

Now that Xdebug is installed in the container image, we need to configure it. Create an `xdebug.ini` file next to the Dockerfile:

```bash
vi xdebug.ini
```

```ini
zend_extension=xdebug ; Load the Xdebug extension

[xdebug] ; Start Xdebug configuration
; Enable debugging, profiling, and function tracing
xdebug.mode=debug,profile,trace 
; Activate Xdebug only when XDEBUG_TRIGGER is set
xdebug.start_with_request=trigger
; IDE callback: the hostname is injected by Docker Desktop on macOS/Windows, 
; and we add it for Linux via `extra_hosts` in the docker-compose.yaml
xdebug.client_host=host.docker.internal 
```

The `xdebug.ini` file needs to be copied into the container to apply the configuration.
Update the Dockerfile:

```Dockerfile
FROM php:8.3-apache

RUN  pecl install xdebug-3.3.2 \
     && docker-php-ext-enable xdebug

COPY ./xdebug.ini /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini
```

### Building the Docker Image

The Dockerfile we just created outlines the instructions for building the container image. To make it usable, we now need to build the image using the following command:

```bash
docker build -t php8.3-apache-xdebug .
```

- **`-t php8.3-apache-xdebug`**: Specifies a tag (name) for the image.
- **`.`**: Indicates the location of the Dockerfile.

### Updating Docker Compose

Finally, we need to modify the `docker-compose.yaml` file to utilize the newly built image.

```bash
# ensure the container is not running
docker compose down

vi docker-compose.yaml
```

Update to use the `php8.3-apache-xdebug` image:

```yaml
services:
  myproject:
    image: php8.3-apache-xdebug
    volumes:
      - ./web:/var/www/html
    ports:
      - 85:80

    # ────────────────────────────────────────────────────────────────────────────────
    # This block is needed only on **native Linux**, so that Xdebug (or other services)
    # can reach your IDE from inside the container using the alias `host.docker.internal`.
    #
    # Docker Desktop on **macOS/Windows** already provides this alias automatically.
    #
    # - If you don’t need it, remove the entire block or replace it with `extra_hosts: []`
    #   to keep the YAML file valid.
    # ────────────────────────────────────────────────────────────────────────────────
    extra_hosts:
      - "host.docker.internal:host-gateway"
```

### Verifying Xdebug

After starting the service again with `docker compose up` you can verify on `localhost:85/phpinfo.php` that xdebug is enabled now

## Mariadb

Now we have a web server running PHP with the Xdebug extension. However, we likely still need a database for our application.

### Add The Mariadb Container

Let`s search for an official mariadb image on [dockerhub](https://hub.docker.com/_/mariadb) and add an additional service to our docker-compose.yaml

```yaml
services:
  web:
    image: php8.3-apache-xdebug
    volumes:
      - ./web:/var/www/html
    ports:
      - 85:80

    # ────────────────────────────────────────────────────────────────────────────────
    # This block is needed only on **native Linux**, so that Xdebug (or other services)
    # can reach your IDE from inside the container using the alias `host.docker.internal`.
    #
    # Docker Desktop on **macOS/Windows** already provides this alias automatically.
    #
    # - If you don’t need it, remove the entire block or replace it with `extra_hosts: []`
    #   to keep the YAML file valid.
    # ────────────────────────────────────────────────────────────────────────────────
    extra_hosts:
      - "host.docker.internal:host-gateway"
  
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
In this `docker-compose.yaml` file, we are adding MariaDB to our project environment. We use a named volume (`mariadb-data`) for the MariaDB service to persist database data instead of a bind mount (`./web:/var/www/html`) as in the web service.
The named volume ensures that data remains intact even if the container is stopped or removed, providing easier management and better security by isolating database data. In contrast, the bind mount for the web service allows for immediate reflection of changes made on the host during development.

The `environment` section added to the MariaDB service defines environment variables within the container. For more details on available environment variables that can be customized, refer to the container image documentation on Docker Hub.

**Note:** The port **3306** used for MariaDB is mapped to host port **3378** (you can choose any available port on your system). If you prefer to access the database using port **3306** from your system, simply set the ports value to **3306** instead of **3378:3306**.

### Add Mysqli Extension To The Docker Image

Now that we have a database container set up, we need to install the PHP `mysqli` extension to utilize it. To do this, we will modify the Dockerfile by adding `docker-php-ext-install mysqli`.

```Dockerfile
FROM php:8.3-apache

RUN  docker-php-ext-install mysqli \
     && pecl install xdebug-3.3.2 \
     && docker-php-ext-enable xdebug

COPY ./xdebug.ini /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini
```

After a rebuild of our image and a restart of our services we should now see the mysqli-extension enabled on `localhost:85/phpinfo.php`

```bash
docker compose down
docker build -t php8.3-apache-xdebug .
docker compose up
```

### Verify Database Connection

Create the file `hostinfo.php` to verify the database configuration.
```
vi web/hostinfo.php
```

```php
<?php  
  
$mysqli = new mysqli("mariadb", "myuser", "mY-s3cr3t", "mydb");  

if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

echo $mysqli->host_info . "\n";

```

### Access Database With Database Client

In your database client (e.g. [HeidiSQL](https://www.heidisql.com/) or [DBeaver](https://dbeaver.io/)), create a new connection with the following settings:

- **Host:** `localhost`
- **Port:** `3378`
- **Username:** `myuser`
- **Password:** `mY-s3cr3t`
- **Database:** `mydb` (optional, depending on your client)

#### Troubleshooting Tips

- If you cannot connect, check if the MariaDB container is running and that you have the correct port mappings in your `docker-compose.yaml` file

## Links
- https://hub.docker.com/
- https://collabnix.com/docker-cheatsheet/
- https://collabnix.com/docker-compose-cheatsheet/
