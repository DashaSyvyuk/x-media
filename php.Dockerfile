FROM php:8.4-fpm AS base

RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libicu-dev \
    libpng-dev \
    libjpeg-dev \
    libwebp-dev \
    libfreetype6-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    curl \
    && docker-php-ext-configure intl \
    && docker-php-ext-install intl pdo pdo_mysql zip opcache \
    && docker-php-ext-configure gd --with-jpeg --with-webp --with-freetype \
    && docker-php-ext-install gd \
    && rm -rf /var/lib/apt/lists/*

# ---------------------------------------------
# Install Node 18 + Yarn for frontend build
# ---------------------------------------------
FROM base AS node_setup

RUN curl -fsSL https://deb.nodesource.com/setup_18.x | bash - \
    && apt-get update \
    && apt-get install -y nodejs \
    && npm install -g yarn \
    && rm -rf /var/lib/apt/lists/*

# ---------------------------------------------
# Composer install
# ---------------------------------------------
FROM node_setup AS composer_setup

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Copy only dependency files first (cache layers)
COPY composer.json composer.lock ./
COPY package.json yarn.lock ./

RUN yarn install
RUN composer install --no-scripts --no-dev --prefer-dist --no-interaction

# ---------------------------------------------
# Copy the full project
# ---------------------------------------------
COPY . .

# ---------------------------------------------
# Build assets (Webpack, Vite, Encore, etc.)
# ---------------------------------------------
RUN yarn build

# ---------------------------------------------
# Final Composer install (prod)
# ---------------------------------------------
RUN composer install --optimize-autoloader --no-dev --no-interaction \
    && composer dump-autoload --optimize

# ---------------------------------------------
# Symfony cache warmup
# ---------------------------------------------
RUN php bin/console cache:warmup

# ---------------------------------------------
# Permissions (important for Symfony)
# ---------------------------------------------
RUN mkdir -p var && chmod -R 777 var

# ---------------------------------------------
# Final runtime container
# ---------------------------------------------
FROM php:8.4-fpm AS runtime

WORKDIR /app

# Install needed PHP extensions in the runtime layer
RUN apt-get update && apt-get install -y \
    libicu-dev \
    libpng-dev \
    libjpeg-dev \
    libwebp-dev \
    libfreetype6-dev \
    && docker-php-ext-install intl pdo pdo_mysql opcache \
    && docker-php-ext-configure gd --with-jpeg --with-webp --with-freetype \
    && docker-php-ext-install gd \
    && rm -rf /var/lib/apt/lists/*

# Copy built app from builder
COPY --from=composer_setup /app /app

# Expose the HTTP port for DO App Platform
EXPOSE 8080

# Run Symfony using PHP built-in web server
CMD ["php", "-S", "0.0.0.0:8080", "-t", "public"]
