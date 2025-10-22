ARG BUILD_FROM
FROM $BUILD_FROM

# Persisted working directory (Home Assistant maps this)
WORKDIR /data

# Install PHP + SQLite (no nginx, no php-fpm)
RUN apk -U upgrade && apk add --no-cache \
      php83 \
      php83-session \
      php83-pdo \
      php83-pdo_sqlite \
      php83-sqlite3 \
      sqlite \
  && rm -rf /var/cache/apk/* /etc/php83/conf.d/*

# PHP config (SQLite + sessions + sensible defaults)
RUN { \
      echo "extension=pdo.so"; \
      echo "extension=pdo_sqlite.so"; \
      echo "extension=sqlite3.so"; \
    } > /etc/php83/conf.d/20-sqlite.ini \
 && { \
      echo "extension=session.so"; \
    } > /etc/php83/conf.d/21-session.ini \
 && { \
      echo 'variables_order = "EGPCS"'; \
      echo 'expose_php = Off'; \
      echo 'cgi.fix_pathinfo = 0'; \
      # Auto-run DB bootstrap before any request
      echo 'auto_prepend_file = "/www/public/init_db.php"'; \
    } > /etc/php83/conf.d/00-autoprepend.ini

# Copy your project (must include /www/public and init_db.php + seed DB)
COPY files/general /          # -> /www/public/... etc.

# Provide a single s6 service called "app" (PHP built-in server)
# We create the scripts here to guarantee LF endings + exec bit
RUN set -eux; \
  mkdir -p /etc/services.d/app; \
  cat > /etc/services.d/app/run <<'SH'; \
#!/usr/bin/with-contenv sh
set -e
# Make sure web root exists
mkdir -p /www/public
# Start PHP built-in server
exec php -S 0.0.0.0:80 -t /www/public
SH
  cat > /etc/services.d/app/finish <<'SH'; \
#!/usr/bin/with-contenv sh
exit 0
SH
  chmod +x /etc/services.d/app/run /etc/services.d/app/finish; \
  # Make sure /data is writable by default (HA will mount it)
  chmod -R 775 /data

ENTRYPOINT ["/init"]
