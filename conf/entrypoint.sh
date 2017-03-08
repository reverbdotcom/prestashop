#!/bin/sh -e

#===================================#
#       CALL PARENT ENTRYPOINT
#===================================#
echo "\n Parent Entrypoint \n";
/tmp/docker_run.sh

#===================================#
#       CUSTOMS CONFIGURATIONS
#===================================#
if [ ! -f /var/www/html/console/console.php ];then
    echo "\n Installation prestashop Console \n";
    cd /var/www/html/ \
    && git clone https://github.com/nenes25/prestashop_console.git console \
    && cd console \
    && composer install


    # Installation  Reverb's module
    echo "\n Installation Reverb's module \n";
    php console.php module:install reverb
fi

#===================================#
#       START WEBSERVER
#===================================#
echo "\n* Starting Apache now\n";
exec apache2-foreground
