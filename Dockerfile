# syntax=docker/dockerfile:1.7
FROM composer:2 AS composer
FROM busybox:1.37.0-musl AS archive-tools
FROM node:24-bookworm-slim AS node

FROM php:8.5-cli-bookworm AS php-tools
COPY --from=archive-tools /bin/busybox /usr/local/bin/archive-tool
RUN ln -s /usr/local/bin/archive-tool /usr/local/bin/unzip \
    && docker-php-ext-install -j"$(nproc)" pdo_mysql pcntl
COPY --from=composer /usr/bin/composer /usr/local/bin/composer
ENV COMPOSER_ALLOW_SUPERUSER=1
WORKDIR /app

FROM php-tools AS build
COPY --from=node /usr/local/bin/node /usr/local/bin/node
COPY --from=node /usr/local/lib/node_modules/npm /usr/local/lib/node_modules/npm
RUN ln -s /usr/local/lib/node_modules/npm/bin/npm-cli.js /usr/local/bin/npm \
    && ln -s /usr/local/lib/node_modules/npm/bin/npx-cli.js /usr/local/bin/npx
COPY composer.json composer.lock package.json package-lock.json ./
RUN --mount=type=cache,target=/root/.cache/composer composer install --no-scripts --no-autoloader --prefer-dist --no-interaction
RUN --mount=type=cache,target=/root/.npm npm ci --no-audit --no-fund
COPY . .
RUN mkdir -p bootstrap/cache storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs storage/app/private \
    && composer dump-autoload --optimize --no-interaction \
    && APP_ENV=testing DB_CONNECTION=sqlite DB_DATABASE=:memory: CACHE_STORE=array npm run build

FROM build AS compiled
RUN rm -rf node_modules

FROM php-tools AS demo
COPY --from=compiled /app /app
EXPOSE 8080
ENTRYPOINT ["sh", "/app/docker/laravel-start.sh"]
