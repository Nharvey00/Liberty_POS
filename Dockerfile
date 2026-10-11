FROM php:8.2-apache

# Install required server dependencies and the PostgreSQL driver for Supabase
RUN apt-get update && apt-get install -y libpq-dev git unzip \
    && docker-php-ext-install pdo pdo_pgsql

# Enable Apache rewrite module for Laravel routing
RUN a2enmod rewrite

# Point Apache document root to Laravel's /public folder
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Copy your application files to the server
WORKDIR /var/www/html
COPY . .

# Install Composer and your Laravel dependencies
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
RUN composer install --no-dev --optimize-autoloader

# Set strict permissions for Laravel storage
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
