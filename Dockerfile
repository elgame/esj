FROM php:7.0-apache

# install all the dependencies and enable PHP modules
RUN apt-get update && apt-get upgrade -y && apt-get install -y \
      procps \
      nano \
      git \
      unzip \
      libicu-dev \
      zlib1g-dev \
      libxml2 \
      libxml2-dev \
      libreadline-dev \
      supervisor \
      cron \
      libzip-dev \
    && docker-php-ext-install \
      pgsql \
      intl \
      zip && \
      rm -fr /tmp/* && \
      rm -rf /var/list/apt/* && \
      rm -r /var/lib/apt/lists/* && \
      apt-get clean