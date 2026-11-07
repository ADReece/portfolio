# Single-container image for hydrogen (Raspberry Pi 5, arm64):
# nginx + PHP-FPM + MariaDB + a queue worker, run by supervisord.

# --- PHP runtime and Composer packages -----------------------------------------
FROM alpine:3.21 AS app

RUN apk add --no-cache \
        nginx supervisor mariadb mariadb-client tini \
        php83 php83-fpm php83-opcache php83-pdo php83-pdo_mysql php83-mysqli \
        php83-mbstring php83-xml php83-dom php83-xmlreader php83-xmlwriter php83-simplexml \
        php83-tokenizer php83-ctype php83-fileinfo php83-bcmath php83-zip php83-gd \
        php83-curl php83-openssl php83-session php83-intl php83-exif php83-iconv \
        php83-phar php83-sodium php83-pcntl php83-posix php83-pecl-imagick \
    && ln -sf /usr/bin/php83 /usr/bin/php

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

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
COPY --from=app /var/www/html/vendor/livewire/livewire/dist ./vendor/livewire/livewire/dist
COPY --from=app /var/www/html/vendor/laravel/framework/src/Illuminate/Pagination/resources/views ./vendor/laravel/framework/src/Illuminate/Pagination/resources/views
COPY vite.config.js ./
COPY resources ./resources
COPY public ./public
RUN npm run build

# --- Final image --------------------------------------------------------------
FROM app
WORKDIR /var/www/html
COPY . .
COPY --from=assets /app/public/build ./public/build

RUN composer dump-autoload --optimize --no-dev --no-interaction \
    && ln -sfn ../storage/app/public public/storage \
    && mkdir -p storage/app/public storage/app/media storage/framework/cache/data \
                storage/framework/sessions storage/framework/views storage/logs \
    && chown -R nginx:nginx storage bootstrap/cache

COPY docker/nginx.conf /etc/nginx/http.d/default.conf
COPY docker/php-fpm.conf /etc/php83/php-fpm.d/www.conf
COPY docker/php.ini /etc/php83/conf.d/99-portfolio.ini
COPY docker/mariadb.cnf /etc/my.cnf.d/zz-portfolio.cnf
COPY docker/supervisord.conf /etc/supervisord.conf
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 8080
ENTRYPOINT ["/sbin/tini", "--", "/usr/local/bin/entrypoint.sh"]
