#!/bin/sh
set -eu

port="${PORT:-10000}"

case "$port" in
    ''|*[!0-9]*)
        echo "PORT debe ser un número válido." >&2
        exit 1
        ;;
esac

sed -ri "s/^Listen 80$/Listen ${port}/" /etc/apache2/ports.conf
sed -ri "s#^<VirtualHost \*:80>#<VirtualHost *:${port}>#" \
    /etc/apache2/sites-available/000-default.conf

storage_root="${CONVIVIUM_STORAGE_ROOT:-/var/data/convivium}"
mkdir -p "$storage_root"
chown www-data:www-data "$storage_root"

# Dejar habilitado solo el MPM compatible con PHP mod_php.
a2dismod mpm_event >/dev/null 2>&1 || true
a2dismod mpm_worker >/dev/null 2>&1 || true
rm -f /etc/apache2/mods-enabled/mpm_event.* \
      /etc/apache2/mods-enabled/mpm_worker.*
a2enmod mpm_prefork >/dev/null

apache2ctl -t

exec docker-php-entrypoint "$@"