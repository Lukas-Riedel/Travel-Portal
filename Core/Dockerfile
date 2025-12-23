FROM php:8.2-cli-bullseye AS builder
RUN apt-get update \
    && apt-get install -y libpq-dev git unzip tini \
    && docker-php-ext-install sockets pdo_pgsql pgsql pcntl \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*
WORKDIR /app
COPY Common/ /Common
COPY Core/composer.json ./
COPY Core/composer.lock ./
RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" \
    && php composer-setup.php --install-dir=/usr/local/bin --filename=composer \
    && rm composer-setup.php
RUN composer install --no-dev --prefer-dist --optimize-autoloader && composer dump-autoload --optimize
COPY Core/. ./
FROM php:8.2-fpm-bullseye
RUN apt-get update \
    && apt-get install -y wget gnupg2 lsb-release \
    && echo "deb http://apt.postgresql.org/pub/repos/apt/ $(lsb_release -cs)-pgdg main" > /etc/apt/sources.list.d/pgdg.list \
    && wget --quiet -O - https://www.postgresql.org/media/keys/ACCC4CF8.asc | apt-key add - \
    && apt-get update \
    && apt-get install -y libpq-dev nginx bash curl postgresql-client-16 tini \
    && docker-php-ext-install sockets pdo_pgsql pgsql pcntl opcache \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*
WORKDIR /var/www/html
COPY --from=builder /app ./
COPY Core/nginx.conf /etc/nginx/nginx.conf
COPY Core/www.conf /usr/local/etc/php-fpm.d/www.conf
EXPOSE 8080
COPY Core/docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh
ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
