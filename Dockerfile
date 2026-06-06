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

# Symfony Runtime still expects an .env file during Composer auto-scripts.
RUN cp .env.example .env

# Set production environment variables
ENV APP_ENV=prod
ENV COMPOSER_ALLOW_SUPERUSER=1
ENV APP_SECRET=build-time-secret
ENV DEFAULT_URI=http://localhost
ENV AI_THERAPIST_URL=https://example.invalid
ENV DATABASE_URL="mysql://user:pass@127.0.0.1:3306/app?serverVersion=8.0"
ENV MESSENGER_TRANSPORT_DSN=doctrine://default?auto_setup=0
ENV MAILER_DSN=null://null
ENV OAUTH_GOOGLE_CLIENT_ID=
ENV OAUTH_GOOGLE_CLIENT_SECRET=
ENV GEMINI_API_KEY=
ENV OPENAI_API_KEY=
ENV HF_TOKEN=
ENV HUGGINGFACE_API_KEY=
ENV STRIPE_SECRET_KEY=
ENV STRIPE_PUBLIC_KEY=
ENV ELEVENLABS_API_KEY=
ENV VOICERSS_API_KEY=
ENV CLOUDINARY_URL=

# Install dependencies (this generates vendor/autoload_runtime.php)
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Do not bake Symfony's prod cache with build-time placeholder env values.
RUN rm -rf var/cache/*

# Make sure permissions are correct for Symfony's var directory
RUN chown -R www-data:www-data /app/var

# Expose port
EXPOSE 8080

CMD ["frankenphp", "run", "--config", "/etc/caddy/Caddyfile"]
