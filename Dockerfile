FROM php:8.2-fpm

RUN apt-get update && apt-get install -y \
    bash \
    vim \
    nano \
    git \
    unzip \
    sudo \
    libpq-dev \
    libzip-dev \
    zip \
    && docker-php-ext-install pdo pdo_pgsql zip pcntl \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && curl -fsSL https://deb.nodesource.com/setup_18.x | bash - \
    && apt-get install -y nodejs \
    && rm -rf /var/lib/apt/lists/*

COPY .docker/app/.bashrc /root/.bashrc
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

SHELL ["/bin/bash", "-c"]

WORKDIR /var/www/html

COPY . .
RUN composer install

RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 775 /var/www/html

CMD ["php-fpm"]
