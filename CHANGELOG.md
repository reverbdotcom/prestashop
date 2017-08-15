# Version 1.1.1

- Fix: add new help tooltip under the Model field in the product detail about Model is mandatory.
- Fix: add in the documentation a description about the Model field.

# Version 1.1.0

- New: If upc is empty and ean not empty, ean code is synced in Reverb API in the upc field
- Fix: Add a new environment variable PS_ERASE_DB=1 in the env_file for the Docker image of PrestaShop
- Fix: Update new version of PrestaShop images in the Dockerfile (PrestaShop 1.6.1.16 and 1.7.2.0)

# Version 1.0.5

- Fix a bug to the bulk sync button (sync status)
- New: add a new button to launch cron manually (sync status)

# Version 1.0.4

- Fix Category Mapping with multi-store

# Version 1.0.3

- Fix Error CRON in the Prestashop < 1.6.1
- Fix loading button in the product detail in Prestashop < 1.6.1
- Fix bug Cart declaration for PrestaShop < 1.6.1

# Version 1.0.2

Fix - add tax to the field total_products_wt


# Version 1.0.1

Fix - tax in the create order

# Version 1.0.0

Init project