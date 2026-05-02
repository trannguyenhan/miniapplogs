# =============================================================================
# Build arguments (override on `docker build` with --build-arg)
# =============================================================================
ARG NODE_VERSION=22
ARG PHP_VERSION=8.2
ARG COMPOSER_VERSION=2.8

# =============================================================================
# Stage 1: Build frontend assets (Node + Vite)
# =============================================================================
FROM node:${NODE_VERSION}-alpine AS frontend-builder

WORKDIR /app

# Install dependencies (leverages layer cache when package.json unchanged)
COPY package.json package-lock.json* ./
RUN npm ci --no-audit --no-fund

# Copy only files needed for Vite build
COPY vite.config.js ./
COPY resources/ resources/
COPY public/ public/

RUN npm run build

# =============================================================================
# Stage 2: Composer – install PHP dependencies
# =============================================================================
FROM composer:${COMPOSER_VERSION} AS composer-builder

WORKDIR /app

ENV COMPOSER_ALLOW_SUPERUSER=1 \
    COMPOSER_NO_INTERACTION=1

COPY composer.json composer.lock ./

# Install production deps only (no dev, no scripts)
RUN composer install \
    --no-dev \
    --optimize-autoloader \
    --no-interaction \
    --no-scripts \
    --prefer-dist

# =============================================================================
# Stage 3: Final production image (PHP-FPM + Nginx)
# =============================================================================
FROM php:${PHP_VERSION}-fpm-alpine AS production

# Build-time metadata (used for Docker Hub image labels)
ARG BUILD_DATE
ARG GIT_COMMIT
ARG VERSION=latest
ARG DOCKER_REGISTRY=docker.io
ARG IMAGE_NAME=trannguyenhan/miniapplogs

LABEL org.opencontainers.image.title="Mini App Logs" \
      org.opencontainers.image.description="Laravel Log Viewer – xem log realtime từ nhiều server qua SSH" \
      org.opencontainers.image.version="${VERSION}" \
      org.opencontainers.image.created="${BUILD_DATE}" \
      org.opencontainers.image.revision="${GIT_COMMIT}" \
      org.opencontainers.image.source="https://github.com/trannguyenhan/miniapplogs" \
      org.opencontainers.image.licenses="MIT"

# ── System packages ──────────────────────────────────────────────────────────
RUN apk add --no-cache \
        bash \
        nginx \
        supervisor \
        curl \
        sqlite \
        libpng-dev \
        libjpeg-turbo-dev \
        libwebp-dev \
        freetype-dev \
        libzip-dev \
        sqlite-dev \
        icu-dev \
        oniguruma-dev \
        openssh-client \
    && docker-php-ext-configure gd \
        --with-freetype \
        --with-jpeg \
        --with-webp \
    && docker-php-ext-install -j$(nproc) \
        pdo \
        pdo_mysql \
        pdo_sqlite \
        mbstring \
        exif \
        pcntl \
        bcmath \
        gd \
        zip \
        intl \
        opcache \
    && mkdir -p /var/log/supervisor

# ── Application ──────────────────────────────────────────────────────────────
WORKDIR /var/www/html

# Copy vendor from composer stage
COPY --from=composer-builder /app/vendor ./vendor

# Copy full application source
COPY . .

# ⚠️  Xóa .env khỏi image – credentials không được baked vào layer
# Runtime sẽ nhận config qua biến môi trường Docker/k8s
RUN rm -f .env .env.* auth.json

# Copy compiled frontend assets from frontend stage
COPY --from=frontend-builder /app/public/build ./public/build

# ── Default ENV (có thể override khi run) ────────────────────────────────────
ENV APP_ENV=production \
    APP_DEBUG=false \
    APP_LOCALE=en \
    TRUSTED_PROXIES= \
    LOG_CHANNEL=stderr \
    SESSION_DRIVER=file \
    CACHE_STORE=file \
    QUEUE_CONNECTION=sync \
    DB_CONNECTION=sqlite \
    DB_DATABASE=/var/www/html/storage/database/app.sqlite \
    VIEW_COMPILED_PATH=/var/www/html/storage/framework/views

# ── Config files ─────────────────────────────────────────────────────────────
COPY docker/nginx/nginx.conf        /etc/nginx/nginx.conf
COPY docker/nginx/default.conf      /etc/nginx/http.d/default.conf
COPY docker/php/php.ini             /usr/local/etc/php/conf.d/app.ini
COPY docker/php/www.conf            /usr/local/etc/php-fpm.d/www.conf
COPY docker/supervisord.conf        /etc/supervisor/conf.d/supervisord.conf
COPY docker/entrypoint.sh           /entrypoint.sh

# ── Permissions ──────────────────────────────────────────────────────────────
RUN chmod +x /entrypoint.sh \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 775 /var/www/html/storage \
    && chmod -R 775 /var/www/html/bootstrap/cache

EXPOSE 80

# Container is healthy when Laravel health endpoint is reachable.
HEALTHCHECK --interval=30s --timeout=5s --start-period=40s --retries=3 \
    CMD curl -fsS http://127.0.0.1/up || exit 1

ENTRYPOINT ["/entrypoint.sh"]
