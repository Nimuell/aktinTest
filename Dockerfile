FROM php:8.2-fpm

RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libicu-dev \
    sqlite3 \
    libsqlite3-dev \
    && docker-php-ext-install \
    pdo_mysql \
    pdo_sqlite \
    intl \
    opcache

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY . .

RUN composer install --no-dev --no-interaction

RUN mkdir -p var/
RUN touch var/data.db
RUN chown -R www-data:www-data /var/www/html/var
RUN chmod 777 var/data.db

RUN mkdir -p config/jwt
RUN openssl genpkey -out config/jwt/private.pem -aes256 -algorithm rsa -pkeyopt rsa_keygen_bits:4096 -pass pass:vilainapi
RUN openssl pkey -in config/jwt/private.pem -out config/jwt/public.pem -pubout -passin pass:vilainapi

CMD ["php-fpm"] 