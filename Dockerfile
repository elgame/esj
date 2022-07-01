FROM php:7.0-apache

# install all the dependencies and enable PHP modules
RUN apt-get update && apt-get upgrade -y && apt-get install -y \
      procps \
      nano \
      git \
      unzip \
      libicu-dev \
      libpq-dev \
      libpng-dev \
      zlib1g-dev \
      libxml2 \
      libxml2-dev \
      libreadline-dev \
      supervisor \
      cron \
      libzip-dev

# Install PHP extensions
RUN docker-php-ext-install pgsql bcmath gd intl xml soap hash zip dom session opcache

RUN rm -fr /tmp/* && \
      rm -rf /var/list/apt/* && \
      rm -r /var/lib/apt/lists/* && \
      apt-get clean

# enable apache modules
RUN a2enmod rewrite

USER www-data

EXPOSE 80
CMD echo "ServerName localhost" >> /etc/apache2/apache2.conf
CMD service apache2 restart
