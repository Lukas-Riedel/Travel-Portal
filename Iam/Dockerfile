FROM composer:2 AS builder
WORKDIR /app

COPY Common/ /Common
COPY Iam/composer.json ./
COPY Iam/composer.lock ./
RUN composer install --no-dev --prefer-dist --optimize-autoloader && composer dump-autoload --optimize
COPY Iam/. ./

FROM php:8.2-fpm-alpine

RUN apk add --no-cache nginx bash curl

WORKDIR /var/www/html
COPY --from=builder /app ./

COPY Iam/nginx.conf /etc/nginx/nginx.conf

EXPOSE 8080

CMD ["sh", "-c", "php-fpm -D && nginx -g 'daemon off;'"]
