FROM php:8.0.0-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y git

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql

# Get latest Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Install XDebug
#COPY php.ini $PHP_INI_DIR/
COPY xdebug.ini $PHP_INI_DIR/conf.d/

RUN pecl install xdebug \
    && docker-php-ext-enable xdebug

#RUN echo 'xdebug.remote_enable = 1' >> /usr/local/etc/php/conf.d/php.ini

# Set working directory
# WORKDIR /var/www
