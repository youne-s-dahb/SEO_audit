FROM php:8.2-fpm-alpine

# تثبيت الإضافات الضرورية لـ PostgreSQL
RUN apk add --no-cache libpq-dev bash git \
    && docker-php-ext-install pdo pdo_pgsql

# تثبيت Composer وسط الكونتينر
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# تشغيل سيرفر داخلي مؤقت لـ PHP
# السطر القديم: CMD php -S 0.0.0.0:80 -t public
# السطر الجديد المؤقت:
CMD php -S 0.0.0.0:80 -t public