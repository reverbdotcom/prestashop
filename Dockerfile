FROM prestashop/prestashop:1.7.0.5

MAINTAINER Johan PROTIN <jprotin@boutiquedubio.fr>

RUN apt-get update \
        && apt-get install -y ssmtp \
                && echo "sendmail_path = /usr/sbin/ssmtp -t" > /usr/local/etc/php/conf.d/sendmail.ini \
                        && echo "mailhub=smtp:1025\nUseTLS=NO\nFromLineOverride=YES" > /etc/ssmtp/ssmtp.conf

COPY conf /tmp
COPY src /var/www/html
RUN sed -i "/exec apache2 -DFOREGROUND/d" /tmp/docker_run.sh \
    && sed -i "/Almost ! Starting Apache now/d" /tmp/docker_run.sh \
        && chmod 777 -R /tmp

ENTRYPOINT ["/tmp/entrypoint.sh"]
