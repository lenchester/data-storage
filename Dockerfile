FROM php:8.4-apache

# Install necessary libraries and dependencies
RUN apt-get update && apt-get install -y \
  git \
  zip \
  unzip \
  libpng-dev \
  libzip-dev \
  default-mysql-client \
  libicu-dev  # Required for PHP Intl extension

# Install PHP extensions
RUN docker-php-ext-install pdo pdo_mysql zip gd intl  # Added intl for date/time formatting

# Enable Apache rewrite module
RUN a2enmod rewrite

# Set working directory
WORKDIR /var/www/html

# Copy project files to the container
COPY . /var/www/html

# Copy composer from official composer image
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Run composer install
#RUN COMPOSER_ALLOW_SUPERUSER=1 composer install

# Expose port 80
EXPOSE 80

# Set PHP upload limits
RUN echo "upload_max_filesize = 8M" >> /usr/local/etc/php/conf.d/uploads.ini
RUN echo "post_max_size = 8M" >> /usr/local/etc/php/conf.d/uploads.ini
RUN echo "memory_limit = 128M" >> /usr/local/etc/php/conf.d/uploads.ini


# Configure Apache to serve Symfony's public directory
RUN sed -i 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/000-default.conf

# Set the command to run Apache in the foreground
CMD ["apache2-foreground"]
