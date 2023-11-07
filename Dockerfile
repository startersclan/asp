ARG IMAGE=php:7.4-fpm-alpine
FROM $IMAGE AS build

# Set permissions for 'www-data' user
COPY ./src/ASP /src/ASP
WORKDIR /src/ASP
RUN chown -R www-data:www-data . \
    && find . -type d -exec chmod 750 {} \; \
    && find . -type f -exec chmod 640 {} \;

FROM $IMAGE AS dev

# Install nginx and supervisor for multi-process container
RUN apk add --no-cache ca-certificates nginx supervisor

# opcache
RUN docker-php-ext-install opcache

# mysql PDO
RUN docker-php-ext-install pdo pdo_mysql

# Xdebug: https://stackoverflow.com/questions/46825502/how-do-i-install-xdebug-on-dockers-official-php-fpm-alpine-image
# PHPIZE_DEPS: autoconf dpkg-dev dpkg file g++ gcc libc-dev make pkgconf re2c
RUN apk add --no-cache --virtual .build-dependencies $PHPIZE_DEPS \
    && pecl install xdebug-3.1.6 \
    && docker-php-ext-enable xdebug \
    && docker-php-source delete \
    && apk del .build-dependencies
RUN { \
        echo "[xdebug]"; \
        echo "zend_extension=xdebug"; \
        echo "xdebug.mode=debug"; \
        echo "xdebug.start_with_request=yes"; \
        echo "xdebug.client_host=host.docker.internal"; \
        echo "xdebug.client_port=9000"; \
    } > /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini;

RUN set -eux; \
    echo; \
    php -i; \
    php -m

# Add configs
COPY ./config/ASP/. /
COPY ./src/ASP/system/config /config.sample
RUN chmod +x /docker-entrypoint.sh;
# Disable the built-in php-fpm configs, since we're using our own config
RUN set -eux; \
    mv -v /usr/local/etc/php-fpm.d/docker.conf /usr/local/etc/php-fpm.d/docker.conf.disabled; \
    mv -v /usr/local/etc/php-fpm.d/www.conf /usr/local/etc/php-fpm.d/www.conf.disabled; \
    mv -v /usr/local/etc/php-fpm.d/zz-docker.conf /usr/local/etc/php-fpm.d/zz-docker.conf.disabled;

# In docker, IPs may be dynamic. This ensures we get access
ENV ADMIN_HOSTS=0.0.0.0/0
VOLUME /src/ASP/system/backups
VOLUME /src/ASP/system/cache
VOLUME /src/ASP/system/config
VOLUME /src/ASP/system/logs
VOLUME /src/ASP/system/snapshots
EXPOSE 80
EXPOSE 9000
WORKDIR /src/ASP
ENTRYPOINT []
CMD ["/docker-entrypoint.sh"]

FROM dev AS prod

# Disable xdebug
RUN set -eux; \
    rm /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini; \
    php -m;

COPY --from=build /src /src
