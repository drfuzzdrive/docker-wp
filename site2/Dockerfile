FROM php:7.4-fpm-alpine
# RUN docker-php-ext-install mysqli pdo_mysql
RUN sed -i 's/9000/9001/' /usr/local/etc/php-fpm.d/zz-docker.conf
COPY "./site2" "/var/www/ya-to.online/site2"
WORKDIR /var/www
USER www-data
EXPOSE 9001
CMD ["php-fpm"]
 
