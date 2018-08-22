FROM php:7.2.8-apache

RUN echo "log_errors = On" >> /usr/local/etc/php/php.ini \
  && echo "error_reporting = E_ALL" >> /usr/local/etc/php/php.ini
