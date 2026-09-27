# ---------------------------------------------------------------------------
# MyCMS — Docker image (PHP-FPM + nginx, non-root user)
# The front-end assets are committed (public/build, resources/themes/*/assets):
# no Node.js stage is needed.
# ---------------------------------------------------------------------------
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --no-scripts --no-autoloader --prefer-dist --ignore-platform-req=ext-*
COPY . .
RUN composer dump-autoload --no-dev --optimize --classmap-authoritative --no-scripts

FROM serversideup/php:8.4-fpm-nginx

USER root
# gd: image re-encoding (EXIF/GPS removal) — intl: dates — zip: theme & language packages
RUN install-php-extensions gd intl exif zip
USER www-data

ENV PHP_OPCACHE_ENABLE=1 \
    PHP_EXPOSE_PHP=Off \
    PHP_UPLOAD_MAX_FILE_SIZE=50M \
    PHP_POST_MAX_SIZE=55M \
    SSL_MODE=off \
    AUTORUN_ENABLED=true \
    AUTORUN_LARAVEL_STORAGE_LINK=false

COPY --chown=www-data:www-data . /var/www/html
COPY --chown=www-data:www-data --from=vendor /app/vendor /var/www/html/vendor
# At start-up: installation of the starter content and of the first administrator
COPY --chmod=755 docker/entrypoint.d/ /etc/entrypoint.d/

EXPOSE 8080
