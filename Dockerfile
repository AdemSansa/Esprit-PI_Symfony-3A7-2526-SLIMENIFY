FROM dunglas/frankenphp:latest-php8.2

# Install system dependencies
RUN apt-get update && apt-get install -y --no-install-recommends \
    git \
    unzip \
    zip \
    libzip-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libicu-dev \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
    gd \
    zip \
    pdo \
    pdo_mysql \
    intl \
    opcache

# Copy composer from the official Composer image
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /app

# Copy the application source code
COPY . /app

# Copy custom Caddyfile to the correct system location for FrankenPHP
COPY Caddyfile /etc/caddy/Caddyfile

# Set production environment variables
ENV APP_ENV=prod

# Install dependencies (this generates vendor/autoload_runtime.php)
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Warm up the cache
RUN php bin/console cache:clear --env=prod --no-warmup \
    && php bin/console cache:warmup --env=prod

# Make sure permissions are correct for Symfony's var directory
RUN chown -R www-data:www-data /app/var

# Expose port
EXPOSE 8080
