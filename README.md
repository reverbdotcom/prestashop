# Reverb.com PrestaShop Plugin
This is a PrestaShop app for integrating with Reverb's API including product sync (PrestaShop->Reverb) and order sync (Reverb->PrestaShop).

Please read this entire README prior to installing the application.

## Features
* Create new draft listings on Reverb from PrestaShop products, including image & category sync
* Control whether price/title/inventory syncs individually.
* Sync updates for inventory from PrestaShop to Reverb.
* Sync orders from Reverb to PrestaShop
* Sync shipping tracking information from PrestaShop to Reverb
* Configurable products - children are synced as individual listings on Reverb
* Make/model/price/finish/year/shipping_profile_name can all be synced

## Installation

To install this module, find the latest release in the releases tab and download the attached `reverb.zip` file. Follow Prestashop's instructions for manually installing here: [http://www.prestatoolbox.com/content/21-to-install-a-new-prestashop-module](http://www.prestatoolbox.com/content/21-to-install-a-new-prestashop-module).

## Connecting your account
Visit Reverb.com and log in to your seller account. Then, navigate to your account settings and access the 'API & Integration' tab.

Generate a new Personal Access Token. Name it 'Prestashop' (or any name you choose), and give it every oauth scope by checking every box on the page. Once the token is generated, copy and paste it into the Login tab of the Reverb module in PrestaShop. Validate your entry to log in.

## Syncing your products

### 1. Ensure your products have unique SKUs
### 2. Map your product categories

The Reverb module allows you to select your product category and associate it with a Reverb category. You can find this in the Reverb module configuration within the Product type mapping tab.

### 3. Set up your products

You can edit your products and activate product sync by editing an individual product (Catalog -> Product Settings -> Click on a product) and going to the Modules tab.  Then, click on the Configure button for Reverb to access the product information that will be sent to Reverb.  Ensure that the syncronization is activated, and fill in any applicable product information here.

## Setup CRON

CRON Tasks is a program that allows users of Unix systems to automatically run scripts, commands, or software at a specified date and time or in a pre-defined cycle. The CRON allows you to set an automatic sync between PrestaShop and Reverb, so that you don't have to manually bulk-sync your products. 

Go to the administrative panel of your hosting in order to learn how to set up the ordering of your CRON, otherwise you may have to ask your host how to setup 

The following cron Tasks must be configured:

`*/5 * * * * php /var/www/html/modules/reverb/cron.php?code=product > /var/log/cron.log`

`*/8 * * * * php /var/www/html/modules/reverb/cron.php?code=orders > /var/log/cron.log`

The first cron is a script executed every 5 minutes about the product sync - PrestaShop to Reverb. The second cron is a script executed every 8 minutes about the order sync - Reverb to PrestaShop.

## Product sync management

In the Reverb module configuration in PrestaShop, you need to go to Sync Status tab. You can filter your search results and you can see the status of sync (Success, error, to_sync) with a message. 3 actions are available: Sync a product manually, a PrestaShop product link, and a Reverb product link.

## FAQ
### Q: Why aren't things synced in real time, or failing to sync at all?

### Q: Why aren't things synced in real time, or failing to sync at all?
-Check if the API token is valid

-Check that each eligible product in Reverb is setup correctly

-Check the logs in the Logs tab

If the problem persists, contact Reverb support at integrations@reverb.com


## Additional documentation

Read the **[project documentation][doc-home-fr] in French** for comprehensive information about the requirements, general workflow and installation procedure.

Read the **[project documentation][doc-home-en] in English** for comprehensive information about the requirements, general workflow and installation procedure.

## Resources
- [Full project documentation][doc-home-fr] — To have a comprehensive understanding of the workflow and get the installation procedure in french
- [Full project documentation][doc-home-en] — To have a comprehensive understanding of the workflow and get the installation procedure in english
- [Reverb Support Center][reverb-help] — To get technical help from Reverb
- [Issues][project-issues] — To report issues, submit pull requests and get involved (see [Apache 2.0 License][project-license])
- [Change log][project-changelog] — To check the changes of the latest versions
- [Contributing guidelines][project-contributing] — To contribute to our source code

## License

The **Reverb.com** is available under the **Apache 2.0 License**. Check out the [license file][project-license] for more information.


[doc-home-fr]: https://github.com/jprotin/reverb-prestashop/blob/develop/src/reverb/doc/documentation-reverb-fr.md
[doc-home-en]: https://github.com/jprotin/reverb-prestashop/blob/develop/src/reverb/doc/documentation-reverb-fr.md
[reverb-help]: https://reverb.com/fr/page/contact
[project-issues]: https://github.com/jprotin/reverb-prestashop
[project-license]: LICENSE.md
[project-changelog]: CHANGELOG.md
[project-contributing]: CONTRIBUTING.md
