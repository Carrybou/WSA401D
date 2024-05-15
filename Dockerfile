FROM php:8.2-cli

WORKDIR /var/www/sae401

COPY . .

RUN apt-get update && apt-get install -y \
    libzip-dev zip unzip git \
    && docker-php-ext-install zip pdo pdo_mysql

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Ajouter cette ligne pour permettre les plugins Composer
ENV COMPOSER_ALLOW_SUPERUSER=1

RUN composer install

CMD ["php", "bin/console", "messenger:consume", "async", "-vv"]
