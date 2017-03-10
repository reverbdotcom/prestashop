FROM prestashop/prestashop:1.7.0.6

MAINTAINER Johan PROTIN <jprotin@boutiquedubio.fr>

RUN apt-get update \
        && apt-get install -y ssmtp vim git \
                && curl -sS https://getcomposer.org/installer | php -- --filename=composer -- --install-dir=/usr/local/bin \
                && echo "sendmail_path = /usr/sbin/ssmtp -t" > /usr/local/etc/php/conf.d/sendmail.ini \
                && echo '' && pecl install xdebug \
                    &&   echo "zend_extension=$(find /usr/local/lib/php/extensions/ -name xdebug.so)" > /usr/local/etc/php/conf.d/xdebug.ini \
                        && echo "mailhub=smtp:1025\nUseTLS=NO\nFromLineOverride=YES" > /etc/ssmtp/ssmtp.conf \
                        &&  rm -rf /var/lib/apt/lists/*

COPY conf /tmp

COPY src /var/www/html

RUN sed -i "/exec apache2-foreground/d" /tmp/docker_run.sh \
    && sed -i "/Almost ! Starting Apache now/d" /tmp/docker_run.sh \
        && chmod 777 -R /tmp

ENTRYPOINT ["/tmp/entrypoint.sh"]
