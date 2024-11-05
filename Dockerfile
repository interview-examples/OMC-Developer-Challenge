FROM php:8.3-cli

LABEL authors="vitaly_root"

RUN apt-get update && apt-get install -y libssl-dev

RUN pecl install mongodb && docker-php-ext-enable mongodb

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

COPY . /app

WORKDIR /app

RUN mkdir -p /app/logs && chmod -R 777 /app/logs

RUN composer install

CMD [ "php", "-S", "0.0.0.0:80", "-t", "public" ]
