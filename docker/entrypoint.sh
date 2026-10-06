#!/bin/sh
set -e

# Render tells the container which port to listen on
PORT="${PORT:-10000}"
sed -ri "s/^Listen .*/Listen ${PORT}/" /etc/apache2/ports.conf
sed -ri "s/<VirtualHost \*:[0-9]+>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf

# Create any missing tables before serving. A failure here stops the deploy
# instead of starting a site that cannot reach its database.
php bin/migrate.php

exec apache2-foreground
