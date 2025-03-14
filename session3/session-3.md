

## Non root

## Multi-Stage Build: Xdebug in separate container
https://jtreminio.com/blog/developing-at-full-speed-with-xdebug/

## .env file

## directory structure

## routing

## Useful ...

### local docker registry

```bash
cd docker
docker run -d -p 5000:5000 --name registry registry:2

docker build --no-cache -t localhost:5000/my-php ./php
docker push localhost:5000/my-php
```
