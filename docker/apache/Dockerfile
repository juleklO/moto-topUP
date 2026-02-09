FROM php:8.2-apache

# Install system dependencies
RUN apt-get update && apt-get install -y \
    libpq-dev \
    git \
    unzip \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo pdo_pgsql opcache

# Configure Apache
RUN a2enmod rewrite headers

# Set DocumentRoot to /var/www/html/public
RUN sed -ri 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/000-default.conf \
    && sed -ri 's!/var/www/!/var/www/html/public!g' /etc/apache2/apache2.conf

# Allow .htaccess overrides if you add them later (plan mentions .htaccess flexibility)
RUN sed -ri 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

# Security Hardening
RUN echo "ServerTokens Prod" >> /etc/apache2/apache2.conf
RUN echo "ServerSignature Off" >> /etc/apache2/apache2.conf

# Application Setup
WORKDIR /var/www/html
COPY ./src /var/www/html

# Permissions
RUN chown -R www-data:www-data /var/www/html