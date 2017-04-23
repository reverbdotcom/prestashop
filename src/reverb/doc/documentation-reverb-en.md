![Reverb.com](img/ban-home.jpg)

# Documentation for the Reverb module - PrestaShop 1.6 & 1.7

This documentation is a guide for the merchant to install and configure the module PrestaShop.

## Summary

### You are developer ?

1. Prerequisites
2. Install your Develop environment 
3. Contribute in GitHub

### You are Merchant ?

1. Prerequisites
2. How install the Reverb module in your PrestaShop ?
3. How do you update the Reverb module ?
4. Login with your Reverb account
5. Configure the settings
6. Configure the mapping categories 
7. Configure your products
8. Sync management of products
9. Sync management of Orders and inventory
10. FAQ


## You are developer ?

### Prerequisites

To install your test environment with Docker, you need to:

* Docker (https://docs.docker.com/engine/installation/)
* Docker Compose (https://docs.docker.com/compose/)

### Install your Develop environment

To launch a container with a PrestaShop, a database and an SMTP to receive emails, you must first configure the settings.
For PrestaShop 1.6, you need to edit the file /conf/env/PRESTASHOP-16.env
For PrestaShop 1.7, you need to edit the file /conf/env/PRESTASHOP-17.env


    ####################################
    ###     ENV SPECIFIC PRESTASHOP
    ####################################
    PS_DOMAIN=localhost:8016 
    DB_NAME=prestashop16
    DB_SERVER=mysql'

You can change the PS_DOMAIN variable with the domain name you want, we recommend in the local environment to stay on the localhost domain.


#### PrestaShop 1.6


    $ sh prestashop.sh init 16
    
You must wait a few minutes for the PrestaShop to install.

#### PrestaShop 1.7


    $ sh prestashop.sh init 17
    
You must wait a few minutes for the PrestaShop to install.

#### Links to access websites

**With the default configuration:**

* Front office PrestaShop 1.6: http://localhost:8016/
* Front office PrestaShop 1.7: http://localhost:8017/
* Back office PrestaShop 1.6: http://localhost:8016/admin-reverb
* Back office PrestaShop 1.7: http://localhost:8017/admin-reverb
* SMTP Mail Catcher: http://localhost:1082/

**To access the database, you must use software such as MySQL Workbench and connect with the following information:**

* Hostname: localhost
* Port: 3317
* Username: root
* Password: admin

### Contribute in GitHub

**[ Reverb must be write his rules ]**

## You are Merchant ?

### Prerequisites

* PrestaShop prerequisites: http://doc.prestashop.com/display/PS17/What+you+need+to+get+started
* Certificat SSL: your domain must be in HTTPS
* Token generated on your Reverb account: Reverb FAQ https://help.reverb.com/hc/en-us

### How install the Reverb module in your PrestaShop ?

#### PrestaShop Addons 

In the Back office of the PrestaShop, you can find the Reverb module in the list of the modules.
You can buy the module and install it.

#### ZIP package

Download the package in the PrestaShop addons.

To install it in your PrestaShop administrator back office:

* PrestaShop 1.7: click on "_Modules > Modules & services > Upload a module_".
* PrestaShop 1.6: click on "_Modules & services > Modules & services > Add a new module_".

Choose the package and click on "_Upload this module_".

![Upload module](img/upload-module.png)
 
#### By FTP

You must have a file transfer software like "_FileZilla_" for example.

1. Open your software and connect to your FTP (SFTP).
2. Go to the root of your PrestaShop project.
3. Transfer the "_reverb_" source module in the "_/modules/_" folder.

![legend](img/ftp.png)

### How do you update the Reverb module ?

When Reverb updates the module on the addons of PrestaShop, you will be offered a module update in the list of modules in your PrestaShop Back Office.
Else via GitHub or downloading the ZIP file, apply the same methodology as the point "_By FTP_" or "_ZIP package_".

### Login with your Reverb account

You should connect the module with Reverb via a Token that you have previously generated on your Reverb space.

#### How to configure the Reverb module ?

Configure in PrestaShop 1.6 : _Modules & services > modules & services > Fin the Reverb module > Configure_
Configure in PrestaShop 1.7 : _Modules > Modules & services > installed > Reverb > Configure_

#### Login

Select the sandbox mode or production and please enter your Reverb Token.

![login](img/login.png)

### Configure the settings

Configure the different rules in the product sync :

![configure settings](img/settings.png)

The field _PayPal Email_ can associate your PayPal account directly in Reverb.
Thus, the funds will transfert directly in your PayPal account.

### Configure the mapping categories 


### Configure your products
### Sync management of products
### Sync management of Orders and inventory
### FAQ