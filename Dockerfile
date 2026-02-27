FROM php:8.3-cli-alpine

# Install system deps
RUN apk add --no-cache \
    curl \
    sqlite \
    sqlite-dev \
    unzip \
    git \
    && docker-php-ext-install pdo pdo_sqlite

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Copy composer files
COPY composer.json composer.lock* ./
RUN composer install --no-dev --no-interaction --optimize-autoloader --no-scripts

# Copy the rest of the application
COPY . .

# Run post-install scripts
RUN composer run-script post-autoload-dump || true

# Set up env
RUN cp .env.example .env && php artisan key:generate --ansi

# Create SQLite database
RUN touch database/database.sqlite && php artisan migrate --force

EXPOSE 8000

CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
