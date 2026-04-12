FROM php:8.2-cli

RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libonig-dev \
    libxml2-dev \
    libicu-dev \
    libcurl4-openssl-dev \
    default-mysql-client \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install \
        pdo \
        pdo_mysql \
        mysqli \
        gd \
        zip \
        intl \
        mbstring \
        exif \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

COPY ./docker/php/php.ini /usr/local/etc/php/conf.d/99-local.ini

RUN ln -sf /usr/local/bin/php /usr/bin/php

WORKDIR /var/www/html