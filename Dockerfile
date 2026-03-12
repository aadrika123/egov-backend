# ─── Stage 1: Composer dependencies ─────────────────────────────────────────
FROM php:8.1-cli AS vendor

# Install composer
COPY --from=composer:2.7 /usr/bin/composer /usr/bin/composer

RUN apt-get update && apt-get install -y --no-install-recommends unzip git libzip-dev && docker-php-ext-install zip && rm -rf /var/lib/apt/lists/*

WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install \
  --no-dev \
  --no-interaction \
  --prefer-dist \
  --optimize-autoloader \
  --no-scripts

# ─── Stage 2: Runtime ────────────────────────────────────────────────────────
FROM php:8.1-fpm

LABEL org.opencontainers.image.title="egov-backend" \
  org.opencontainers.image.vendor="Aadrika Enterprises"

RUN apt-get update && apt-get upgrade -y && apt-get install -y --no-install-recommends \
  nginx \
  supervisor \
  curl \
  libzip-dev \
  libpq-dev \
  libonig-dev \
  libxml2-dev \
  && docker-php-ext-install \
  pdo \
  pdo_pgsql \
  mbstring \
  zip \
  bcmath \
  xml \
  pcntl \
  && pecl install redis \
  && docker-php-ext-enable redis \
  && apt-get clean \
  && rm -rf /var/lib/apt/lists/*

COPY .docker/nginx.conf /etc/nginx/sites-available/default
COPY .docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

WORKDIR /var/www/html

COPY --from=vendor /app/vendor ./vendor
COPY . .

RUN chown -R www-data:www-data storage bootstrap/cache \
  && chmod -R 775 storage bootstrap/cache

EXPOSE 80

HEALTHCHECK --interval=30s --timeout=5s --start-period=10s --retries=3 \
  CMD curl -f http://localhost:80/ || exit 1

CMD php artisan config:cache && php artisan route:cache && php artisan view:cache && /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
