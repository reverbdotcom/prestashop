<?php
/**
 * Module Reverb
 *
 * @author Johan Protin
 * @copyright Copyright (c) 2017 - Johan Protin
 * @license Apache License Version 2.0, January 2004
 * @package Reverb
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class Reverb extends Module
{
    const KEY_REVERB_CONFIG = 'REVERB_CONFIG';
    const KEY_SANDBOX_MODE = 'sandbox_mode';
    const KEY_APP_CLIENT_ID = 'app_client_id';
    const KEY_APP_REDIRECT_URI = 'app_redirect_api';
    const KEY_API_TOKEN = 'api_token';
    const KEY_API_TOKEN_SANDBOX = 'api_token_sandbox';
    const KEY_RANDOM_STATE = 'random_state';
    const KEY_DEBUG_MODE = 'debug_mode';
    const KEY_SETTINGS_AUTO_SYNC = 'settings_auto_sync';
    const KEY_SETTINGS_AUTO_PUBLISH = 'settings_auto_publish';
    const KEY_SETTINGS_CREATE_NEW_LISTINGS = 'settings_create_new_listings';
    const KEY_SETTINGS_DESCRIPTION = 'settings_description';
    const KEY_SETTINGS_PHOTOS = 'settings_photos';
    const KEY_SETTINGS_CONDITION = 'settings_condition';
    const KEY_SETTINGS_PRICE = 'settings_price';

    const LIST_ID = 'ps_product_reverb';
    const LIST_CATEGORY_ID = 'ps_mapping_category';
    const LIST_ORDERS_ID = 'ps_reverb_orders';

    protected $config_form = false;
    public $reverbConfig;
    public $reverbSync;
    public $reverbOrders;
    public $reverbAttributes;
    public $logs;
    public $active_tab;
    public $language_id;
    protected $class_names = array(
        'AdminReverbConfiguration',
    );

    const CODE_CRON_ORDERS = 'orders';

    public $prod_url = 'https://reverb.com';
    public $sandbox_url = 'https://sandbox.reverb.com';

    protected $_errors = array();
    protected $_successes = array();
    protected $_infos = array();

    public function __construct()
    {
        $this->name = 'reverb';
        $this->tab = 'market_place';
        $this->version = '1.3.2';
        $this->author = 'Johan PROTIN';
        $this->need_instance = 0;

        /**
         * Set $this->bootstrap to true if your module is compliant with bootstrap (PrestaShop 1.6)
         */
        $this->bootstrap = true;

        parent::__construct();

        // init log object
        $this->logs = new \Reverb\ReverbLogs($this);

        $this->reverbSync = new \ReverbSync($this);
        $this->reverbOrders = new \ReverbOrders($this);
        $this->reverbAttributes = new \ReverbAttributes($this);

        $this->displayName = $this->l('Reverb');
        $this->description = $this->l('Reverb is the best place anywhere to sell your guitar, amp, drums, bass, or other music gear.Built for and by musicians, our marketplace offers a broad variety of tools to help buy and sell, and has the lowest transaction fees of any online marketplace.');

        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);

        $this->language_id = $this->context->language->id;

        $this->reverbConfig = $this->getReverbConfig();

        $this->_infos[] = $this->l('The following cron tasks must be configured in your hosting:');
        $this->_infos[] = ' -   */5 * * * *  php '.dirname(__FILE__).'/cron.php products  > /var/log/cron.log';
        $this->_infos[] = ' -   */8 * * * *  php '.dirname(__FILE__).'/cron.php orders > /var/log/cron.log';
        $this->_infos[] = ' -   0 8 * * *  php '.dirname(__FILE__).'/cron.php orders-reconciliation > /var/log/cron.log';
        $this->_infos[] = $this->l('It is possible to launch manually in you browser:');
        $this->_infos[] = ' -   '.Tools::getHttpHost(true).__PS_BASE_URI__.'/modules/'.$this->name.'/cron.php?code=products';
        $this->_infos[] = ' -   '.Tools::getHttpHost(true).__PS_BASE_URI__.'/modules/'.$this->name.'/cron.php?code=orders';
        $this->_infos[] = ' -   '.Tools::getHttpHost(true).__PS_BASE_URI__.'/modules/'.$this->name.'/cron.php?code=orders-reconciliation';
    }

    /**
     * Don't forget to create update methods if needed:
     * http://doc.prestashop.com/display/PS16/Enabling+the+Auto-Update
     */
    public function install()
    {
        Configuration::updateValue(self::KEY_REVERB_CONFIG, '');

        $sql = array();
        $sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'reverb_sync` (
            `id_sync` int(10) unsigned NOT NULL AUTO_INCREMENT,
            `id_product` int(10) unsigned NOT NULL,
            `id_product_attribute` int(10) unsigned,
            `reverb_id` varchar(32) ,
            `status` varchar(32) NOT NULL,
            `details` text,
            `reverb_slug` varchar(150) ,
            `date` datetime,
            `origin` text,
            PRIMARY KEY  (`id_sync`),
            CONSTRAINT `ps_reverb_sync_ibfk_1` FOREIGN KEY fk_reverb_sync_product(id_product) REFERENCES `' . _DB_PREFIX_ . 'product` (id_product) ON DELETE CASCADE,
            CONSTRAINT `ps_reverb_sync_ibfk_2` FOREIGN KEY fk_reverb_sync_product_2(id_product_attribute) REFERENCES `' . _DB_PREFIX_ . 'product_attribute` (id_product_attribute) ON DELETE CASCADE,
            UNIQUE (id_product, id_product_attribute)
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';
        $sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'reverb_mapping` (
            `id_mapping` int(10) unsigned NOT NULL AUTO_INCREMENT,
            `id_category` int(10) unsigned NOT NULL,
            `reverb_code` varchar(50) NOT NULL,
            PRIMARY KEY  (`id_mapping`),
            CONSTRAINT `ps_reverb_mapping_ibfk_1` FOREIGN KEY fk_reverb_mapping_category(id_category) REFERENCES `' . _DB_PREFIX_ . 'category` (id_category) ON DELETE CASCADE,
            UNIQUE (id_category)
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';
        $sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'reverb_attributes` (
            `id_attribute` int(10) unsigned NOT NULL AUTO_INCREMENT,
            `id_product` int(10) unsigned NOT NULL,
            `reverb_enabled` tinyint(1),
            `model` varchar(50),
            `id_lang` ' . (version_compare(_PS_VERSION_, '1.7', '<') ? 'int(10) unsigned' : 'int(11)') . ' NOT NULL,
            `offers_enabled` tinyint(1),
            `tax_exempt` tinyint(1),
            `finish` varchar(50),
            `origin_country_code` varchar(50),
            `year` varchar(50),
            `id_condition` varchar(50),
            `id_shipping_profile` int(10) unsigned,
            `shipping_local` tinyint(1),
            PRIMARY KEY (`id_attribute`),
            CONSTRAINT `ps_reverb_attributes_ibfk_1` FOREIGN KEY fk_reverb_attributes_product(id_product) REFERENCES `' . _DB_PREFIX_ . 'product` (id_product) ON DELETE CASCADE,
            CONSTRAINT `ps_reverb_attributes_ibfk_2` FOREIGN KEY fk_reverb_attributes_lang(id_lang) REFERENCES `' . _DB_PREFIX_ . 'lang` (id_lang) ON DELETE CASCADE,
            UNIQUE (id_product)
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';
        $sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'reverb_shipping_methods` (
            `id_shipping_method` int(10) unsigned NOT NULL AUTO_INCREMENT,
            `id_attribute` int(10) unsigned NOT NULL,
            `region_code` varchar(50) NOT NULL,
            `rate` decimal(20,2) NOT NULL,
            PRIMARY KEY (`id_shipping_method`),
            CONSTRAINT `ps_reverb_shipping_methods_ibfk_1` FOREIGN KEY fk_reverb_shipping_methods_attribute(id_attribute) REFERENCES `' . _DB_PREFIX_ . 'reverb_attributes` (id_attribute) ON DELETE CASCADE,
            UNIQUE (id_attribute, region_code)
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';
        $sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'reverb_crons` (
            `id_cron` int(10) unsigned NOT NULL AUTO_INCREMENT,
            `code` varchar(50) NOT NULL,
            `status` text NOT NULL,
            `date` datetime NOT NULL,
            `number_to_sync` int(11),
            `number_sync` int(11),
            `details` text,
            PRIMARY KEY  (`id_cron`)
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';
        $sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'reverb_sync_history` (
            `id_sync_history` int(10) unsigned NOT NULL AUTO_INCREMENT,
            `id_product` int(10) unsigned NOT NULL,
            `id_product_attribute` int(10) unsigned,
            `status` text NOT NULL,
            `origin` text NOT NULL,
            `date` datetime NOT NULL,
            `details` text NOT NULL,
            PRIMARY KEY  (`id_sync_history`),
            CONSTRAINT `ps_reverb_sync_history_ibfk_1` FOREIGN KEY fk_reverb_sync_history_product(id_product) REFERENCES `' . _DB_PREFIX_ . 'product` (id_product) ON DELETE CASCADE,
            CONSTRAINT `ps_reverb_sync_history_ibfk_2` FOREIGN KEY fk_reverb_sync_history_product_attribute(id_product_attribute) REFERENCES `' . _DB_PREFIX_ . 'product_attribute` (id_product_attribute) ON DELETE CASCADE
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';
        $sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'reverb_orders` (
            `id_reverb_orders` int(10) unsigned NOT NULL AUTO_INCREMENT,
            `id_order` int(10) unsigned,
            `id_product` int(10) unsigned,
            `id_product_attribute` int(10) unsigned,
            `id_shop` int(11) unsigned,
            `id_shop_group` int(11) unsigned,
            `reverb_order_number` varchar(32),
            `reverb_product_sku` varchar(32),
            `reverb_product_title` text,
            `status` varchar(32) NOT NULL,
            `details` text,
            `created_at` datetime,
            `updated_at` datetime,
            `shipping_method` varchar(32),
            `shipping_tracker` text,
            PRIMARY KEY  (`id_reverb_orders`)
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

        foreach ($sql as $query) {
            if (Db::getInstance()->execute($query) == false) {
                return false;
            }
        }

        $this->updateReverbOrderState();

        return parent::install() &&
            $this->registerHook('backOfficeHeader') &&
            $this->registerHook('displayBackOfficeHeader') &&
            $this->registerHook('displayAdminProductsExtra') &&
            $this->registerHook('actionProductUpdate') &&
            $this->registerHook('actionProductSave') &&
            $this->registerHook('actionObjectOrderAddAfter') &&
            $this->registerHook('actionObjectProductAddAfter') &&
            $this->registerHook('actionObjectProductDeleteAfter') &&
            $this->registerHook('actionObjectProductUpdateAfter') &&
            $this->registerHook('actionOrderStatusPostUpdate') &&
            $this->registerHook('actionAdminOrdersTrackingNumberUpdate') &&
            $this->registerHook('actionOrderEdited') &&
            $this->createAdminTab();
    }

    public function uninstall()
    {
        Configuration::deleteByName(self::KEY_REVERB_CONFIG);

        $sql = array();

        $sql[] = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'reverb_sync`;';
        $sql[] = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'reverb_mapping`;';
        $sql[] = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'reverb_shipping_methods`;';
        $sql[] = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'reverb_attributes`;';
        $sql[] = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'reverb_sync_history`;';
        $sql[] = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'reverb_crons`';
        $sql[] = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'reverb_orders`';

        foreach ($sql as $query) {
            if (Db::getInstance()->execute($query) == false) {
                return false;
            }
        }

        return $this->uninstallAdminTab() &&
            parent::uninstall();
    }

    protected function createAdminTab()
    {
        foreach ($this->class_names as $class_name) {
            $tab = new Tab();

            $tab->active = 1;
            $tab->module = $this->name;
            $tab->class_name = $class_name;
            $tab->id_parent = -1;

            foreach (Language::getLanguages(true) as $lang) {
                $tab->name[$lang['id_lang']] = $this->name;
            }
            if (!$tab->add()) {
                return false;
            }
        }
        return true;
    }

    public function uninstallAdminTab()
    {
        foreach ($this->class_names as $class_name) {
            $id_tab = (int)Tab::getIdFromClassName($class_name);

            if ($id_tab) {
                $tab = new Tab($id_tab);
                if (!$tab->delete()) {
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * Load the configuration form
     */
    public function getContent()
    {
        $this->postProcess();

        $module_url = $this->context->link->getAdminLink('AdminModules', true)
            . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;

        if ($this->isApiTokenAvailable()) {
            $reverbCategories = new \Reverb\ReverbCategories($this);
            $reverbShop = new \Reverb\ReverbShop($this);
            $reverbShippingRegions = new \Reverb\ReverbShippingRegions($this);
            $reverbConditions = new \Reverb\ReverbConditions($this);

            $products = $this->reverbSync->getAllProductsPagination();
            //dump($products); exit;

            $this->context->smarty->assign(array(
                'reverb_categories' => $reverbCategories->getFormattedCategories(),
                'is_logged' => true,
                'token' => Tools::getAdminTokenLite('AdminModules'),
                'reverb_product_preview_url' => $this->getReverbProductPreviewUrl(),
                'reverb_order_preview_url' => $this->getReverbOrderPreviewUrl(),
                'ps_product_preview_base_url' => _PS_BASE_URL_,
                'ps_order_preview_base_url' => $this->context->link->getAdminLink('AdminOrders', true),
                'module_url' => $module_url,
                'reverb_list_country' => Country::getCountries($this->context->language->id),
                'reverb_shipping_profiles' => $reverbShop->getShoppingProfiles(),
                'reverb_url' => $this->getReverbUrl(),
                'reverb_regions' => $reverbShippingRegions->getFormattedShippingRegions(),
                'currency' => $this->getContext()->currency->getSign(),
                'reverb_list_conditions' => $reverbConditions->getFormattedConditions(),
                'products_mass_edit' => $products,
            ));
            if (!$this->active_tab) {
                $this->active_tab = 'sync_status';
            }
        } else {
            $this->context->smarty->assign(array(
                'active_tab' => 'login',
                'is_logged' => false,
                'reverb_product_preview_url' => '',
                'reverb_order_preview_url' => '',
                'ps_product_preview_base_url' => '',
                'ps_order_preview_base_url' => '',
                'module_url' => $module_url
            ));
        }

        $this->context->smarty->assign(array(
            'module_dir' => $this->_path,
            'reverb_login_form' => $this->renderLoginForm(),
            'reverb_url_prod' => $this->prod_url . '/my/api_settings',
            'reverb_url_sandbox' => $this->sandbox_url . '/my/api_settings',
            'reverb_settings_form' => $this->renderSettingsForm(),
            'reverb_config' => $this->reverbConfig,
            'reverb_sync_status' => $this->getViewSyncStatus(),
            'reverb_orders_status' => $this->getViewOrdersSync(),
            'reverb_mapping_categories' => $this->getViewMappingCategories(),
            'logs' => $this->getLogFiles(),
            'active_tab' => $this->active_tab,
            'ajax_url' => $this->context->link->getAdminLink('AdminReverbConfiguration'),
            'url_site' => Tools::getHttpHost(true).__PS_BASE_URI__,
        ));

        // Set alert messages
        $this->context->smarty->assign(array(
            'errors' => $this->_errors,
            'successes' => $this->_successes,
            'infos' => $this->_infos,
        ));

        $output = $this->context->smarty->fetch($this->local_path . 'views/templates/admin/configure.tpl');
        return $output;
    }

    /**
     * Get the appropriate logs
     * @return string
     */
    protected function getLogFiles()
    {
        // scan log dir
        $dir = _PS_MODULE_DIR_ . '/reverb/logs/';
        $files = scandir($dir, 1);
        // init array files
        $error_files = $info_files = $listings_files = $categories_files = $cron_files = array();
        // dispatch files
        foreach ($files as $file) {
            if (preg_match("/" . \Reverb\ReverbLogs::LOG_ERROR . "/i", $file) && count($error_files) < 10) {
                $error_files[] = $file;
            }
            if (preg_match("/" . \Reverb\ReverbLogs::LOG_INFOS . "/i", $file) && count($info_files) < 10) {
                $info_files[] = $file;
            }
            if (preg_match("/" . \Reverb\ReverbLogs::LOG_LISTINGS . "/i", $file) && count($info_files) < 10) {
                $listings_files[] = $file;
            }
            if (preg_match("/" . \Reverb\ReverbLogs::LOG_CATEGORIES . "/i", $file) && count($info_files) < 10) {
                $categories_files[] = $file;
            }
            if (preg_match("/" . \Reverb\ReverbLogs::LOG_CRON . "/i", $file) && count($info_files) < 10) {
                $cron_files[] = $file;
            }
        }
        return array(
            'error' => $error_files,
            'info' => $info_files,
            'listings' => $listings_files,
            'categories' => $categories_files,
            'cron' => $cron_files,
        );
    }

    /**
     * Create the form that will be displayed in the configuration of your module.
     */
    protected function renderLoginForm()
    {
        $helper = new HelperForm();

        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitReverbModuleLogin';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigLoginFormValues(), /* Add values for your inputs */
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );

        return $helper->generateForm(array($this->getConfigLoginForm()));
    }

    /**
     * Create the form that will be displayed in the configuration settings tab of your module.
     */
    protected function renderSettingsForm()
    {
        $helper = new HelperForm();

        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitReverbModuleSettings';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigSettingsFormValues(), /* Add values for your inputs */
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );

        return $helper->generateForm(array($this->getConfigSettingsForm()));
    }

    /**
     * Create the structure of your login form.
     */
    protected function getConfigLoginForm()
    {
        return array(
            'form' => array(
                'input' => array(
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Sandbox mode'),
                        'name' => self::KEY_SANDBOX_MODE,
                        'is_bool' => true,
                        'desc' => $this->l('Use this module in sandbox mode'),
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Enabled')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('Disabled')
                            )
                        ),
                    ),
                    array(
                        'id' => 'token_production',
                        'col' => 3,
                        'type' => 'text',
                        'desc' => '<a id="reverb-url-help" href="https://reverb.com/my/api_settings" target="_blank">' . $this->l('From https://reverb.com/my/api_settings') . '</a>',
                        'name' => self::KEY_API_TOKEN,
                        'label' => $this->l('API Token'),
                    ),
                    array(
                        'id' => 'token_sandbox',
                        'col' => 3,
                        'type' => 'text',
                        'desc' => '<a id="reverb-url-help" href="https://sandbox.reverb.com/my/api_settings" target="_blank">' . $this->l('From https://sandbox.reverb.com/my/api_settings') . '</a>',
                        'name' => self::KEY_API_TOKEN_SANDBOX,
                        'label' => $this->l('API Token'),
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                ),
            ),
        );
    }

    /**
     * Create the structure of your settings form.
     */
    protected function getConfigSettingsForm()
    {
        $fields = array(
            array(
                'name' => self::KEY_SETTINGS_AUTO_SYNC,
                'label' => $this->l('Automatically sync Prestashop changes to reverb'),
                'desc' => $this->l('You can selectively disable sync for certain items by tagging them with the prestashop tag.'),
            ),
            array(
                'name' => self::KEY_SETTINGS_CREATE_NEW_LISTINGS,
                'label' => $this->l('Create new listings'),
                'desc' => $this->l('If the settings is off, only updates will be synced. New listings will not be automatically created.'),
            ),
            array(
                'name' => self::KEY_SETTINGS_AUTO_PUBLISH,
                'label' => $this->l('Automatically publish listings after sync'),
                'desc' => $this->l('To publish the listing right away requires more fields such as images and shipping rates, and may not always be possible.'),
            ),
            array(
                'name' => self::KEY_SETTINGS_DESCRIPTION,
                'label' => $this->l('Description'),
                'desc' => $this->l('You may want to turn this off if you have emails/phone numbers in yours descriptions, wich are not allowed on Reverb.'),
            ),
            array(
                'name' => self::KEY_SETTINGS_PHOTOS,
                'label' => $this->l('Photos'),
                'desc' => $this->l('On first time sync to Reverb, photos will be ignored if Reverb already has photos. On subsequent syncs, only new photos will be copied over. Reordering will not be synced.'),
            ),
            array(
                'name' => self::KEY_SETTINGS_CONDITION,
                'label' => $this->l('Condition'),
                'desc' => $this->l('On first time listing create, we will always sync the condition. This setting controls wether we sync the field on updates. Condition will be read from a tag (example: condition:Brand New). If the condition tag is not specified, we will use Brand New for inventory items and Mint for non-inventory items.'),
            ),
            array(
                'name' => self::KEY_SETTINGS_PRICE,
                'label' => $this->l('Price'),
                'desc' => $this->l('On first time listing create, we will always sync price. If you set special prices on Reverb, turn off this settings to avoid updating price.'),
            )
        );

        $input = array();
        foreach ($fields as $field) {
            if (array_key_exists('type', $field) && $field['type'] == 'text') {
                $input[] = array(
                    'type' => 'text',
                    'label' => $field['label'],
                    'name' => $field['name'],
                    'desc' => $field['desc'],
                );
            } else {
                $input[] = array(
                    'type' => 'switch',
                    'label' => $field['label'],
                    'name' => $field['name'],
                    'is_bool' => true,
                    'desc' => $field['desc'],
                    'values' => array(
                        array(
                            'id' => 'active_on',
                            'value' => true,
                            'label' => $this->l('Enabled')
                        ),
                        array(
                            'id' => 'active_off',
                            'value' => false,
                            'label' => $this->l('Disabled')
                        )
                    ),
                );
            }
        }
        return array(
            'form' => array(
                'input' => $input,
                'submit' => array(
                    'title' => $this->l('Save'),
                ),
            ),
        );
    }

    /**
     * Set values for the login form inputs.
     */
    protected function getConfigLoginFormValues()
    {
        return array(
            self::KEY_SANDBOX_MODE => $this->getReverbConfig(self::KEY_SANDBOX_MODE),
            self::KEY_API_TOKEN => $this->getReverbConfig(self::KEY_API_TOKEN),
            self::KEY_API_TOKEN_SANDBOX => $this->getReverbConfig(self::KEY_API_TOKEN_SANDBOX),
        );
    }

    /**
     * Set values for the settings form inputs.
     */
    protected function getConfigSettingsFormValues()
    {
        return array(
            self::KEY_SETTINGS_AUTO_SYNC => $this->getReverbConfig(self::KEY_SETTINGS_AUTO_SYNC),
            self::KEY_SETTINGS_AUTO_PUBLISH => $this->getReverbConfig(self::KEY_SETTINGS_AUTO_PUBLISH),
            self::KEY_SETTINGS_CREATE_NEW_LISTINGS => $this->getReverbConfig(self::KEY_SETTINGS_CREATE_NEW_LISTINGS),
            self::KEY_SETTINGS_CONDITION => $this->getReverbConfig(self::KEY_SETTINGS_CONDITION),
            self::KEY_SETTINGS_DESCRIPTION => $this->getReverbConfig(self::KEY_SETTINGS_DESCRIPTION),
            self::KEY_SETTINGS_PHOTOS => $this->getReverbConfig(self::KEY_SETTINGS_PHOTOS),
            self::KEY_SETTINGS_PRICE => $this->getReverbConfig(self::KEY_SETTINGS_PRICE),
        );
    }

    /**
     * Get Reverb configuration from database.
     * If not exists, init it with just the sandbox mode set to true
     */
    public function initReverbConfig()
    {
        // init multistore
        $id_shop = (int)$this->context->shop->id;
        $id_shop_group = (int)Shop::getContextShopGroupID();

        $reverbConfig = Configuration::get(self::KEY_REVERB_CONFIG, null, $id_shop_group, $id_shop);

        // Reverb config not saved yet
        if (!$reverbConfig || empty($reverbConfig)) {
            $this->reverbConfig = array(
                self::KEY_SANDBOX_MODE => true,
            );

            // the config is stacked in JSON
            if (!Configuration::updateValue(self::KEY_REVERB_CONFIG, Tools::jsonEncode($this->reverbConfig), false, $id_shop_group, $id_shop)
            ) {
                throw new Exception($this->l('Config save failed, try again.'));
            }
        } else {
            $this->reverbConfig = Tools::jsonDecode($reverbConfig, true);
        }
    }

    /**
     * Get all or one specific reverb configuration
     * @param null|string
     * @return array|string
     */
    public function getReverbConfig($key = null)
    {
        // If reverb config is empty, we create it
        if (!$this->reverbConfig || empty($this->reverbConfig)) {
            $this->initReverbConfig();
        }

        if ($key) {
            return isset($this->reverbConfig[$key]) ? $this->reverbConfig[$key] : null;
        }

        return $this->reverbConfig;
    }

    /**
     * Save Reverb configuration to database
     * @throws Exception
     */
    protected function saveReverbConfiguration()
    {
        // init multistore
        $id_shop = (int)$this->context->shop->id;
        $id_shop_group = (int)Shop::getContextShopGroupID();

        if (!Configuration::updateValue(self::KEY_REVERB_CONFIG, Tools::jsonEncode($this->reverbConfig), false, $id_shop_group, $id_shop)
        ) {
            throw new Exception($this->l('Update failed, try again.'));
        }
    }


    /**
     * Process submitted forms
     */
    protected function postProcess()
    {
        // First form with api token and env mode
        if (Tools::isSubmit('submitReverbModuleLogin')) {
            $form_values = $this->getConfigLoginFormValues();

            foreach (array_keys($form_values) as $key) {
                $value = Tools::getValue($key);
                if (!empty($value) && (!$this->reverbConfig[self::KEY_SANDBOX_MODE] && $key == self::KEY_API_TOKEN ||
                        ($this->reverbConfig[self::    KEY_SANDBOX_MODE] && $key == self::KEY_API_TOKEN_SANDBOX))
                ) {
                    $mode = 'PRODUCTION';
                    if ((bool)$this->reverbConfig[self::KEY_SANDBOX_MODE] ||
                        !array_key_exists(self::KEY_SANDBOX_MODE, $this->reverbConfig)
                    ) {
                        $mode = 'SANDBOX';
                    }

                    $reverbClient = new \Reverb\ReverbAuth($this, $value);
                    try {
                        $shop = $reverbClient->getListFromEndpoint(null, null, false);
                    } catch (\Exception $e) {
                        $shop = false;
                    }


                    if (!is_array($shop) || (!array_key_exists('shop', $shop) && empty($shop['shop']))) {
                        $value = '';
                        $this->_errors[] = $this->l('API Token is invalid for ' . $mode . ' mode , please try again.');
                    } elseif (is_array($shop) && !array_key_exists('shop', $shop) && empty($shop['shop'])) {
                        $value = '';
                        $this->_errors[] = $this->l('API Token is valide for ' . $mode . ' mode , but you have to configured a shop in reverb.');
                    }
                }

                $this->reverbConfig[$key] = trim($value);
                $this->saveReverbConfiguration();
            }


            if (empty($this->_errors)) {
                $this->_successes[] = $this->l('Login configuration saved successfully.');
            }
        }

        // Settings form
        if (Tools::isSubmit('submitReverbModuleSettings')) {
            $form_values = $this->getConfigSettingsFormValues();

            foreach (array_keys($form_values) as $key) {
                $this->reverbConfig[$key] = Tools::getValue($key);
            }

            $this->saveReverbConfiguration();
            $this->_successes[] = $this->l('Settings configuration saved successfully.');
        }

        // Categories pagination
        if (Tools::isSubmit('submitFilterps_mapping_category')) {
            $this->active_tab = 'categories';
        }

        // Orders sync pagination
        if (Tools::isSubmit('submitFilterps_reverb_') || Tools::isSubmit('ps_reverb_ordersOrderby')) {
            $this->active_tab = 'orders_status';
        }

        // Orders sync pagination
        if (Tools::isSubmit('submitFilterps_reverb_orders') || Tools::isSubmit('ps_reverb_ordersOrderby')) {
            $this->active_tab = 'orders_status';
        }

        // Process reset to Filters
        if (Tools::isSubmit('submitResetps_reverb_orders')) {
            $this->processResetFilters(self::LIST_ORDERS_ID);
        }

        // Process reset to Filters
        if (Tools::isSubmit('submitResetps_product_reverb')) {
            $this->processResetFilters(self::LIST_ID);
        }

        // Bulk sync selected products
        if (Tools::isSubmit('submitBulkSyncronizeps_product_reverb')) {
            $identifiers = Tools::getValue('ps_product_reverbBox');
            if (!empty($identifiers)) {
                foreach ($identifiers as $identifier) {
                    $ids = explode('-', $identifier);

                    if (!empty($ids) && count($ids) == 2) {
                        $id_product = $ids[0];
                        $id_product_attribute = $ids[1];
                        $this->reverbSync->setProductToSync($id_product, $id_product_attribute, ReverbSync::ORIGIN_MANUAL_SYNC_MULTIPLE);
                    }
                }
                $this->_successes[] = $this->l('The ' . count($identifiers) . ' products will be synced soon');
            } else {
                $this->_errors[] = $this->l('Please select at least one product.');
            }
        }

        // Bulk sync all
        if (Tools::isSubmit('submitBulkSyncronize_allps_product_reverb')) {
            $fields_list = array(
                'id_product' => array(
                    'title' => $this->l('ID'),
                    'width' => 30,
                    'type' => 'int',
                    'filter_key' => 'p!id_product'
                ),
                'reverb_id' => array(
                    'title' => $this->l('Reverb ID'),
                    'width' => 70,
                    'type' => 'text',
                    'filter_key' => 'rs!id_sync'
                ),
                'reference' => array(
                    'title' => $this->l('SKU'),
                    'width' => 140,
                    'type' => 'text',
                    'filter_key' => 'p!reference'
                ),
                'name' => array(
                    'title' => $this->l('Name'),
                    'width' => 140,
                    'type' => 'text',
                    'filter_key' => 'pl!name'
                ),
                'status' => array(
                    'title' => $this->l('Sync Status'),
                    'width' => 50,
                    'type' => 'select',
                    'search' => true,
                    'orderby' => true,
                    'filter_key' => 'rs!status',
                    'badge_success' => true,
                    'list' => array('success' => 'success',
                        'error' => 'error',
                        'to_sync' => 'to_sync')
                ),
                'details' => array(
                    'title' => $this->l('Sync Detail'),
                    'width' => 300,
                    'type' => 'text',
                    'search' => 'true',
                    'orderby' => 'true',
                    'filter_key' => 'details'
                ),
                'date' => array(
                    'title' => $this->l('Last synced'),
                    'width' => 140,
                    'type' => 'datetime',
                    'filter_key' => 'date'
                ),
                'reverb_slug' => array(
                    'title' => '',
                    'search' => false,
                    'orderby' => false,
                )
            );
            //=========================================
            //         GET DATAS FOR LIST
            //=========================================
            $datas = $this->reverbSync->getListProductsWithStatus($fields_list, true, false);
            foreach ($datas as $data) {
                $identifier = $data['identifier'];
                $ids = explode('-', $identifier);
                if (!empty($ids) && count($ids) == 2) {
                    $id_product = $ids[0];
                    $id_product_attribute = $ids[1];
                    $this->reverbSync->setProductToSync($id_product, $id_product_attribute, ReverbSync::ORIGIN_MANUAL_SYNC_ALL);
                }
            }
            $this->_successes[] = $this->l('The ' . count($datas) . ' products will be synced soon');
        }

        // Get log file content
        if (Tools::isSubmit('logfile')) {
            $logFile = Tools::getValue('logfile');

            $path = _PS_MODULE_DIR_ . '/reverb/logs/' . $logFile;
            if (!file_exists($path)) {
                http_response_code(404);
                die('<h1>File not found</h1>');
            } else {
                header('Content-Type: text/plain');
                $content = file_get_contents($path);
                echo $content;
                die();
            }
        }
    }

    /**
     * Cancel all filters for this tab
     *
     * @param int|null $list_id
     */
    public function processResetFilters($list_id = null)
    {
        $prefix = $this->getCookieOrderByPrefix();
        $filters = $this->context->cookie->getFamily($prefix.$list_id.'Filter_');
        foreach ($filters as $cookie_key => $filter) {
            if (strncmp($cookie_key, $prefix.$list_id.'Filter_', 7 + Tools::strlen($prefix.$list_id)) == 0) {
                $key =  Tools::substr($cookie_key, 7 + Tools::strlen($prefix.$list_id));
                if (is_array($this->fields_list) && array_key_exists($key, $this->fields_list)) {
                    $this->context->cookie->$cookie_key = null;
                }
                unset($this->context->cookie->$cookie_key);
            }
        }

        if (isset($this->context->cookie->{'submitFilter'.$list_id})) {
            unset($this->context->cookie->{'submitFilter'.$list_id});
        }
        if (isset($this->context->cookie->{$prefix.$list_id.'Orderby'})) {
            unset($this->context->cookie->{$prefix.$list_id.'Orderby'});
        }
        if (isset($this->context->cookie->{$prefix.$list_id.'Orderway'})) {
            unset($this->context->cookie->{$prefix.$list_id.'Orderway'});
        }

        $_POST = array();
        $this->_filter = false;
        unset($this->_filterHaving);
        unset($this->_having);
    }

    /**
     *  Oveeride from AdminController
     *
     * @return mixed
     */
    protected function getCookieOrderByPrefix()
    {
        return str_replace(array('admin', 'controller'), '', Tools::strtolower(get_class($this)));
    }

    /**
     * Add the CSS & JavaScript files you want to be loaded in the BO.
     */
    public function hookBackOfficeHeader()
    {
        if (Tools::getValue('controller') == 'AdminProducts' || Tools::getValue('configure') == $this->name) {
            $this->context->controller->addJS($this->_path . 'views/js/back.js');
            $this->context->controller->addJS($this->_path . 'views/js/datatables/datatables.min.js');
            $this->context->controller->addJS($this->_path . 'views/js/datatables/dataTables.bootstrap.min.js');
            $this->context->controller->addCSS($this->_path . 'views/css/back.css', 'all');
            $this->context->controller->addCSS($this->_path . 'views/css/datatables/dataTables.bootstrap.min.css', 'all');
        }
    }

    /**
     * Add the CSS & JavaScript files you want to be loaded in the BO.
     */
    public function hookDisplayBackOfficeHeader()
    {
        if (Tools::getValue('controller') == 'AdminProducts' || Tools::getValue('configure') == $this->name) {
            $this->context->controller->addJS($this->_path . 'views/js/back.js');
            $this->context->controller->addCSS($this->_path . 'views/css/back.css', 'all');
            $this->context->controller->addJS($this->_path . 'views/js/datatables/datatables.min.js');
            $this->context->controller->addJS($this->_path . 'views/js/datatables/dataTables.bootstrap.min.js');
            $this->context->controller->addCSS($this->_path . 'views/css/datatables/dataTables.bootstrap.min.css', 'all');
            $this->context->controller->addJqueryUi('ui.widget');
            $this->context->controller->addJqueryPlugin('tagify');
        }
    }

    /**
     *   Add Tab in product Page
     *
     * @param $params
     * @return Smarty_Internal_Data|string
     */
    public function hookDisplayAdminProductsExtra($params)
    {
        if (version_compare(_PS_VERSION_, '1.7', '<')) {
            $id_product = (int)Tools::getValue('id_product');
            $template = 'product-tab-content.tpl';
        } else {
            $id_product = $params['id_product'];
            $template = 'product-tab-content17.tpl';
        }
        //=========================================
        //     LOADING CONFIGURATION REVERB
        //=========================================

        if (isset($id_product)) {
            $reverbConditions = new \Reverb\ReverbConditions($this);
            $reverbShippingRegions = new \Reverb\ReverbShippingRegions($this);
            $reverbAttributes = new ReverbAttributes($this);
            $reverbShop = new \Reverb\ReverbShop($this);

            $attribute = $reverbAttributes->getAttributes($id_product);

            $this->context->smarty->assign(array(
                'reverb_enabled' => $attribute['reverb_enabled'],
                'reverb_model' => $attribute['model'],
                'reverb_finish' => $attribute['finish'],
                'reverb_condition' => $attribute['id_condition'],
                'reverb_year' => $attribute['year'],
                'reverb_offers_enabled' => $attribute['offers_enabled'],
                'reverb_tax_exempt' => $attribute['tax_exempt'],
                'reverb_country' => $attribute['origin_country_code'],
                'reverb_list_conditions' => $reverbConditions->getFormattedConditions(),
                'reverb_list_country' => Country::getCountries($this->context->language->id),
                'reverb_url' => $this->getReverbUrl(),
                'reverb_regions' => $reverbShippingRegions->getFormattedShippingRegions(),
                'reverb_shipping_profile' => $attribute['id_shipping_profile'],
                'reverb_shipping_local' => $attribute['shipping_local'],
                'reverb_shipping_methods' => $reverbAttributes->getShippingMethods($attribute['id_attribute']),
                'reverb_shipping_profiles' => $reverbShop->getShoppingProfiles(),
                'currency' => $this->getContext()->currency->getSign(),
                'admin_css' => _PS_CSS_DIR_,
                'admin_css' => _PS_ADMIN_DIR_,
                'old16_prestashop' => version_compare(_PS_VERSION_, '1.6.1', '<') ? 0:1,
            ));
        } else {
            $this->logs->errorLogs('hookDisplayAdminProductsExtra does not found idProduct ! __PS_VERSION__ = ' . _PS_VERSION_);
        }

        //=========================================
        //     PROCESS TEMPLATE
        //=========================================
        $output = $this->context->smarty->fetch($this->local_path . 'views/templates/admin/product/' . $template);
        return $output;
    }

    public function hookActionObjectOrderAddAfter()
    {
        $this->logs->infoLogs(__METHOD__);
        /* Place your code here. */
    }

    public function hookActionObjectProductAddAfter()
    {
        $this->logs->infoLogs(__METHOD__);
        /* Place your code here. */
    }

    public function hookActionObjectProductDeleteAfter()
    {
        $this->logs->infoLogs(__METHOD__);
        /* Place your code here. */
    }


    public function hookActionAdminOrdersTrackingNumberUpdate()
    {
        $this->logs->infoLogs(__METHOD__);
        $this->logs->infoLogs(var_export($_POST, true));
        $id_order = (int)Tools::getValue('id_order');

        if (!empty($id_order)) {
            $order = new Order($id_order);
            $reverbOrder = $this->reverbOrders->getOrderByPsId($id_order);
            if (!empty($reverbOrder)) {
                $this->logs->infoLogs(' - Order ' . $id_order . ' : ' . $order->reference . ' comes from Reverb !');

                if (version_compare(_PS_VERSION_, '1.7', '<')) {
                    $this->logs->infoLogs('PS 1.6');
                    $trackingNumber = Tools::getValue('tracking_number');
                    $this->logs->infoLogs('tracking = ' . var_export($trackingNumber, true));
                    $idOrderCarrier = Tools::getValue('id_order_carrier');
                    $this->logs->infoLogs('idOrderCarrier = ' . var_export($idOrderCarrier, true));
                    $orderCarrier = new OrderCarrier((int)$idOrderCarrier, $this->language_id);
                    $this->logs->infoLogs('idCarrier = ' . $orderCarrier->id_carrier);
                    $carrier = new Carrier((int)$orderCarrier->id_carrier, $this->language_id);
                } else {
                    $this->logs->infoLogs('PS 1.7');
                    $trackingNumber = Tools::getValue('shipping_tracking_number');
                    $this->logs->infoLogs('tracking = ' . var_export($trackingNumber, true));
                    $idCarrier = Tools::getValue('shipping_carrier');
                    $this->logs->infoLogs('idCarrier = ' . var_export($idCarrier, true));
                    $carrier = new Carrier((int)$idCarrier, $this->language_id);
                }

                if (!empty($trackingNumber)) {
                    $this->reverbOrders->update($reverbOrder['id_reverb_orders'], array(
                        'shipping_tracker' => $trackingNumber,
                        //'status' => ReverbOrders::REVERB_ORDERS_STATUS_SHIPPED
                    ));
                    $reverbOrders = new \Reverb\ReverbOrders($this);



                    $provider = $carrier->name;
                    $reverbOrders->setOrderShip($reverbOrder['reverb_order_number'], $provider, $trackingNumber);
                } else {
                    $this->logs->infoLogs('Empty $trackingNumber !');
                }
            } else {
                $this->logs->infoLogs('Empty $reverbOrder with PS id ' . $id_order);
            }
        } else {
            $this->logs->infoLogs('Empty $id_order !');
        }
    }

    /**
     *  When a shipping tracker is set to order ( Status Change )
     *
     * @param $param
     */
    public function hookActionOrderStatusPostUpdate()
    {
        $this->logs->infoLogs(__METHOD__);
        $id_order = (int)Tools::getValue('id_order');
        $order = new Order((int)$id_order);
        $orderProducts = $order->getCartProducts();

        foreach ($orderProducts as &$orderProduct) {
            $this->flagSyncProductForReverbToSync($orderProduct['id_product'], ReverbSync::ORIGIN_PRODUCT_UPDATE);
        }

        $id_order_state = (int)Tools::getValue('id_order_state');
        if (!empty($order) && !empty($id_order_state)) {
            $orderState = new OrderState($id_order_state, $this->language_id);
            $this->logs->infoLogs(' - Order state = ' . $orderState->name . ' delivery = ' . $orderState->delivery);
            if ($orderState->delivery) {
                $reverbOrder = $this->reverbOrders->getOrderByPsId($id_order);
                if (!empty($reverbOrder) && in_array($reverbOrder['status'], array(
                    ReverbOrders::REVERB_ORDERS_STATUS_PAID,
                ))) {
                    $this->logs->infoLogs(' - Order ' . $id_order . ' : ' . $order->reference . ' comes from Reverb and not sent yet !');

                    if ($reverbOrder['shipping_method'] == ReverbOrders::REVERB_ORDERS_SHIPPING_METHOD_LOCAL) {
                        $this->logs->infoLogs(' - Reverb order shipping method set to local');
                        $reverbOrders = new \Reverb\ReverbOrders($this);
                        $reverbOrders->setOrderPickedUp($reverbOrder['reverb_order_number']);

                        $this->logs->infoLogs(' - Update reverb_orders');
                        $this->reverbOrders->update($id_order, array(
                            'status' => ReverbOrders::REVERB_ORDERS_STATUS_PICKED_UP
                        ));
                    } else {
                        $this->logs->infoLogs(' - Order shipping method is not local (shipping_method=' . $reverbOrder['shipping_method'] . ')');
                    }
                } else {
                    $this->logs->infoLogs(' - Order shipping status already sent to reverb (status=' . $reverbOrder['status'] . ')');
                }
            } else {
                $this->logs->infoLogs(' - Order state not delivery');
            }
        }
    }


    /**
     *  Hook for save reverb's configuration on product page
     *
     * @param $params
     */
    public function hookActionProductSave()
    {
        $id_product = Tools::getValue('id_product');

        if (isset($id_product) && $id_product) {
            $model = Tools::getValue('reverb_model');
            $settingsReverb = Tools::getValue('reverb_enabled');
            $condition = Tools::getValue('reverb_condition');
            $finish = Tools::getValue('reverb_finish');
            $year = Tools::getValue('reverb_year');
            $offers_enabled = Tools::getValue('offers_enabled');
            $tax_exempt = Tools::getValue('tax_exempt');
            $reverb_country = Tools::getValue('reverb_country');
            $reverb_shipping = Tools::getValue('reverb_shipping');
            $reverb_shipping_profile = Tools::getValue('reverb_shipping_profile');
            $reverb_shipping_methods_region = Tools::getValue('reverb_shipping_methods_region');
            $reverb_shipping_methods_rate = Tools::getValue('reverb_shipping_methods_rate');
            $reverb_shipping_local = Tools::getValue('reverb_shipping_local');
            
            $values = array(
                'reverb_enabled' => pSQL($settingsReverb),
                'model' => pSQL($model),
                'id_condition' => pSQL($condition),
                'finish' => pSQL($finish),
                'year' => pSql($year),
                'offers_enabled' => pSql($offers_enabled),
                'tax_exempt' => pSql($tax_exempt),
                'origin_country_code' => pSql($reverb_country)
            );
            if ($reverb_shipping == 'reverb') {
                $values['id_shipping_profile'] = pSQL($reverb_shipping_profile);
                $values['shipping_local'] = 0;
            } else {
                $values['id_shipping_profile'] = '';
                $values['shipping_local'] = $reverb_shipping_local;
            }

            $this->logs->infoLogs('Save reverb attributes for product ' . $id_product);
            $this->logs->infoLogs(var_export($values, true));

            $this->logs->infoLogs(var_export($reverb_shipping, true));
            $this->logs->infoLogs(var_export($reverb_shipping_profile, true));
            $this->logs->infoLogs(var_export($reverb_shipping_methods_region, true));
            $this->logs->infoLogs(var_export($reverb_shipping_methods_rate, true));
            $this->logs->infoLogs(var_export($reverb_shipping_local, true));

            try {
                $idAttribute = $this->getAttributesId($id_product);
                $db = Db::getInstance();


                if ($idAttribute) {
                    $this->logs->infoLogs('### $idAttribute = ' . $idAttribute);
                    $db->update(
                        'reverb_attributes',
                        $values,
                        'id_attribute = ' . (int)$idAttribute
                    );
                    $this->logs->infoLogs('### fin update');
                    // Remove all shipping methods
                    $db->delete('reverb_shipping_methods', 'id_attribute = ' . $idAttribute, false);
                    $this->logs->infoLogs('### fin delete');
                } else {
                    $this->logs->infoLogs('### debut insert');
                    $db->insert('reverb_attributes', array_merge($values, array(
                        'id_lang' => pSql($this->language_id),
                        'id_product' => pSql($id_product),
                    )));

                    $this->logs->infoLogs('### get idAttribute');
                    $idAttribute = (int)$db->Insert_ID();
                    $this->logs->infoLogs('### fin insert - $idAttribute = ' . $idAttribute);
                }
            } catch (Exception $e) {
                $this->logs->errorLogs($e->getMessage());
            }


            // Save new shipping methods
            if ($reverb_shipping == 'custom') {
                $this->logs->infoLogs('shipping_regions = ' . var_export($reverb_shipping_methods_region, true));
                $this->logs->infoLogs('shipping_rates = ' . var_export($reverb_shipping_methods_rate, true));
                foreach ($reverb_shipping_methods_region as $key => $reverb_shipping_method_region) {
                    if (!empty($idAttribute) && !empty($reverb_shipping_method_region)) {
                        $db->insert('reverb_shipping_methods', array(
                            'id_attribute' => $idAttribute,
                            'region_code' => pSql($reverb_shipping_method_region),
                            'rate' => pSql($reverb_shipping_methods_rate[$key]),
                        ));
                    }
                }
            }

            // Update sync status
            $this->flagSyncProductForReverbToSync($id_product, ReverbSync::ORIGIN_PRODUCT_UPDATE);
        }
    }

    /**
     *  Set Flag To Sync to Product and IdAttribute
     *
     * @param $id_product
     */
    public function flagSyncProductForReverbToSync($id_product, $origin)
    {
        $this->logs->infoLogs('flagSyncProductForReverbToSync ' . $id_product . ' => origin ' . $origin);
        $reverbSync = new ReverbSync($this);
        $products = $reverbSync->getListProductsWithStatusByProductId($id_product);
        $this->logs->infoLogs('Products to flag:');
        $this->logs->infoLogs(var_export($products, true));
        foreach ($products as $product) {
            $reverbSync->insertOrUpdateSyncStatus(
                $product['id_product'],
                $product['id_product_attribute'],
                \Reverb\ReverbProduct::REVERB_CODE_TO_SYNC,
                null,
                null,
                null,
                $origin
            );
        }
    }

    /**
     * Get Attribute ID from product
     *
     * @param int $idProduct
     * @return int|false
     */
    public function getAttributesId($idProduct)
    {
        $sql = new DbQuery();
        $sql->select('ra.id_attribute')
            ->from('reverb_attributes', 'ra')
            ->where('ra.`id_product` = ' . $idProduct)
            ->where('ra.`id_lang` = ' . $this->language_id);
        $result = Db::getInstance()->getValue($sql);
        return $result;
    }

    /**
     * Get Attributes from product
     *
     * @param int $idProduct
     * @return array
     */
    public function getAttribute($idProduct, $massEdit = false)
    {
        $sql = new DbQuery();
        $sql->select('ra.*')
            ->from('reverb_attributes', 'ra')
            ->where('ra.`id_product` = ' . $idProduct);

        if(!$massEdit) {
            $sql->where('ra.`id_lang` = ' . $this->language_id);
        }

        $result = Db::getInstance()->getRow($sql);
        return $result;
    }

    /**
     *  Hook for reverb's syncronization
     *
     * @param $params
     */
    public function hookActionProductUpdate()
    {
        $this->logs->infoLogs(__METHOD__);
    }

    /**
     *  Hook for order update
     *
     * @param $params
     */
    public function hookActionOrderEdited()
    {
        $this->logs->infoLogs(__METHOD__);
    }

    /**
     *   Prepare view sync status
     *
     * @return HelperList
     */
    public function getViewSyncStatus()
    {
        //=========================================
        //         PREPARE VIEW
        //=========================================
        $helper = new HelperListReverb();

        $this->fields_list = array(
            'id_product' => array(
                'title' => $this->l('ID'),
                'width' => 30,
                'type' => 'int',
                'filter_key' => 'p!id_product'
            ),
            'reverb_id' => array(
                'title' => $this->l('Reverb ID'),
                'width' => 70,
                'type' => 'text',
                'filter_key' => 'rs!id_sync'
            ),
            'reference' => array(
                'title' => $this->l('SKU'),
                'width' => 140,
                'type' => 'text',
                'filter_key' => 'p!reference'
            ),
            'name' => array(
                'title' => $this->l('Name'),
                'width' => 140,
                'type' => 'text',
                'filter_key' => 'pl!name'
            ),
            'status' => array(
                'title' => $this->l('Sync Status'),
                'width' => 50,
                'type' => 'select',
                'search' => true,
                'orderby' => true,
                'filter_key' => 'rs!status',
                'badge_success' => true,
                'list' => array('success' => 'success',
                                'error' => 'error',
                                'to_sync' => 'to_sync')
            ),
            'details' => array(
                'title' => $this->l('Sync Detail'),
                'width' => 300,
                'type' => 'text',
                'search' => 'true',
                'orderby' => 'true',
                'filter_key' => 'details'
            ),
            'date' => array(
                'title' => $this->l('Last synced'),
                'width' => 140,
                'type' => 'datetime',
                'filter_key' => 'date'
            ),
            'reverb_slug' => array(
                'title' => '',
                'search' => false,
                'orderby' => false,
            )
        );

        //=========================================
        //         GET DATAS FOR LIST
        //=========================================
        $datas = $this->reverbSync->getListProductsWithStatus($this->fields_list);

        $helper->override_folder = 'ReverbSync/';
        $helper->table = self::LIST_ID;
        $helper->allow_export = true;
        $helper->shopLinkType = '';
        $helper->default_pagination = 20;
        $helper->listTotal = $this->reverbSync->getListProductsWithStatusTotals($this->fields_list);
        $helper->module = $this;
        $helper->no_link = true;

        $helper->simple_header = false;
        $helper->show_toolbar = true;
        $helper->identifier = 'identifier';

        /*        $helper->toolbar_btn['new'] =  array(
                    'href' => AdminController::$currentIndex.'&configure='.$this->name.'&add'.$this->name.'&token='.Tools::getAdminTokenLite('AdminModules'),
                    'desc' => $this->l('Add new')
                );*/

        $helper->bulk_actions = array(
            'Syncronize' => array(
                'text' => $this->l('Syncronize selected products'),
                'icon' => 'icon-refresh',
                'confirm' => $this->l('Are you sure ?')
            ),
            'Syncronize_all' => array(
                'text' => $this->l('Syncronize all products'),
                'icon' => 'icon-refresh',
                'confirm' => $this->l('Are you sure ?')
            )
        );

        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            . '&configure=' . $this->name . '&module_name=' . $this->name;

        //=========================================
        //              GENERATE VIEW
        //=========================================
        return $helper->generateList($datas, $this->fields_list);
    }

    /**
     * Prepare view orders sync
     *
     * @return HelperList
     */
    public function getViewOrdersSync()
    {
        //=========================================
        //         PREPARE VIEW
        //=========================================
        $helper = new HelperListReverb();

        $fields_list = array(
            'id_order' => array(
                'title' => $this->l('ID'),
                'width' => 30,
                'type' => 'int',
                'filter_key' => 'id_order'
            ),
            'reverb_order_number' => array(
                'title' => $this->l('Reverb ID'),
                'width' => 70,
                'type' => 'text',
                'filter_key' => 'reverb_order_number'
            ),
            'reverb_product_sku' => array(
                'title' => $this->l('SKU'),
                'width' => 70,
                'type' => 'text',
                'filter_key' => 'reverb_product_sku'
            ),
            'status' => array(
                'title' => $this->l('Sync Status'),
                'width' => 50,
                'type' => 'select',
                'search' => true,
                'orderby' => true,
                'filter_key' => 'status',
                'badge_success' => true,
                'list' => ReverbOrders::getAllStatuses()
            ),
            'details' => array(
                'title' => $this->l('Sync Detail'),
                'width' => 300,
                'type' => 'text',
                'search' => 'true',
                'orderby' => 'true',
                'filter_key' => 'details'
            ),
            'updated_at' => array(
                'title' => $this->l('Last synced'),
                'width' => 140,
                'type' => 'datetime',
                'filter_key' => 'updated_at'
            ),
            'reverb_slug' => array(
                'title' => '',
                'search' => false,
                'orderby' => false,
            )
        );

        //=========================================
        //         GET DATAS FOR LIST
        //=========================================
        $datas = $this->reverbOrders->getOrdersList($fields_list);

        $helper->override_folder = 'ReverbOrders/';
        $helper->table = self::LIST_ORDERS_ID;
        $helper->allow_export = true;
        $helper->shopLinkType = '';
        $helper->default_pagination = 20;
        $helper->listTotal = $this->reverbOrders->getOrdersTotals($fields_list);
        $helper->module = $this;
        $helper->no_link = true;

        $helper->simple_header = false;
        $helper->show_toolbar = true;
        $helper->identifier = 'id_order';

        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            . '&configure=' . $this->name . '&module_name=' . $this->name;

        //=========================================
        //              GENERATE VIEW
        //=========================================
        return $helper->generateList($datas, $fields_list);
    }

    /**
     *   Prepare view mapping categories
     *
     * @return HelperList
     */
    public function getViewMappingCategories()
    {
        $id_shop = (int)$this->context->shop->id;

        //=========================================
        //         PREPARE VIEW
        //=========================================
        $helper = new HelperListReverb();

        $this->fields_list = array(
            'ps_category' => array(
                'title' => $this->l('Prestashop Name'),
                'width' => 70,
                'search' => false,
            ),
            'reverb_category' => array(
                'title' => $this->l('Reverb Name'),
                'width' => 70,
                'search' => false,
            ),
            'action' => array(
                'title' => $this->l('Action'),
                'width' => 70,
                'search' => false,
            ),
        );

        //=========================================
        //         GET DATAS FOR LIST
        //=========================================
        $datas = ReverbMapping::getFormattedPsCategories($this->context->language->id,$id_shop);

        $helper->override_folder = 'ReverbMapping/';
        $helper->table = self::LIST_CATEGORY_ID;
        $helper->allow_export = true;
        $helper->shopLinkType = '';
        $helper->default_pagination = ReverbMapping::DEFAULT_PAGINATION;
        $helper->listTotal = ReverbMapping::countPsCategories($this->context->language->id,$id_shop);
        $helper->module = $this;
        $helper->no_link = true;
        $helper->show_filters = false;

        $helper->simple_header = false;
        $helper->show_toolbar = true;
        $helper->identifier = 'ps_category';

        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            . '&configure=' . $this->name . '&module_name=' . $this->name;

        //=========================================
        //              GENERATE VIEW
        //=========================================
        return $helper->generateList($datas, $this->fields_list);
    }

    /**
     * @return string
     */
    public function getReverbUrl()
    {
        $url = $this->prod_url;

        if ((bool)$this->reverbConfig[self::KEY_SANDBOX_MODE] ||
            !array_key_exists(self::KEY_SANDBOX_MODE, $this->reverbConfig)
        ) {
            $url = $this->sandbox_url;
        }

        return $url;
    }

    /**
     * @return string
     */
    private function getReverbProductPreviewUrl()
    {
        return $this->getReverbUrl() . '/item/';
    }

    /**
     * @return string
     */
    private function getReverbOrderPreviewUrl()
    {
        return $this->getReverbUrl() . '/my/selling/orders/';
    }

    /**
     * @return Context
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     *  Check if Token for API is present and available
     *
     * @return boolean
     */
    public function isApiTokenAvailable()
    {
        if ((bool)$this->reverbConfig[self::KEY_SANDBOX_MODE] ||
            !array_key_exists(self::KEY_SANDBOX_MODE, $this->reverbConfig)
        ) {
            if (array_key_exists(self::KEY_API_TOKEN_SANDBOX, $this->reverbConfig)) {
                return $this->reverbConfig[self::KEY_API_TOKEN_SANDBOX];
            }
        } else {
            if (array_key_exists(self::KEY_API_TOKEN, $this->reverbConfig)) {
                return $this->reverbConfig[self::KEY_API_TOKEN];
            }
        }
        return false;
    }

    private function updateReverbOrderState()
    {
        $pending_payment_state_names = $blocked_state_names = $partially_paid_state_names = array();
        $setup = array(
            'delivery' => false,
            'hidden' => false,
            'invoice' => false,
            'logable' => false,
            'module_name' => $this->name,
            'send_email' => false,
        );
        foreach (Language::getLanguages(false) as $language) {
            if (Tools::strtolower($language['iso_code']) == 'fr') {
                $pending_payment_state_names[(int)$language['id_lang']] = 'Reverb - En attente de paiement';
                $blocked_state_names[(int)$language['id_lang']] = utf8_encode('Reverb - Bloqué');
                $partially_paid_state_names[(int)$language['id_lang']] = 'Reverb - Paiement partiel';
            } else {
                $pending_payment_state_names[(int)$language['id_lang']] = 'Reverb - Waiting for payment';
                $blocked_state_names[(int)$language['id_lang']] = 'Reverb - Blocked';
                $partially_paid_state_names[(int)$language['id_lang']] = 'Reverb - Partially paid';
            }
        }

        $statuses = array(
            array(
                'config' => 'REVERB_OS_PENDING_PAYMENT',
                'color' => '#FF8C00',
                'names' => $pending_payment_state_names,
            ),
            array(
                'config' => 'REVERB_OS_BLOCKED',
                'color' => '#FF8C00',
                'names' => $blocked_state_names,
            ),
            array(
                'config' => 'REVERB_OS_PARTIALLY_PAID',
                'color' => '#FF8C00',
                'names' => $partially_paid_state_names,
            ),
        );

        foreach ($statuses as $status) {
            $this->saveOrderState($status['config'], $status['color'], $status['names'], $setup);
        }

        return true;
    }

    private function saveOrderState($config, $color, $names, $setup)
    {
        $state_id = Configuration::get($config);
        if ((bool)$state_id == true) {
            $order_state = new OrderState($state_id);
        } else {
            $order_state = new OrderState();
        }
        $order_state->name = $names;
        $order_state->color = $color;
        foreach ($setup as $param => $value) {
            $order_state->{$param} = $value;
        }
        if ((bool)$state_id == true) {
            return $order_state->save();
        } elseif ($order_state->add() == true) {
            Configuration::updateValue($config, $order_state->id);

            // Copy status icon
            if (file_exists($this->local_path . 'views/img/status_pending.gif')) {
                @copy($this->local_path . 'views/img/status_pending.gif', _PS_ORDER_STATE_IMG_DIR_ . (int)$order_state->id . '.gif');
            }
            return true;
        }
        return false;
    }
}

