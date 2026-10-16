#!/bin/sh
# Container start for the Render Blueprint. Render's free tier has no
# preDeployCommand and no persistent disk, so everything that has to happen on
# boot happens here, every boot.
set -eu

cd /var/www/html

# Absolute URLs (TwiML callbacks, asset links) must point at the public host.
if [ -z "${APP_URL:-}" ] && [ -n "${RENDER_EXTERNAL_URL:-}" ]; then
    APP_URL="$RENDER_EXTERNAL_URL"
    export APP_URL
fi

# Render's generateValue yields a bare base64 string; Laravel wants "base64:".
if [ -n "${APP_KEY:-}" ] && [ "${APP_KEY#base64:}" = "$APP_KEY" ] \
    && [ "$(printf '%s' "$APP_KEY" | base64 -d 2>/dev/null | wc -c)" -eq 32 ]; then
    APP_KEY="base64:$APP_KEY"
    export APP_KEY
fi

# A provider CA pasted into the dashboard (Aiven signs with its own CA).
if [ -n "${MYSQL_SSL_CA_PEM:-}" ]; then
    printf '%s\n' "$MYSQL_SSL_CA_PEM" > /etc/ssl/certs/yap-mysql-ca.pem
    MYSQL_ATTR_SSL_CA=/etc/ssl/certs/yap-mysql-ca.pem
    export MYSQL_ATTR_SSL_CA
fi

# Hosted MySQL-compatible databases (TiDB Cloud, Aiven) require TLS. PDO only
# turns TLS on when a CA is given, so default to the system bundle.
if [ -z "${MYSQL_ATTR_SSL_CA:-}" ] && [ "${MYSQL_TLS:-true}" = "true" ]; then
    MYSQL_ATTR_SSL_CA=/etc/ssl/certs/ca-certificates.crt
    export MYSQL_ATTR_SSL_CA
fi

# 1. config.php from env vars (see yap-generate-config.php).
php /usr/local/bin/yap-generate-config.php /var/www/html/config.php
chown www-data:www-data config.php

# The filesystem is fresh on every boot; recreate what Laravel writes to.
mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

# 2. Migrations. A cold database (e.g. a serverless tier waking up) can refuse
# the first connections, so retry for a while before giving up.
attempt=1
until php artisan migrate --force --no-interaction; do
    if [ "$attempt" -ge "${MIGRATE_ATTEMPTS:-10}" ]; then
        echo "yap: migrations failed after $attempt attempts" >&2
        exit 1
    fi
    attempt=$((attempt + 1))
    echo "yap: migrate failed, retrying in 5s (attempt $attempt)" >&2
    sleep 5
done

# 3. Caches. Built after config.php exists so the cached config holds the real
# DB credentials. Routes are cached too: SettingsService::featureEnabledAtBoot()
# reads the experimental feature flags at registration, which is fine because
# config.php only changes on a restart, and a restart rebuilds this cache.
php artisan config:cache
php artisan route:cache
php artisan view:cache
chown -R www-data:www-data storage bootstrap/cache

# 4. Apache in the foreground.
exec apache2-foreground
