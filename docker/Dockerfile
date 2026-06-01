# Dev image for the `app` service. Adds the PHP extensions Filament/Laravel
# need that the base serversideup CLI image does not ship (notably intl).
FROM serversideup/php:8.4-cli

USER root
RUN install-php-extensions intl pdo_sqlite
USER www-data
