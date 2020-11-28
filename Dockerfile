FROM php:7.3-apache

ENV PHP_INI_PATH "/usr/local/etc/php/php.ini"
ENV APACHE_DOCUMENT_ROOT "/var/www/html/public"

RUN docker-php-ext-install pdo pdo_mysql

RUN echo "log_errors = On" >> ${PHP_INI_PATH} \
  && echo "error_reporting = E_ALL" >> ${PHP_INI_PATH} \
  && echo "error_log=/var/www/php_error.log" >> ${PHP_INI_PATH}

RUN pecl install xdebug-2.9.8 && docker-php-ext-enable xdebug \
    && echo "zend_extension=$(find /usr/local/lib/php/extensions/ -name xdebug.so)" >> ${PHP_INI_PATH} \
    && echo "xdebug.remote_port=9000" >> ${PHP_INI_PATH} \
    && echo "xdebug.remote_enable=1" >> ${PHP_INI_PATH} \
    && echo "xdebug.remote_connect_back=0" >> ${PHP_INI_PATH} \
    && echo "xdebug.remote_host=docker.for.mac.localhost" >> ${PHP_INI_PATH} \
    && echo "xdebug.idekey=IDEA_YAP_DEBUG" >> ${PHP_INI_PATH} \
    && echo "xdebug.remote_autostart=1" >> ${PHP_INI_PATH} \
    && echo "xdebug.remote_log=/tmp/xdebug.log" >> ${PHP_INI_PATH}

RUN apt-get update && apt-get install sed \
    && sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

RUN a2enmod rewrite expires
