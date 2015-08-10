FROM php:5.4-apache

ENV APACHE_SERVER_NAME=docker.local
ENV APACHE_SERVER_ALIAS=docker
RUN mkdir /etc/apache2/logs


RUN /bin/ln -sf /etc/apache2/mods-available/rewrite.load /etc/apache2/mods-enabled/rewrite.load
RUN alias ll='ls -la'

COPY config/php.ini /usr/local/etc/php/conf.d/
COPY apache_conf/vhosts/ /etc/apache2/sites-enabled/

EXPOSE 80
EXPOSE 443

CMD ["/usr/sbin/apache2", "-D", "FOREGROUND"]
