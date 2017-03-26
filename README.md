# Rever project

How to test the Reverb plugin ?

Command line :

<code>docker-compose build</code>

<code>docker-compose up -d</code>

Open a browse and go to http://localhost:8082/

Access to the Back Office http://localhost:8082/admin-reverb

Login / Password : demo@reverb.com / 123456

Access to reverb container

<code>docker exec -it reverb bash</code>

Compte Rerverb de sandbox:
https://sandbox.reverb.com
vincent.dossantos@gmail.com / reverb-test-account

Token : 5b520e1fc15b429b3f6693c03a3bafa09b536b0b8e00db9c1cc746c12ff44f71

A récupérer ici : https://sandbox.reverb.com/my/api_settings

6-view-sync-status
ALTER TABLE ps_reverb_sync ADD COLUMN url_reverb varchar(150);

7-view-mapping-categories
ALTER TABLE ps_reverb_mapping MODIFY reverb_code varchar(50);

6-view-sync-status V2
see conf/sql/changes.sql

To launch sync CRON from container:
<code>php /var/www/html/modules/reverb/crons/reverb-products-sync.php</code>