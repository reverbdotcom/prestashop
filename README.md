# Rever project

How to test the Reverb plugin ?

Command line :

<code>docker-compose build</code>

<code>docker-compose up -d</code>

Open a browse and go to http://localhost:8016
Open a browse and go to http://localhost:8017

Access to the Back Office http://localhost:8016/admin-reverb
Access to the Back Office http://localhost:8017/admin-reverb

Login / Password : demo@reverb.com / 123456

Access to reverb container

<code>docker exec -it reverb bash</code>

Compte Rerverb de sandbox:
https://sandbox.reverb.com
vincent.dossantos@gmail.com / reverb-test-account

Token : 5b520e1fc15b429b3f6693c03a3bafa09b536b0b8e00db9c1cc746c12ff44f71

A récupérer ici : https://sandbox.reverb.com/my/api_settings

7-view-mapping-categories
ALTER TABLE ps_reverb_mapping MODIFY reverb_code varchar(50);

6-view-sync-status V2
mysql -h $DB_SERVER -u $DB_USER -p$DB_PASSWD $DB_NAME < /tmp/sql/reverb_ps_product.sql

To launch sync CRON from container:
<code>php /var/www/html/modules/reverb/crons/reverb-products-sync.php</code>

curl -X POST -H "Authorization: Bearer 5b520e1fc15b429b3f6693c03a3bafa09b536b0b8e00db9c1cc746c12ff44f71" \
-H "Accept-Version: 3.0" -H \
"Content-Type: application/hal+json" \
https://sandbox.reverb.com/api/listings --data \
'{
   "make": "Fender",
   "model": "Stratocaster",
   "categories": [{
     "uuid": "af044b2e-88b9-4b3d-97f3-1d7b7c0831af"
   }],
   "condition": {
     "uuid": "fbf35668-96a0-4baa-bcde-ab18d6b1b329"
   },
   "photos": [
     "https://www.easyzic.com/common/datas/dossiers/6/6/acoustique-yamaha-c40-1.jpg"
   ],
   "description": "Awesome guitar",
   "finish": "Sunburst",
   "price": {
     "amount": "5000.00",
     "currency": "USD"
   },
   "title": "my favorite fender stratocaster",
   "year": "1960s",
   "sku": "vdossantos-guitar-4",
   "has_inventory": true,
   "inventory": 5,
   "handmade": true,
   "location": {
     "country_code": "USA",
     "region": "IL",
     "locality": "Chicago"
   },
   "shipping_profile_id": "210"
 }
'