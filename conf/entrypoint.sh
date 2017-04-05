#!/bin/sh -e

#===================================#
#       CALL PARENT ENTRYPOINT
#===================================#
echo "\n Execution PRESTASHOP Entrypoint \n";
/tmp/docker_run.sh

#===================================#
#       CUSTOMS CONFIGURATIONS
#===================================#
if [ ! -f /var/www/html/console/console.php ];then
    echo "\n Installation Prestashop Console \n";
    cd /var/www/html/ \
    && git clone https://github.com/nenes25/prestashop_console.git console \
    && cd console \
    && composer install

    # Installation  Reverb's module
    echo "\n Installation Reverb's module \n";
    php console.php module:install reverb

    #===================================#
    #            ADD CRON
    #===================================#
    crontab -l | { cat; echo "*/5 * * * *  php /var/www/html/modules/reverb/cron.php product > /var/log/cron.log"; } | crontab -
    crontab -l | { cat; echo "*/8 * * * *  php /var/www/html/modules/reverb/cron.php orders > /var/log/cron.log"; } | crontab -
    service cron start
fi

echo "Import data = $IMPORT_SAMPLE_DATA"

# Dump sample datas
#if [ $IMPORT_SAMPLE_DATA ];then
#    echo "\n Dump sample datas  \n";
#    mysql -h mysql -u $DB_USER -p$DB_PASSWD $DB_NAME < /tmp/sql/reverb_ps_product.sql
#fi

if [ $ACTIVE_XDEBUG ];then
    echo "xdebug.remote_enable=on" >> /usr/local/etc/php/conf.d/xdebug.ini
    echo "xdebug.remote_autostart=off" >> /usr/local/etc/php/conf.d/xdebug.ini
fi

#===================================#
#       START WEBSERVER
#===================================#
echo "\n* Starting Apache now\n";
exec apache2-foreground
