FROM php:8.3-apache

RUN apt-get update && apt-get install -y \
    libpq-dev \
    libicu-dev \
    unzip \
    git \
    zip \
    && docker-php-ext-configure intl \
    && docker-php-ext-install intl pdo pdo_pgsql pgsql

RUN a2enmod rewrite

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY . .

RUN composer install --no-dev --optimize-autoloader

RUN chown -R www-data:www-data writable

COPY apache.conf /etc/apache2/sites-available/000-default.conf

COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh

RUN chmod +x /usr/local/bin/docker-entrypoint.sh

EXPOSE 80

ENTRYPOINT ["docker-entrypoint.sh"]