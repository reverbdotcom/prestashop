# Version 1.5.1

- Fix: retro-compatibility Tools::getAllValues() < PS 1.6.1.0

# Version 1.5.0

- New: Add a new column in the Sync status tab : quantity


# Version 1.4.7

- Fix: If stock = 0 and Publish = true in the listing sync, the sync force publish = false.

# Version 1.4.6

- Fix: Integration Reverb orders with status shipped in Prestashop when Prestashop orders don't exist

# Version 1.4.5

- Fix: replace image type "large_default" by "large" when the listing sync is n progress

# Version 1.4.4

- Fix: no duplication of order when the status is on Error
- Fix: update the language context before the validateOrder (block message Can't load status)
- Fix: update dockerfile with the prestashop 1.7 latest

# Version 1.4.3

- Fix: quality image compatibility between Prestashop 1.6 and 1.7

# Version 1.4.2

- Fix: Add max_execution_time = 20min for slow server

# Version 1.4.1

- Fix: Request for the pagination in the mass edit tab

# Version 1.4.0

- Fix: optimize the quality images sunced to Reverb
- Fix: Pagination last-page in mass-edit
- Fix: Documentation about the stock management + optimize the workflow between Prestashop and Reverb
- New: Add Brand to the search by tags in Mass-edit tab
- New: Add a popup in sync status tab to inform the number of products treated

# Version 1.3.4

- Fix: tax-exempt is false by default in mass-edit
- Fix: JS variable undefined massEditType

# Version 1.3.3

- Fix: Pagination in Sync status tab (last page is empty)
- Fix: Bug jQuery conflict for dependencies

# Version 1.3.2

- Fix: Pagination in Sync status tab

# Version 1.3.1

- Fix: Error update module - duplicate entry SQL query

# Version 1.3.0

- Fix: cascading product deletion in SQL
- New: New sync button to manage all product in the list
- New: New mass-edit button to update all products in the list
- New: Management the category mapping

# Version 1.2.9

- Fix: Mass edit search compatible multisite
- Fix: Add a localstorage system to stay in the Active tab after submitting

# Version 1.2.8

- New: Add new search param category in the mass edit

# Version 1.2.7

- Fix: Ps_Round with 2 decimals for the seller cost (wholesale_price)

# Version 1.2.6

- Fix: Redirection after submitting the listing sync manually

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
