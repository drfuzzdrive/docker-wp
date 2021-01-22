FROM php:7.4-fpm-alpine
# RUN docker-php-ext-install mysqli pdo_mysql
COPY "./site1" "/var/www/ya-to.online/site1"
WORKDIR /var/www
USER www-data
EXPOSE 9000
CMD ["php-fpm"]
 