require_once(dirname(__FILE__) . '/controllers/admin/AdminReverbConfigurationController.php');
require_once(dirname(__FILE__) . '/classes/helper/HelperListReverb.php');
require_once(dirname(__FILE__) . '/classes/models/ReverbSync.php');
require_once(dirname(__FILE__) . '/classes/models/ReverbOrders.php');
require_once(dirname(__FILE__) . '/classes/models/ReverbMapping.php');
require_once(dirname(__FILE__) . '/classes/models/ReverbMapping.php');
require_once(dirname(__FILE__) . '/classes/models/ReverbAttributes.php');
require_once(dirname(__FILE__) . '/classes/mapper/models/AbstractModel.php');
require_once(dirname(__FILE__) . '/classes/mapper/models/Category.php');
require_once(dirname(__FILE__) . '/classes/mapper/models/Price.php');
require_once(dirname(__FILE__) . '/classes/mapper/models/Seller.php');
require_once(dirname(__FILE__) . '/classes/mapper/models/Condition.php');
require_once(dirname(__FILE__) . '/classes/ReverbLogs.php');
require_once(dirname(__FILE__) . '/classes/ReverbClient.php');
require_once(dirname(__FILE__) . '/classes/ReverbAuth.php');
require_once(dirname(__FILE__) . '/classes/ReverbCategories.php');
require_once(dirname(__FILE__) . '/classes/ReverbConditions.php');
require_once(dirname(__FILE__) . '/classes/ReverbShippingRegions.php');
require_once(dirname(__FILE__) . '/classes/ReverbProduct.php');
require_once(dirname(__FILE__) . '/classes/ReverbOrders.php');
require_once(dirname(__FILE__) . '/classes/ReverbShop.php');
require_once(dirname(__FILE__) . '/classes/mapper/ProductMapper.php');
require_once(dirname(__FILE__) . '/classes/helper/RequestSerializer.php');
