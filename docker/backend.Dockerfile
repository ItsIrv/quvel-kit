# Base image
FROM php:8.3-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    curl \
    unzip \
    git \
    mariadb-client \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    zip \
    libzip-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd zip pdo pdo_mysql

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Install Xdebug
RUN pecl install xdebug \
    && docker-php-ext-enable xdebug

# Copy custom PHP configuration -- add it to backend folder, or run the setup script
COPY php.ini /usr/local/etc/php/php.ini

# Set working directory
WORKDIR /var/www

# Set permissions and entrypoint
CMD ["php-fpm"]
