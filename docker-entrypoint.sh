#!/bin/sh

set -eu

runtime_paths="/var/www/html/storage /var/www/html/bootstrap/cache /var/www/html/public/assets"

for path in $runtime_paths; do
    mkdir -p "$path"
done

# Bind-mounted runtime directories can retain ownership from the host or from
# an earlier root-run CLI command. Repair them before PHP-FPM accepts traffic.
if [ "$(id -u)" -eq 0 ]; then
    for path in $runtime_paths; do
        chown -R www-data:www-data "$path"
        chmod -R ug+rwX "$path"
    done
fi

exec "$@"
