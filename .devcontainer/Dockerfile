FROM php:8.2-apache

# Enable Apache rewrite
RUN a2enmod rewrite

# Install system packages & PHP extensions
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    zip \
    unzip \
    git \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_mysql zip gd
RUN docker-php-ext-install calendar

WORKDIR /var/www/html

RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

#FROM ubuntu:latest

#ENV DEBIAN_FRONTEND=noninteractive

#RUN apt update -y \
#&& apt install -y apache2 && service apache2 start \
#&& apt install -y php libapache2-mod-php php-mbstring php-cli php-bcmath php-json php-xml php-zip php-pdo php-common php-tokenizer php-mysql unzip php-curl curl gnupg \
#&& curl -fsSL https://deb.nodesource.com/setup_18.x | bash - \
#&& apt install -y nodejs \
#&& curl -sS https://getcomposer.org/installer | php \
#&& mv composer.phar /usr/local/bin/composer \
#&& service apache2 restart






