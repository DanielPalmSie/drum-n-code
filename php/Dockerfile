FROM php:8.1-fpm


RUN apt-get update && apt-get install -y \
    libzip-dev \
    libonig-dev \
    libpq-dev \
    && docker-php-ext-install pdo_mysql zip


RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer


RUN apt-get update && apt-get install -y default-mysql-client


WORKDIR /var/www/html


COPY . .


RUN chown -R www-data:www-data /var/www/html


EXPOSE 9000

CMD ["php-fpm"]


RUN apt-get update && apt-get install -y bash
