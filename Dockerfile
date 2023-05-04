
FROM php:8.2-alpine3.16
RUN docker-php-ext-install pdo_mysql
RUN docker-php-ext-install pcntl
RUN docker-php-ext-install posix

COPY --from=composer /usr/bin/composer /usr/bin/composer

COPY . /usr/src/myapp
WORKDIR /usr/src/myapp

RUN composer install

CMD ["php", "./index.php"]