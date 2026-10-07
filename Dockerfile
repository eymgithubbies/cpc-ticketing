FROM php:8.2-apache

# Install system dependencies
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    zip \
    unzip \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions with OPcache for performance
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
    gd \
    mysqli \
    pdo_mysql \
    opcache

# Enable Apache modules for performance
RUN a2enmod rewrite \
    && a2enmod deflate \
    && a2enmod expires \
    && a2enmod headers

# Configure OPcache for performance
RUN echo "opcache.enable=1" > /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.memory_consumption=128" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.interned_strings_buffer=8" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.max_accelerated_files=10000" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.revalidate_freq=2" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.fast_shutdown=1" >> /usr/local/etc/php/conf.d/opcache.ini

# Configure Apache compression and caching
RUN echo "AddOutputFilterByType DEFLATE text/html text/plain text/xml text/css text/javascript application/javascript application/json" > /etc/apache2/conf-available/compression.conf \
    && echo "BrowserMatch ^Mozilla/4 gzip-only-text/html" >> /etc/apache2/conf-available/compression.conf \
    && echo "BrowserMatch ^Mozilla/4\.0[678] no-gzip" >> /etc/apache2/conf-available/compression.conf \
    && echo "BrowserMatch \bMSIE !no-gzip !gzip-only-text/html" >> /etc/apache2/conf-available/compression.conf \
    && a2enconf compression

# Set working directory
WORKDIR /var/www/html

# Copy project files
COPY . /var/www/html/

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Expose port 80
EXPOSE 80
