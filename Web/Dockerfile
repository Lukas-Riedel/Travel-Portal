FROM php:8.0-apache
RUN apt-get update && apt-get install -y libpng-dev libjpeg-dev libfreetype6-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd mysqli
RUN apt-get install -y curl
RUN curl -sSL https://github.com/vishnubob/wait-for-it/raw/master/wait-for-it.sh -o /usr/local/bin/wait-for-it && chmod +x /usr/local/bin/wait-for-it
RUN a2enmod rewrite
COPY . /var/www/html/
WORKDIR /var/www/html/
EXPOSE 80