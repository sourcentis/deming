FROM php:8.4-fpm-bookworm

ARG APP_VERSION=dev
LABEL org.opencontainers.image.version="${APP_VERSION}"

# Installer Nginx et dépendances
RUN apt-get update \
  && apt-get install -y --no-install-recommends \
     nginx \
     git \
     mariadb-client \
     cron \
     libzip-dev \
     libpng-dev \
     libldap2-dev \
     libpq-dev \
     libicu-dev \
  && docker-php-ext-install \
     pdo_mysql \
     pdo_pgsql \
     zip \
     gd \
     ldap \
     intl \
  && docker-php-ext-enable opcache \
  && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
  && rm -rf /var/lib/apt/lists/*

RUN touch /etc/mailname
RUN echo "sender@yourdomain.org" > /etc/mailname
RUN echo "* * * * * root cd /var/www/deming && php artisan schedule:run >> /dev/null 2>&1" >> /etc/crontab
RUN useradd -ms /bin/bash deming
RUN mkdir -p /var/www/deming

WORKDIR /var/www/deming

COPY . .

RUN cp docker/deming.conf /etc/nginx/conf.d/deming.conf \
 && cp docker/userdemo.sh /etc/userdemo.sh \
 && cp docker/resetdb.sh /etc/resetdb.sh \
 && cp docker/uploadiso27001db.sh /etc/uploadiso27001db.sh \
 && cp docker/initialdb.sh /etc/initialdb.sh \
 && chmod +x /etc/*.sh
RUN mkdir -p storage/framework/views && mkdir -p storage/framework/cache && mkdir -p storage/framework/sessions && mkdir -p bootstrap/cache
RUN chmod -R 775 /var/www/deming/storage && chown -R www-data:www-data /var/www/deming
RUN composer install
RUN php artisan vendor:publish --all

RUN cp .env.example .env
RUN sed -i 's/DB_HOST=127\.0\.0\.1/DB_HOST=mysql/' .env

RUN cp docker/entrypoint.sh /opt/entrypoint.sh && chmod u+x /opt/entrypoint.sh

EXPOSE 80

ENTRYPOINT ["/opt/entrypoint.sh"]
