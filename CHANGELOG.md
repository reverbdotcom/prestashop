# Version 1.2.5

- Fix: Price format for the seller cost (wholesale_price)

# Version 1.2.4

- Fix: ajax method for the listing sync button and orders sync button

# Version 1.2.3

- Fix: No 'Access-Control-Allow-Origin' header is present on the requested resource

# Version 1.2.2

- Fix: Do not send make or model if there is no make or model
- Fix: Only first level categories are showed for the Category Mapping and when it's matched with Reverb Code, all children categories are update with this code.

# Version 1.2.1

- Fix: Bulk action
- Fix: documentations FR and EN

# Version 1.2.0

- New: Feature Mass-edit

# Version 1.1.2

- Fix: Correct pagination on PS <1.6.1
- Fix: add ZIP Package for OVH cron system with 3 files

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
