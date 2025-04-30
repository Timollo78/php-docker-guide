

## Non root

## Multi-Stage Build: Xdebug in separate container
https://jtreminio.com/blog/developing-at-full-speed-with-xdebug/

### hu
```conf
map $cookie_XDEBUG_SESSION $my_fastcgi_pass {
    default php;
    xdebug php_xdebug;
}
```

## .env file

explain .env file

## directory structure

```
prj/  
├── docker/              # Dockerfiles and related configurations  
├── logs/                # Logs (should be excluded via .gitignore)  
├── public/              # Public webroot (index.php, assets, .htaccess)  
├── src/                 # Application logic (Controllers, Models, Services, etc.)  
├── config/              # Configuration files  
├── var/                 # Cache, sessions, uploaded files  
├── tests/               # Unit and integration tests  
├── vendor/              # If using Composer  
├── composer.json        # Composer dependencies  
├── docker-compose.yml   # Docker Compose file  
└── README.md            # Project documentation
```

Why this structure?
public/ as the webroot: Ensures only public assets and entry points are exposed.
src/ for application logic: Keeps core application code separate from public files.
config/ for settings: Helps separate configuration from application logic.
var/ for dynamic content: Handles logs, cache, and file uploads.
This structure is flexible and aligns well with modern PHP development practices. If you ever decide to use a framework like Symfony or Laravel, this layout will fit naturally. But it's also great for a plain OOP-based PHP project.

## nginx
```
location / {
    index index.php index.html;
}
```
This simply sets a default index file for directories.
If you visit /, it will try to load index.php first, then index.html if index.php is missing.

```
location / {
    try_files $uri $uri/ /index.php$is_args$args;
}
```
This try_files directive is used to check whether a requested file or directory exists and fallback to index.php if they don’t. Here's how it works:

- \$uri → Tries to serve the requested file directly (e.g., /style.css → serves style.css if it exists).
- \$uri/ → If the request is for a directory, it checks if it exists (e.g., /about/ → serves /about/index.html if it exists).
- /index.php\$is_args\$args → If neither a file nor a directory is found, the request is forwarded to index.php with query parameters. 
- Use case: This is commonly used in PHP frameworks like Laravel or Symfony, where all requests should be routed through index.php if no static file is found.

## routing

## Useful ...

### local docker registry

```bash
cd docker
docker run -d -p 5000:5000 --name registry registry:2

docker build --no-cache -t localhost:5000/my-php ./php
docker push localhost:5000/my-php
```
