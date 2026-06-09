# Development image for the Laravel (PHP 8.4) backend.
# Production build/deploy is handled separately (see docs/architecture.md).
FROM php:8.4-cli

# System libraries required by the PHP extensions below.
RUN apt-get update && apt-get install -y --no-install-recommends \
    libzip-dev \
    zip \
    unzip \
    && docker-php-ext-install pdo_mysql bcmath pcntl zip \
    && rm -rf /var/lib/apt/lists/*

# Composer (copied from the official image).
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Install dependencies first to leverage Docker layer caching.
# vendor is mounted as a container-managed volume in Compose (B-03); the volume
# is seeded from this image layer on first boot, then bind-mounted code runs on top.
COPY composer.json composer.lock ./
RUN composer install --no-interaction --no-scripts --prefer-dist

COPY . .

EXPOSE 8000

# Artisan dev server; bind to all interfaces so the host can reach it.
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
