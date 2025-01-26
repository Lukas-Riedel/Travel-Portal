FROM php:8.2-apache

# Update and install dependencies
RUN apt-get update && apt-get install -y \
    curl \
    zip \
    unzip \
    git \
    libzip-dev \
    && docker-php-ext-install mysqli zip

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Download and set up `wait-for-it.sh`
RUN curl -sSL https://github.com/vishnubob/wait-for-it/raw/master/wait-for-it.sh -o /usr/local/bin/wait-for-it && chmod +x /usr/local/bin/wait-for-it

# Enable Apache modules
RUN a2enmod rewrite

# Set working directory and copy project files
WORKDIR /var/www/html/
COPY . /var/www/html/

# Set permissions for the web server
RUN chown -R www-data:www-data /var/www/html && chmod -R 755 /var/www/html

# Expose port 80
EXPOSE 80

# Set the default command
CMD ["apache2-foreground"]
