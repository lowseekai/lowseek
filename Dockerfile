FROM php:8.4-fpm-bookworm

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        nginx \
        unzip \
        git \
        libcurl4-openssl-dev \
        libfreetype6-dev \
        libicu-dev \
        libjpeg62-turbo-dev \
        libxml2-dev \
        libonig-dev \
        libpng-dev \
        libwebp-dev \
        libzip-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j"$(nproc)" \
        exif \
        gd \
        intl \
        pcntl \
        pdo_mysql \
        sockets \
        zip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

RUN { \
        echo 'memory_limit=512M'; \
        echo 'upload_max_filesize=100M'; \
        echo 'post_max_size=100M'; \
        echo 'log_errors=On'; \
        echo 'error_log=/proc/self/fd/2'; \
    } > /usr/local/etc/php/conf.d/99-iseekup-runtime.ini

COPY nginx.conf /etc/nginx/conf.d/default.conf
RUN rm -f /etc/nginx/sites-enabled/default

COPY docker-entrypoint.sh /usr/local/bin/iseekup-entrypoint
RUN chmod 755 /usr/local/bin/iseekup-entrypoint

COPY . /var/www/html

RUN composer install --prefer-dist --no-dev --no-interaction --no-progress --classmap-authoritative \
    && php -r '$file = "vendor/flarum/realtime/src/Websocket/Console/ServeCommand.php"; $contents = file_get_contents($file); $old = "E_ALL & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED"; $new = "E_ALL & ~E_NOTICE & ~E_DEPRECATED"; if (!str_contains($contents, $old)) { fwrite(STDERR, "Realtime PHP compatibility patch did not match\n"); exit(1); } file_put_contents($file, str_replace($old, $new, $contents));' \
    && mkdir -p storage bootstrap/cache public/assets \
    && chown -R www-data:www-data storage bootstrap/cache public/assets vendor

EXPOSE 80

ENTRYPOINT ["iseekup-entrypoint"]
CMD ["sh", "-c", "php-fpm -D && nginx -g 'daemon off;'"]
