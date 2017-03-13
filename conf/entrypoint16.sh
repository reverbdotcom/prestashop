#!/bin/sh -e

# Create DB
echo "\n CREATE DATABASE IF NOT EXISTS $DB_NAME \n";
mysql -h mysql -u $DB_USER -p$DB_PASSWD -e "CREATE DATABASE IF NOT EXISTS $DB_NAME"

#===================================#
#       CALL PARENT ENTRYPOINT
#===================================#

echo "\n* Starting internal MySQL server ...";

echo "\n /!\ WARNING : The MySQL server will be shortly removed from this container !"
echo "\n /!\ An external server will be required."
service mysql start
if [ $DB_PASSWD != "" ] && [ ! -f ./config/settings.inc.php  ]; then
    echo "\n* Grant access to MySQL server ...";
    mysql -h $DB_SERVER -P $DB_PORT -u $DB_USER -p$DB_PASSWD --execute="GRANT ALL ON *.* to $DB_USER@'localhost' IDENTIFIED BY '$DB_PASSWD'; " 2> /dev/null;
    mysql -h $DB_SERVER -P $DB_PORT -u $DB_USER -p$DB_PASSWD --execute="GRANT ALL ON *.* to $DB_USER@'%' IDENTIFIED BY '$DB_PASSWD'; " 2> /dev/null;
    mysql -h $DB_SERVER -P $DB_PORT -u $DB_USER -p$DB_PASSWD --execute="flush privileges; " 2> /dev/null;
fi

echo "\n Execution PRESTASHOP Entrypoint \n";
/tmp/docker_run.sh

#===================================#
#       CUSTOMS CONFIGURATIONS
#===================================#

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
