FROM php:8.4-fpm

# Базовые пакеты и dev-зависимости для сборки расширений
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
       nano vim wget git unzip ca-certificates \
       zlib1g-dev libpng-dev libzip-dev libpq-dev \
    && pecl install xdebug \
    && docker-php-ext-enable xdebug \
    && printf "\n" | pecl install apcu \
    && docker-php-ext-enable apcu \
    && docker-php-ext-install -j"$(nproc)" pdo bcmath zip gd sockets pdo_pgsql pgsql \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Пользовательский php.ini (если у тебя он есть в репозитории)
ADD ./php/php.ini /usr/local/etc/php/php.ini

# Установка Composer глобально
RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" \
    && php composer-setup.php --install-dir=/usr/local/bin --filename=composer \
    && php -r "unlink('composer-setup.php');"

# (Необязательно) Обновить корневой CA-бандл curl, если используешь кастомный cacert.pem
RUN curl --fail --remote-name --time-cond ./php/cacert.pem https://curl.se/ca/cacert.pem || true

ENV COMPOSER_ALLOW_SUPERUSER=1

WORKDIR /var/www
