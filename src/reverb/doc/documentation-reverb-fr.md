![Reverb.com](img/ban-home.jpg)

# Documentation du module Reverb - PrestaShop 1.6 & 1.7

Cette documentation a pour but d'aider le marchand à installer puis configurer le module Reverb sur PrestaShop.

## Sommaire

### Vous êtes developpeur ?

1. Pré-requis
2. Installer votre environement de développement 
3. Comment contribuer sur GitHub ?

### Vous êtes marchand ?

1. Pré-requis
2. Comment installer le module Reverb sur votre PrestaShop ?
3. Comment mettre à jour votre module Reverb ?
4. Connexion avec votre compte Reverb
5. Configurer les paramètres
6. Configurer le mapping des catégories 
7. Configurer vos produits
8. Gestion des synchronisations de vos produits
9. Gestion des synchronisation de vos commandes et stock
10. FAQ


## You are developer ?

### Pré-requis

Pour installer votre environement de test avec Docker, il vous faut :

* Docker (https://docs.docker.com/engine/installation/)
* Docker Compose (https://docs.docker.com/compose/)

### Installer votre environement de développement

Pour lancer un container avec un PrestaShop, une base données et un smtp pour recevoir les emails, il faut tout d'abord configurer les paramètres.
Pour PrestaShop 1.6, il faut éditer le fichier /conf/env/PRESTASHOP-16.env
Pour PrestaShop 1.7, il faut éditer le fichier /conf/env/PRESTASHOP-17.env


    ####################################
    ###     ENV SPECIFIC PRESTASHOP
    ####################################
    PS_DOMAIN=localhost:8016 
    DB_NAME=prestashop16
    DB_SERVER=mysql'

Vous pouvez modifier la variable PS_DOMAIN avec le nom de domaine que vous souhaitez, nous recommandons en local de rester sur le domaine localhost.


#### PrestaShop 1.6


    $ sh prestashop.sh init 16
    
Vous devez attendre quelques minutes pour que PrestaShop s'installe correctement.

#### PrestaShop 1.7


    $ sh prestashop.sh init 17
    
Vous devez attendre quelques minutes pour que PrestaShop s'installe correctement.

#### Liens pour accéder aux sites

**Avec la configuration par défaut:**

* Front office PrestaShop 1.6: http://localhost:8016/
* Front office PrestaShop 1.7: http://localhost:8017/
* Back office PrestaShop 1.6: http://localhost:8016/admin-reverb
* Back office PrestaShop 1.7: http://localhost:8017/admin-reverb
* SMTP Mail Catcher: http://localhost:1082/

**Pour accéder à la base de données, vous devez utiliser un logiciel comme MySQL Workbench et vous connectez avec les informations suivantes :**

* Hostname: localhost
* Port: 3317
* Username: root
* Password: admin

### Contribuer sur GitHub

**[ Reverb must be write his rules ]**

## Vous êtes marchand ?

### Pré-requis

* PrestaShop pré-requis : http://doc.prestashop.com/display/PS17/What+you+need+to+get+started
* Certificat SSL: votre domaine doit être en HTTPS
* Un token généré sur votre compte : FAQ Reverb sur https://help.reverb.com/hc/fr

### Comment installer le module Reverb sur votre PrestaShop ?

#### Addons de PrestaShop

Dans le Back office de PrestaShop, vous pouvez trouver le module Reverb dans la liste des modules.
Vous pouvez acheter le module et l'installer.

#### Fichier ZIP

Télécharger le fichier ZIP via votre compte PrestaShop sur l'addons.

Pour installer le module Reverb via le panel d'administration de votre PrestaShop: 

* PrestaShop 1.7: cliquez sur "_Modules > Modules & services > Importer un module_".
* PrestaShop 1.6: cliquez sur "_Modules & services > Modules & services > Ajouter un nouveau module_".

Choisissez votre fichier ZIP et cliquez sur "_Importer ce module_"

![Upload module](img/upload-module.png)
 
#### Par FTP

Vous devez avoir une logiciel de transfert de fichier comme "_FileZilla_" par exemple.

1. Ouvrez votre logiciel et connectez vous sur votre FTP (SFTP).
2. Allez à la racine de votre projet PrestaShop.
3. Transéfer les sources du module "_reverb_" le répertoire "_/modules/_".

![legend](img/ftp.png)

### Comment mettre à jour votre module Reverb ?


### Connexion avec votre compte Reverb


### Configurer les paramètres
### Configurer le mapping des catégories 
### Configurer vos produits
### Gestion des synchronisations de vos produits
### Gestion des synchronisation de vos commandes et stock
### FAQ