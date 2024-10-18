# Use an official PHP runtime as a parent image
FROM php:7.4-apache

# Set the maintainer label
LABEL maintainer="ngoleduy@gmail.com"

# Update and install necessary packages
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libonig-dev \
    libzip-dev \
    zip \
    unzip \
    nano \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd mbstring zip opcache mysqli pdo pdo_mysql

# Install Xdebug
# RUN pecl install xdebug \
#     && docker-php-ext-enable xdebug
RUN pecl install xdebug-3.1.6 \
    && docker-php-ext-enable xdebug

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Set document root permissions
RUN chown -R www-data:www-data /var/www/html

# Copy custom PHP configuration (optional)
# COPY php.ini /usr/local/etc/php/

# Expose the default Apache port
EXPOSE 80

# Expose Xdebug port for remote debugging
EXPOSE 9003

# Start Apache server in the foreground
CMD ["apache2-foreground"]
