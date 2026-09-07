#!/bin/bash
set -e

a2dismod mpm_event 2>/dev/null || true
a2dismod mpm_worker 2>/dev/null || true
a2dismod mpm_prefork 2>/dev/null || true
a2enmod mpm_prefork

php bin/console doctrine:migrations:migrate --no-interaction || true
php bin/console asset-map:compile
php bin/console cache:warmup

exec apache2-foreground