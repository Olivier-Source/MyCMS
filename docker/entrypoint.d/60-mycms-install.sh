#!/bin/sh
# Installs the starter content and the first administrator when the database
# is empty (no effect afterwards). Runs after the automatic migrations of the
# serversideup/php image.
if [ "${AUTORUN_ENABLED:-false}" = "true" ]; then
    php /var/www/html/artisan mycms:install --no-interaction \
        --locale="${MYCMS_LOCALE:-en}" \
        --site-name="${MYCMS_SITE_NAME:-My Website}" \
        ${MYCMS_ADMIN_EMAIL:+--email="$MYCMS_ADMIN_EMAIL" --admin-email="$MYCMS_ADMIN_EMAIL"} \
        || echo "MyCMS: installation step failed, see the messages above."
fi
