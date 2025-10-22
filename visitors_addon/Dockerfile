ARG BUILD_FROM
FROM $BUILD_FROM

# Persisted working directory (Home Assistant maps this)
WORKDIR /data

# Install packages (add php83-sqlite3)
RUN apk -U upgrade && apk add --no-cache \
    curl \
    nginx \
    php83-fpm \
    php83-pdo \
    php83-pdo_sqlite \
    php83-sqlite3 \
    php83-session \
    sqlite \
 && ln -s /usr/sbin/php-fpm83 /usr/sbin/php-fpm \
 && addgroup -S php \
 && adduser -S -G php php \
 && rm -rf /var/cache/apk/* /etc/nginx/http.d/* /etc/php83/conf.d/* /etc/php83/php-fpm.d/*

# PHP extensions + runtime (auto-prepend our init script)
RUN { \
      echo "extension=pdo.so"; \
      echo "extension=pdo_sqlite.so"; \
      echo "extension=sqlite3.so"; \
    } > /etc/php83/conf.d/20-pdo_sqlite.ini \
 && { \
      echo "extension=session.so"; \
    } > /etc/php83/conf.d/21-session.ini \
 && { \
      echo 'variables_order = "EGPCS"'; \
      echo 'cgi.fix_pathinfo = 0'; \
      echo 'expose_php = Off'; \
      echo 'auto_prepend_file = "/www/public/init_db.php"'; \
    } > /etc/php83/conf.d/00-autoprepend.ini

# PHP-FPM pool (run as nginx)
RUN mkdir -p /etc/php83/php-fpm.d \
 && { \
      echo "[www]"; \
      echo "user = nginx"; \
      echo "group = nginx"; \
      echo "listen = /run/php/php-fpm.sock"; \
      echo "listen.owner = nginx"; \
      echo "listen.group = nginx"; \
      echo "pm = dynamic"; \
      echo "pm.max_children = 5"; \
      echo "pm.start_servers = 2"; \
      echo "pm.min_spare_servers = 1"; \
      echo "pm.max_spare_servers = 3"; \
      echo "clear_env = no"; \
      echo "env[DATABASE_PATH] = /data/visitor_signin.db"; \
    } > /etc/php83/php-fpm.d/www.conf

# Nginx default site (root = /www/public; block direct .db access)
RUN { \
      echo "server {"; \
      echo "  listen 80 default_server;"; \
      echo "  server_name _;"; \
      echo "  root /www/public;"; \
      echo "  index index.php index.html;"; \
      echo "  location ~ \\.db\\$ { deny all; }"; \
      echo "  location / { try_files \\$uri /index.php?\\$args; }"; \
      echo "  location ~ \\.php\\$ {"; \
      echo "    include fastcgi_params;"; \
      echo "    fastcgi_param SCRIPT_FILENAME \\$document_root\\$fastcgi_script_name;"; \
      echo "    fastcgi_pass unix:/run/php/php-fpm.sock;"; \
      echo "  }"; \
      echo "}"; \
    } > /etc/nginx/http.d/default.conf

# Copy your project (must include /www and any s6 services)
COPY files/general files/php83 /

# ✅ Normalize s6 scripts (strip CRLF) and ensure executables + runtime perms
RUN set -eux; \
  # Fix line endings & executable bit for s6 service scripts
  if [ -d /etc/services.d ]; then \
    find /etc/services.d -type f \( -name run -o -name finish \) -print0 2>/dev/null \
      | xargs -0 -r -I{} sh -c "sed -i 's/\r$//' {}; chmod +x {}"; \
  fi; \
  # Do the same for any cont-init.d scripts you may add
  if [ -d /etc/cont-init.d ]; then \
    find /etc/cont-init.d -type f -print0 \
      | xargs -0 -r -I{} sh -c "sed -i 's/\r$//' {}; chmod +x {}"; \
  fi; \
  # Ensure runtime dirs and sane permissions
  mkdir -p /run/nginx /run/php /www/public; \
  chown -R nginx:nginx /run/nginx /run/php /www /data; \
  chmod -R 775 /data; \
  chmod -R 755 /www

ENTRYPOINT ["/init"]
