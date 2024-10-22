FROM php:8.2-apache

ARG APP_INSTALL_DIR

RUN apt-get update -y && apt-get install -y libicu-dev nano

#COPY ./app/conf ${APP_INSTALL_DIR}/app/conf

# Set php.ini file
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql intl && echo "Packages installed"

# Enable Apache module
RUN a2enmod rewrite

ENV APACHE_DOCUMENT_ROOT ${APP_INSTALL_DIR}/public

RUN sed -i "s#^[[:blank:]]*DocumentRoot.*\$#DocumentRoot ${APP_INSTALL_DIR}#" /etc/apache2/sites-available/000-default.conf
#RUN sed -i "s#^[[:blank:]]*ServerName.*\$#ServerName ${APP_DOMAIN_NAME}#" /etc/apache2/sites-available/000-default.conf

#RUN mv "${APP_INSTALL_DIR}/app/conf/settings-dev.php" "${APP_INSTALL_DIR}/app/conf/settings.php"
#RUN sed -i "s#^[[:blank:]]*\\\$biusanteWebSiteBaseUrl.*\$#\\\$biusanteWebSiteBaseUrl = '${biusanteWebSiteBaseUrl}';#" ${APP_INSTALL_DIR}/app/conf/settings.php
#RUN sed -i "s#^[[:blank:]]*\\\$applicationBaseUrl.*\$#\\\$applicationBaseUrl = '${applicationBaseUrl}';#" ${APP_INSTALL_DIR}/app/conf/settings.php
