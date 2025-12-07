FROM php:8.0-apache

# Install dependencies
RUN apt-get update && apt-get install -y \
    libzip-dev \
    unzip \
    && docker-php-ext-install zip

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Set the working directory
WORKDIR /var/www/html

# Copy the application files
COPY ./src /var/www/html/src
COPY ./public /var/www/html/public
COPY ./config /var/www/html/config
COPY ./composer.json /var/www/html/composer.json
COPY ./.env.example /var/www/html/.env.example
COPY ./migrations /var/www/html/migrations
COPY ./tests /var/www/html/tests
COPY ./includes /var/www/html/includes
COPY ./docker /var/www/html/docker

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Install PHP dependencies
RUN composer install --no-dev

# Expose port 80
EXPOSE 80

# Start Apache server
CMD ["apache2-foreground"]