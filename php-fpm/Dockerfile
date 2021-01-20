FROM php:7.4-fpm-alpine
RUN docker-php-ext-install mysqli pdo_mysql
WORKDIR /var/www
USER www-data
EXPOSE 9000
CMD ["php-fpm"]
 
