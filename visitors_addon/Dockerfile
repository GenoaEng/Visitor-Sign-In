ARG BUILD_FROM
FROM $BUILD_FROM

WORKDIR /data

RUN apk -U upgrade && apk add --no-cache \
    curl \
    nginx \
    php83-fpm \
    php83-pdo \
    php83-pdo_sqlite \
    php83-session \
    sqlite \
    && ln -s /usr/sbin/php-fpm83 /usr/sbin/php-fpm \
    && addgroup -S php \
    && adduser -S -G php php \
    && rm -rf /var/cache/apk/* /etc/nginx/http.d/* /etc/php83/conf.d/* /etc/php83/php-fpm.d/* \
    && { \
        echo "extension=pdo.so"; \
        echo "extension=pdo_sqlite.so"; \
    } > /etc/php83/conf.d/20-pdo.ini \
    && { \
        echo "extension=session.so"; \
    } > /etc/php83/conf.d/21-session.ini

COPY files/general files/php83 /

# --- FIX: make s6 service scripts executable and strip CRLF ---
RUN chmod 0755 \
      /etc/services.d/nginx/run /etc/services.d/nginx/finish /etc/services.d/nginx/type \
      /etc/services.d/php-fpm83/run /etc/services.d/php-fpm83/finish /etc/services.d/php-fpm83/type \
 && sed -i 's/\r$//' \
      /etc/services.d/nginx/run /etc/services.d/nginx/finish /etc/services.d/nginx/type \
      /etc/services.d/php-fpm83/run /etc/services.d/php-fpm83/finish /etc/services.d/php-fpm83/type

# --- RECOMMENDED: ensure web root is writable by runtime user ---
RUN chown -R nginx:nginx /www/public \
 && chmod -R u+rwX,g+rX /www/public

# --- OPTIONAL: run php-fpm as nginx instead of root (less perms issues) ---
RUN sed -i \
      -e 's/^user = .*/user = nginx/' \
      -e 's/^group = .*/group = nginx/' \
      /etc/php83/php-fpm.conf

# (original line) keep if you want to set explicit owner on the shipped DB file
RUN chown php:nginx /www/public/visitor_signin.db || true

ENTRYPOINT ["/init"]
