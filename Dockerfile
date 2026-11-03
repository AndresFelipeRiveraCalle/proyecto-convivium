FROM php:8.5.12-apache-bookworm

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        libfreetype6-dev \
        libicu-dev \
        libjpeg62-turbo-dev \
        libonig-dev \
        libpng-dev \
        libxml2-dev \
        libzip-dev \
        poppler-utils \
        tesseract-ocr \
        tesseract-ocr-spa \
        unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" dom gd intl mbstring pdo_mysql zip \
    && a2enmod rewrite headers \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

WORKDIR /var/www/html

COPY composer.json composer.lock ./

ENV COMPOSER_ALLOW_SUPERUSER=1
RUN composer install \
    --no-dev \
    --no-interaction \
    --prefer-dist \
    --optimize-autoloader

COPY . /var/www/html/

ENV CONVIVIUM_STORAGE_ROOT=/var/data/convivium \
    CONVIVIUM_TESSERACT_LANG=spa+eng

RUN mkdir -p /var/data/convivium \
    && chown www-data:www-data /var/data/convivium

COPY docker-entrypoint.sh /usr/local/bin/convivium-entrypoint
RUN chmod +x /usr/local/bin/convivium-entrypoint

EXPOSE 10000

ENTRYPOINT ["convivium-entrypoint"]
CMD ["apache2-foreground"]
