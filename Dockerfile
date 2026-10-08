FROM php:8.3-apache

# Em Docker, somente frontend/ fica exposto por HTTP. backend/ permanece
# acessível aos includes PHP, mas fora do DocumentRoot público.
ENV APACHE_DOCUMENT_ROOT=/var/www/html/frontend

RUN docker-php-ext-install pdo_mysql \
    && a2enmod rewrite \
    && sed -ri -e "s!/var/www/html!${APACHE_DOCUMENT_ROOT}!g" /etc/apache2/sites-available/*.conf

WORKDIR /var/www/html
