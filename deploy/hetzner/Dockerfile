# Production image for the Hetzner server (linux/amd64, Coolify + Traefik).
# One image, three roles chosen by the command: web (nginx + PHP-FPM on
# :8080), queue (queue:work) and scheduler (schedule:work). It runs as a
# non-root user and contains no secrets; configuration comes from the
# environment at runtime. The database is external (Coolify's MariaDB).
#
# Build from the repository root:
#   docker build -f deploy/hetzner/Dockerfile -t ghcr.io/adreece/portfolio:dev .

# --- PHP runtime and Composer packages -----------------------------------------
FROM alpine:3.21 AS php

RUN apk add --no-cache \
        nginx supervisor tini composer \
        php83 php83-fpm php83-opcache php83-pdo php83-pdo_mysql php83-mysqli \
        php83-mbstring php83-xml php83-dom php83-xmlreader php83-xmlwriter php83-simplexml \
        php83-tokenizer php83-ctype php83-fileinfo php83-bcmath php83-zip php83-gd \
        php83-curl php83-openssl php83-session php83-intl php83-exif php83-iconv \
        php83-phar php83-sodium php83-pcntl php83-posix php83-pecl-imagick \
    && ln -sf /usr/bin/php83 /usr/bin/php \
    && addgroup -g 1000 app && adduser -D -u 1000 -G app -h /var/www app

WORKDIR /var/www/html

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --no-interaction --prefer-dist \
    && rm -rf /root/.composer/cache

# --- Front-end assets ---------------------------------------------------------
FROM node:22-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci --no-audit --no-fund
# app.js imports Livewire's ESM build and app.css scans Laravel's pagination views.
COPY --from=php /var/www/html/vendor/livewire/livewire/dist ./vendor/livewire/livewire/dist
COPY --from=php /var/www/html/vendor/laravel/framework/src/Illuminate/Pagination/resources/views ./vendor/laravel/framework/src/Illuminate/Pagination/resources/views
COPY vite.config.js ./
COPY resources ./resources
COPY public ./public
RUN npm run build

# --- Final image --------------------------------------------------------------
FROM php

COPY . .
COPY --from=assets /app/public/build ./public/build

RUN composer dump-autoload --optimize --no-dev --no-interaction \
    && rm -rf docker hydrogen.json deploy/hetzner/.env.example \
    && ln -sfn ../storage/app/public public/storage \
    && mkdir -p storage/app/public storage/app/media storage/framework/cache/data \
                storage/framework/sessions storage/framework/views storage/logs \
    && chown -R app:app storage bootstrap/cache

COPY deploy/hetzner/docker/nginx.conf /etc/nginx/nginx.conf
COPY deploy/hetzner/docker/php-fpm.conf /etc/php83/php-fpm.conf
COPY deploy/hetzner/docker/php.ini /etc/php83/conf.d/99-portfolio.ini
COPY deploy/hetzner/docker/supervisord.conf /etc/supervisord.conf
COPY --chmod=755 deploy/hetzner/docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN rm -f /etc/php83/php-fpm.d/www.conf

USER app
EXPOSE 8080
ENTRYPOINT ["/sbin/tini", "--", "/usr/local/bin/entrypoint.sh"]
CMD ["web"]
