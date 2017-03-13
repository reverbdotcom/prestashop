# Rever project

How to test the Reverb plugin ?

Command line :

<code>docker-compose build</code>

<code>docker-compose up -d</code>

This will create 2 containers :

One on Prestashop 1.6 available on http://localhost:8016

One on Prestashop 1.7 available on http://localhost:8017

Access to the Back Office :
http://localhost:8016/admin-reverb
OR
http://localhost:8017/admin-reverb

Login / Password : demo@reverb.com / 123456

Access to reverb container

<code>docker exec -it reverb16 bash</code>

or just 

<code>./bash16.sh</code>


OR

<code>docker exec -it reverb17 bash</code>


or just 

<code>./bash17.sh</code>

Sandbox Rerverb account :
https://sandbox.reverb.com
vincent.dossantos@gmail.com / reverb-test-account

Token : 5b520e1fc15b429b3f6693c03a3bafa09b536b0b8e00db9c1cc746c12ff44f71

Get token from https://sandbox.reverb.com/my/api_settings
