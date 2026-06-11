# --- Stage 1: build the Vite/Tailwind assets ---
FROM node:22-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY vite.config.js ./
COPY resources resources
RUN npm run build

# --- Stage 2: PHP runtime (FrankenPHP serves classical Laravel; no Octane) ---
# Pin a minor when you cut a release, e.g. dunglas/frankenphp:1.x-php8.4-bookworm
FROM dunglas/frankenphp:php8.4 AS runtime

# PHP extensions Laravel + SQLite + Chronicle need (sodium/openssl ship enabled)
RUN install-php-extensions pdo_sqlite intl zip opcache pcntl

# supervisor runs the web server and the scheduler together in one container
RUN apt-get update \
    && apt-get install -y --no-install-recommends supervisor \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /app

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Install PHP deps first for better layer caching (no scripts/autoloader yet)
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction

# Application source
COPY . .

# Built assets from stage 1 (overrides any stale local public/build)
COPY --from=assets /app/public/build public/build

# Finish Composer: optimized autoloader + package discovery
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Production php.ini (enables opcache defaults)
RUN cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

# Runtime config
COPY docker/Caddyfile /etc/caddy/Caddyfile
COPY docker/supervisord.conf /etc/supervisor/conf.d/app.conf
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Writable dirs + the volume mount point
RUN chmod -R ug+rwX storage bootstrap/cache \
    && mkdir -p /data

ENV SERVER_NAME=:8080
EXPOSE 8080

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["supervisord", "-c", "/etc/supervisor/conf.d/app.conf"]
