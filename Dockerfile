FROM php:8.3-apache

ENV PHP_INI_PATH "/usr/local/etc/php/php.ini"

RUN docker-php-ext-install pdo pdo_mysql

RUN echo "log_errors = On" >> ${PHP_INI_PATH} \
  && echo "error_reporting = E_ALL" >> ${PHP_INI_PATH} \
  && echo "error_log=/var/www/php_error.log" >> ${PHP_INI_PATH}

RUN pecl install xdebug-3.2.1 && docker-php-ext-enable xdebug \
    && echo "zend_extension=$(find /usr/local/lib/php/extensions/ -name xdebug.so)" >> ${PHP_INI_PATH} \
    && echo "xdebug.mode=debug" >> ${PHP_INI_PATH} \
    && echo "xdebug.client_port=9003" >> ${PHP_INI_PATH} \
    && echo "xdebug.client_host=host.docker.internal" >> ${PHP_INI_PATH} \
    && echo "xdebug.start_with_request=1" >> ${PHP_INI_PATH} \
    && echo "xdebug.log=/tmp/xdebug.log" >> ${PHP_INI_PATH} \
    && echo "xdebug.idekey=IDEA_YAP_DEBUG" >> ${PHP_INI_PATH}

RUN a2enmod rewrite expires
