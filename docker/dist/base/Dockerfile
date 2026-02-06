FROM php:8.4-cli-alpine

LABEL maintainer="GMEDIA"

# Set working directory
WORKDIR /var/www/html

# Install system dependencies
RUN apk add --no-cache \
    curl \
    libpng-dev \
    libxml2-dev \
    zip \
    unzip \
    git \
    oniguruma-dev \
    libzip-dev \
    icu-dev \
    postgresql-dev \
    openldap-dev \
    $PHPIZE_DEPS

# Install PHP extensions
RUN docker-php-ext-install \
    bcmath \
    gd \
    intl \
    ldap \
    mbstring \
    pcntl \
    pdo_mysql \
    pdo_pgsql \
    soap \
    zip

# Install PECL extensions
RUN pecl install redis igbinary msgpack \
    && docker-php-ext-enable redis igbinary msgpack

# Install Swoole
RUN pecl install swoole \
    && docker-php-ext-enable swoole

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Clean up
RUN apk del $PHPIZE_DEPS \
    && rm -rf /tmp/* /var/cache/apk/*
