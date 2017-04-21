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
    const KEY_SETTINGS_PAYPAL = 'settings_paypal';

    const LIST_ID = 'ps_product';
    const LIST_CATEGORY_ID = 'ps_mapping_category';

    protected $config_form = false;
    public $reverbConfig;
    public $reverbSync;
    public $reverbOrders;
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
        $this->version = '1.0.0';
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

        $this->displayName = $this->l('Reverb');
        $this->description = $this->l('Reverb is the best place anywhere to sell your guitar, amp, drums, bass, or other music gear.Built for and by musicians, our marketplace offers a broad variety of tools to help buy and sell, and has the lowest transaction fees of any online marketplace.');

        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);

        $this->language_id = $this->context->language->id;

        $this->reverbConfig = $this->getReverbConfig();

        $this->_infos[] = $this->l('The following cron tasks must be configured in your hosting:');
        $this->_infos[] = '*/5 * * * *  php /var/www/html/modules/reverb/cron.php products  > /var/log/cron.log';
        $this->_infos[] = '*/8 * * * *  php /var/www/html/modules/reverb/cron.php orders > /var/log/cron.log';
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
            FOREIGN KEY fk_reverb_sync_product(id_product) REFERENCES `' . _DB_PREFIX_ . 'product` (id_product),
            FOREIGN KEY fk_reverb_sync_product_2(id_product_attribute) REFERENCES `' . _DB_PREFIX_ . 'product_attribute` (id_product_attribute),
            UNIQUE (id_product, id_product_attribute)
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

        $sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'reverb_mapping` (
            `id_mapping` int(10) unsigned NOT NULL AUTO_INCREMENT,
            `id_category` int(10) unsigned NOT NULL,
            `reverb_code` varchar(50) NOT NULL,
            PRIMARY KEY  (`id_mapping`),
            FOREIGN KEY fk_reverb_mapping_category(id_category) REFERENCES `' . _DB_PREFIX_ . 'category` (id_category),
            UNIQUE (id_category)
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

        $sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'reverb_attributes` (
            `id_attribute` int(10) unsigned NOT NULL AUTO_INCREMENT,
            `id_product` int(10) unsigned NOT NULL,
            `reverb_enabled` tinyint(1),
            `id_lang` ' . (version_compare(_PS_VERSION_, '1.7', '<') ? 'int(10) unsigned' : 'int(11)') . ' NOT NULL,
            `offers_enabled` tinyint(1),
            `finish` varchar(50),
            `origin_country_code` varchar(50),
            `year` varchar(50),
            `id_condition` varchar(50),
            `id_shipping_profile` int(10) unsigned,
            `shipping_local` tinyint(1),
            PRIMARY KEY (`id_attribute`),
            FOREIGN KEY fk_reverb_attributes_product(id_product) REFERENCES `' . _DB_PREFIX_ . 'product` (id_product),
            FOREIGN KEY fk_reverb_attributes_lang(id_lang) REFERENCES `' . _DB_PREFIX_ . 'lang` (id_lang),
            UNIQUE (id_product)
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

        $sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'reverb_shipping_methods` (
            `id_shipping_method` int(10) unsigned NOT NULL AUTO_INCREMENT,
            `id_attribute` int(10) unsigned NOT NULL,
            `region_code` varchar(50) NOT NULL,
            `rate` decimal(20,2) NOT NULL,
            PRIMARY KEY (`id_shipping_method`),
            FOREIGN KEY fk_reverb_shipping_methods_attribute(id_attribute) REFERENCES `' . _DB_PREFIX_ . 'reverb_attributes` (id_attribute),
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
            FOREIGN KEY fk_reverb_sync_history_product(id_product) REFERENCES `' . _DB_PREFIX_ . 'product` (id_product),
            FOREIGN KEY fk_reverb_sync_history_product_attribute(id_product_attribute) REFERENCES `' . _DB_PREFIX_ . 'product_attribute` (id_product_attribute)
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

        $sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'reverb_orders` (
            `id_reverb_orders` int(10) unsigned NOT NULL AUTO_INCREMENT,
            `id_order` int(10) unsigned,
            `id_shop` int(11) unsigned,
            `id_shop_group` int(11) unsigned,
            `reverb_order_number` varchar(32),
            `status` varchar(32) NOT NULL,
            `details` text,
            `date` datetime,
            `shipping_method` varchar(32),
            `shipping_tracker` text,
            PRIMARY KEY  (`id_reverb_orders`)
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';


        foreach ($sql as $query) {
            if (Db::getInstance()->execute($query) == false) {
                return false;
            }
        }


        return parent::install() &&
            $this->createAdminTab() &&
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
            $this->registerHook('actionOrderEdited');
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

        if ($this->isApiTokenAvailable()) {
            $reverbCategories = new \Reverb\ReverbCategories($this);

            $module_url = $this->context->link->getAdminLink('AdminModules', true)
                . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;

            $this->context->smarty->assign(array(
                'reverb_categories' => $reverbCategories->getFormattedCategories(),
                'is_logged' => true,
                'token' => Tools::getAdminTokenLite('AdminModules'),
                'reverb_product_preview_url' => $this->getReverbProductPreviewUrl(),
                'ps_product_preview_base_url' => _PS_BASE_URL_,
                'module_url' => $module_url,
            ));
            if (!$this->active_tab) {
                $this->active_tab = 'sync_status';
            }
        } else {
            $this->context->smarty->assign(array(
                'active_tab' => 'login',
                'is_logged' => false,
                'reverb_product_preview_url' => '',
                'ps_product_preview_base_url' => '',
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
            'reverb_mapping_categories' => $this->getViewMappingCategories(),
            'logs' => $this->getLogFiles(),
            'active_tab' => $this->active_tab,
            'ajax_url' => $this->context->link->getAdminLink('AdminReverbConfiguration'),
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
                        'desc' => '<a id="reverb-url-help" href="" target="_blank">' . $this->l('From https://reverb.com/my/api_settings') . '</a>',
                        'name' => self::KEY_API_TOKEN,
                        'label' => $this->l('API Token'),
                    ),
                    array(
                        'id' => 'token_sandbox',
                        'col' => 3,
                        'type' => 'text',
                        'desc' => '<a id="reverb-url-help" href="" target="_blank">' . $this->l('From https://sandbox.reverb.com/my/api_settings') . '</a>',
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
            ),
            array(
                'name' => self::KEY_SETTINGS_PAYPAL,
                'label' => $this->l('Paypal email'),
                'desc' => $this->l('Put your Paypal email'),
                'type' => 'text'
            ),
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
            self::KEY_SETTINGS_PAYPAL => $this->getReverbConfig(self::KEY_SETTINGS_PAYPAL),
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
                    $reverbClient = new \Reverb\ReverbAuth($this, $value);
                    $shop = $reverbClient->getListFromEndpoint(null, null, false);
                    $mode = 'PRODUCTION';
                    if ((bool)$this->reverbConfig[self::KEY_SANDBOX_MODE] ||
                        !array_key_exists(self::KEY_SANDBOX_MODE, $this->reverbConfig)
                    ) {
                        $mode = 'SANDBOX';
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

        // Bulk sync all
        if (Tools::isSubmit('submitFilterps_product')) {
            $identifiers = Tools::getValue('ps_productBox');
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
     * Add the CSS & JavaScript files you want to be loaded in the BO.
     */
    public function hookBackOfficeHeader()
    {
        if (Tools::getValue('controller') == 'AdminProducts' || Tools::getValue('configure') == $this->name) {
            $this->context->controller->addJS($this->_path . 'views/js/back.js');
            $this->context->controller->addCSS($this->_path . 'views/css/back.css', 'all');
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

            $attribute = $reverbAttributes->getAttributes($id_product);

            $this->context->smarty->assign(array(
                'reverb_enabled' => $attribute['reverb_enabled'],
                'reverb_finish' => $attribute['finish'],
                'reverb_condition' => $attribute['id_condition'],
                'reverb_year' => $attribute['year'],
                'reverb_offers_enabled' => $attribute['offers_enabled'],
                'reverb_country' => $attribute['origin_country_code'],
                'reverb_list_conditions' => $reverbConditions->getFormattedConditions(),
                'reverb_list_country' => Country::getCountries($this->context->language->id),
                'reverb_url' => $this->getReverbUrl(),
                'reverb_regions' => $reverbShippingRegions->getFormattedShippingRegions(),
                'reverb_shipping_profile' => $attribute['id_shipping_profile'],
                'reverb_shipping_local' => $attribute['shipping_local'],
                'reverb_shipping_methods' => $reverbAttributes->getShippingMethods($attribute['id_attribute']),
                'currency' => $this->getContext()->currency->getSign(),
                'admin_css' => _PS_CSS_DIR_,
                'admin_css' => _PS_ADMIN_DIR_,
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
            $reverbOrder = $this->reverbOrders->getOrderByReference($order->reference);
            if (!empty($reverbOrder)) {
                $this->logs->infoLogs(' - Order ' . $id_order . ' : ' . $order->reference . ' comes from Reverb !');

                $trackingNumber = Tools::getValue('shipping_tracking_number');
                if (!empty($trackingNumber)) {
                    $this->reverbOrders->update($id_order, array(
                        'shipping_tracker' => $trackingNumber,
                        'status' => Reverb,
                        Orders::REVERB_ORDERS_STATUS_SHIPPING_SENT
                    ));
                    $reverbOrders = new \Reverb\ReverbOrders($this);

                    $carrier = new Carrier((int)Tools::getValue('shipping_carrier'), $this->language_id);
                    $provider = $carrier->name;
                    $reverbOrders->setOrderShip($reverbOrder['reverb_order_number'], $provider, $trackingNumber);
                }
            }
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
                $reverbOrder = $this->reverbOrders->getOrderByReference($order->reference);
                if (!empty($reverbOrder) && $reverbOrder['status'] == ReverbOrders::REVERB_ORDERS_STATUS_ORDER_SAVED) {
                    $this->logs->infoLogs(' - Order ' . $id_order . ' : ' . $order->reference . ' comes from Reverb and not sent yet !');

                    if ($reverbOrder['shipping_method'] == ReverbOrders::REVERB_ORDERS_SHIPPING_METHOD_LOCAL) {
                        $this->logs->infoLogs(' - Reverb order shipping method set to local');
                        $reverbOrders = new \Reverb\ReverbOrders($this);
                        $reverbOrders->setOrderPickedUp($reverbOrder['reverb_order_number']);

                        $this->logs->infoLogs(' - Update reverb_orders');
                        $this->reverbOrders->update($id_order, array(
                            'status' => ReverbOrders::REVERB_ORDERS_STATUS_SHIPPING_SENT
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
            $settingsReverb = Tools::getValue('reverb_enabled');
            $condition = Tools::getValue('reverb_condition');
            $finish = Tools::getValue('reverb_finish');
            $year = Tools::getValue('reverb_year');
            $offers_enabled = Tools::getValue('offers_enabled');
            $reverb_country = Tools::getValue('reverb_country');
            $reverb_shipping = Tools::getValue('reverb_shipping');
            $reverb_shipping_profile = Tools::getValue('reverb_shipping_profile');
            $reverb_shipping_methods_region = Tools::getValue('reverb_shipping_methods_region');
            $reverb_shipping_methods_rate = Tools::getValue('reverb_shipping_methods_rate');
            $reverb_shipping_local = Tools::getValue('reverb_shipping_local');

            //TODO Controle de validité ?
            $values = array(
                'reverb_enabled' => pSQL($settingsReverb),
                'id_condition' => pSQL($condition),
                'finish' => pSQL($finish),
                'year' => pSql($year),
                'offers_enabled' => pSql($offers_enabled),
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
                    $db->insert('reverb_shipping_methods', array(
                        'id_attribute' => $idAttribute,
                        'region_code' => pSql($reverb_shipping_method_region),
                        'rate' => pSql($reverb_shipping_methods_rate[$key]),
                    ));
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
     * Get Attributes from product
     *
     * @param int $psCategoryId
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
     *  Hook for reverb's syncronization
     *
     * @param $params
     */
    public function hookActionProductUpdate()
    {
        Tools::getValue('id_product');
    }

    /**
     *  Hook for order update
     *
     * @param $params
     */
    public function hookActionOrderEdited()
    {
        $id_order = Tools::getValue('id_order');
        $this->logs->infoLogs('UPDATE order ' . $id_order);
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
                'filter_key' => 'p.id_product'
            ),
            'reverb_id' => array(
                'title' => $this->l('Reverb ID'),
                'width' => 70,
                'type' => 'text',
                'filter_key' => 'id_sync'
            ),
            'reference' => array(
                'title' => $this->l('SKU'),
                'width' => 140,
                'type' => 'text',
                'filter_key' => 'reference'
            ),
            'name' => array(
                'title' => $this->l('Name'),
                'width' => 140,
                'type' => 'text',
                'filter_key' => 'name'
            ),
            'status' => array(
                'title' => $this->l('Sync Status'),
                'width' => 50,
                'type' => 'select',
                'search' => true,
                'orderby' => true,
                'cast' => 'intval',
                'identifier' => 'name',
                'filter_key' => 'status',
                'badge_success' => true,
                'list' => array('success', 'error')
            ),
            'details' => array(
                'title' => $this->l('Sync Detail'),
                'width' => 200,
                'type' => 'text',
                'search' => 'true',
                'orderby' => 'true',
                'filter_key' => 'details'
            ),
            'last_sync' => array(
                'title' => $this->l('Last synced'),
                'width' => 140,
                'type' => 'datetime',
                'filter_key' => 'date'
            ),
            'reverb_slug' => array(
                'title' => '',
                'search' => false,
                'orderby' => false,
            ),
            'icon' => array(
                'title' => '',
                'type' => 'text',
                'filter_key' => 'last_synced',
                'search' => false,
                'orderby' => false,
            ),
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
     *   Prepare view mapping categories
     *
     * @return HelperList
     */
    public function getViewMappingCategories()
    {
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
        );

        //=========================================
        //         GET DATAS FOR LIST
        //=========================================
        $datas = ReverbMapping::getFormattedPsCategories($this->context->language->id);

        $helper->override_folder = 'ReverbMapping/';
        $helper->table = self::LIST_CATEGORY_ID;
        $helper->allow_export = true;
        $helper->shopLinkType = '';
        $helper->default_pagination = ReverbMapping::DEFAULT_PAGINATION;
        $helper->listTotal = ReverbMapping::countPsCategories($this->context->language->id);
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
        return $this->getReverbUrl() . '/preview/';
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
require_once(dirname(__FILE__) . '/classes/mapper/ProductMapper.php');
require_once(dirname(__FILE__) . '/classes/helper/RequestSerializer.php');
