<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

class Reverb extends Module
{
    CONST KEY_REVERB_CONFIG = 'REVERB_CONFIG';
    CONST KEY_SANDBOX_MODE = 'sandbox_mode';
    CONST KEY_APP_CLIENT_ID = 'app_client_id';
    CONST KEY_APP_REDIRECT_URI = 'app_redirect_api';
    CONST KEY_API_TOKEN = 'api_token';
    CONST KEY_RANDOM_STATE = 'random_state';
    CONST KEY_DEBUG_MODE = 'debug_mode';
    CONST KEY_SETTINGS_AUTO_SYNC = 'settings_auto_sync';
    CONST KEY_SETTINGS_AUTO_PUBLISH = 'settings_auto_publish';
    CONST KEY_SETTINGS_CREATE_NEW_LISTINGS = 'settings_create_new_listings';
    CONST KEY_SETTINGS_DESCRIPTION = 'settings_description';
    CONST KEY_SETTINGS_PHOTOS = 'settings_photos';
    CONST KEY_SETTINGS_CONDITION = 'settings_condition';
    CONST KEY_SETTINGS_PRICE = 'settings_price';

    CONST LIST_ID = 'ps_product';

    protected $config_form = false;
    public $reverbConfig;
    public $logs;
    public $active_tab;
    protected $class_names = array(
        'AdminReverbConfiguration',
    );

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
        $this->logs = new \Reverb\ReverLogs($this);

        $this->displayName = $this->l('Reverb');
        $this->description = $this->l('Reverb is the best place anywhere to sell your guitar, amp, drums, bass, or other music gear.Built for and by musicians, our marketplace offers a broad variety of tools to help buy and sell, and has the lowest transaction fees of any online marketplace.');

        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);

        $this->reverbConfig = $this->getReverbConfig();
    }

    /**
     * Don't forget to create update methods if needed:
     * http://doc.prestashop.com/display/PS16/Enabling+the+Auto-Update
     */
    public function install()
    {
        Configuration::updateValue(self::KEY_REVERB_CONFIG, '');

        $sql = array();
        $sql[] = 'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'reverb_sync` (
            `id_sync` int(11) NOT NULL AUTO_INCREMENT,
            `id_product` int(10) unsigned NOT NULL,
            `reverb_ref` varchar(32) NOT NULL,
            `status` varchar(32) NOT NULL,
            `details` text,
            `url_reverb` varchar(150) ,
            `date` datetime,
            PRIMARY KEY  (`id_sync`),
            FOREIGN KEY fk_rever_sync_product(id_product) REFERENCES `'._DB_PREFIX_.'product` (id_product)
        ) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8;';

        $sql[] = 'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'reverb_mapping` (
            `id_mapping` int(11) NOT NULL AUTO_INCREMENT,
            `id_category` int(11) NOT NULL,
            `reverb_code` varchar(50) NOT NULL,
            PRIMARY KEY  (`id_mapping`)
        ) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8;';

        /**
         *     CUSTOMS FIELDS ON PRODUCT TABLE
         */
        $sql[] = 'ALTER TABLE `'._DB_PREFIX_.'product` ADD `reverb_enabled` tinyint(1);';

        foreach ($sql as $query) {
            if (Db::getInstance()->execute($query) == false) {
                return false;
            }
        }

        return parent::install() &&
            $this->createAdminTab() &&
            $this->registerHook('displayBackOfficeHeader') &&
            $this->registerHook('displayAdminProductsExtra') &&
            $this->registerHook('actionObjectOrderAddAfter') &&
            $this->registerHook('actionObjectProductAddAfter') &&
            $this->registerHook('actionObjectProductDeleteAfter') &&
            $this->registerHook('actionObjectProductUpdateAfter');
    }

    public function uninstall()
    {
        Configuration::deleteByName(self::KEY_REVERB_CONFIG);

        $sql = array();

        $sql[] = 'DROP TABLE IF EXISTS `'._DB_PREFIX_.'reverb_sync`;';
        $sql[] = 'DROP TABLE IF EXISTS `'._DB_PREFIX_.'reverb_mapping`;';
        /**
         *     CUSTOMS FIELDS ON PRODUCT TABLE
         */
        $sql[] = 'ALTER TABLE `'._DB_PREFIX_.'product` DROP `reverb_enabled`;';

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

        /* @deprecated: Build request access uri */
        if (false && empty($this->reverbConfig[self::KEY_API_TOKEN])) {
            $reverbAuth = new \Reverb\ReverbAuth($this);
            $randomState = uniqid();
            $this->reverbConfig[self::KEY_RANDOM_STATE] = $randomState;
            $this->saveReverbConfiguration();
            $this->context->smarty->assign(array(
                self::KEY_APP_REDIRECT_URI => $reverbAuth->getRequestAccessUrl($randomState),
            ));
        }

        if (!empty($this->reverbConfig[self::KEY_API_TOKEN])) {
            $reverbCategories = new \Reverb\ReverbCategories($this);

            $this->context->smarty->assign(array(
                'reverb_categories' => $reverbCategories->getFormattedCategories(),
                'ps_categories' => ReverbMapping::getFormattedPsCategories($this->context->language->id),
                'is_logged' => true,
                'token' => Tools::getAdminTokenLite('AdminModules'),
            ));
            if (!$this->active_tab) {
                $this->active_tab = 'sync_status';
            }
        } else {
            $this->context->smarty->assign(array(
                'active_tab' => 'login',
                'is_logged' => false,
            ));
        }

        $this->context->smarty->assign(array(
            'module_dir' => $this->_path,
            'reverb_login_form' => $this->renderLoginForm(),
            'reverb_settings_form' => $this->renderSettingsForm(),
            'reverb_config' => $this->reverbConfig,
            'reverb_sync_status' => $this->getViewSyncStatus(),
            'logs' => $this->getLogFiles(),
            'active_tab' => $this->active_tab,
            'ajax_url' => $this->context->link->getAdminLink('AdminReverbConfiguration'),
            ));
        $output = $this->context->smarty->fetch($this->local_path.'views/templates/admin/configure.tpl');
        if (Tools::isSubmit('submitFilter')) {

        }
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
        $error_files = [];
        $info_files = [];
        $callback_files = [];
        $request_files = [];
        $refund_files = [];
        // dispatch files
        foreach ($files as $file) {
            if (preg_match("/error/i", $file) && count($error_files) < 10) {
                $error_files[] = $file;
            }
            if (preg_match("/callback/i", $file) && count($callback_files) < 10) {
                $callback_files[] = $file;
            }
            if (preg_match("/infos/i", $file) && count($info_files) < 10) {
                $info_files[] = $file;
            }
            if (preg_match("/request/i", $file) && count($request_files) < 10) {
                $request_files[] = $file;
            }
            if (preg_match("/refund/i", $file) && count($refund_files) < 10) {
                $refund_files[] = $file;
            }
        }
        return [
            'error' => $error_files,
            'infos' => $info_files,
            'callback' => $callback_files,
            'request' => $request_files,
            'refund' => $refund_files
        ];
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
            .'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
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
            .'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
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
                        'col' => 3,
                        'type' => 'text',
                        'desc' => $this->l('From https://reverb.com/my/api_settings'),
                        'name' => self::KEY_API_TOKEN,
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
                'name' => self::KEY_SETTINGS_AUTO_PUBLISH,
                'label' => $this->l('Automatically publish listings after sync'),
                'desc' => $this->l('To publish the listing right away requires more fields such as images and shipping rates, and may not always be possible.'),
            ),
            array(
                'name' => self::KEY_SETTINGS_CREATE_NEW_LISTINGS,
                'label' => $this->l('Create new listings'),
                'desc' => $this->l('If the settings is off, only updates will be synced. New listings will not be automatically created.'),
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
        );

        $input = array();
        foreach ($fields as $field) {
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
            if (!Configuration::updateValue(self::KEY_REVERB_CONFIG, Tools::jsonEncode($this->reverbConfig), false, $id_shop_group, $id_shop)) {
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

        if (!Configuration::updateValue(self::KEY_REVERB_CONFIG, Tools::jsonEncode($this->reverbConfig), false, $id_shop_group, $id_shop)) {
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
                $this->reverbConfig[$key] = Tools::getValue($key);
            }

            $this->saveReverbConfiguration();
        }

        // Settings form
        if (Tools::isSubmit('submitReverbModuleSettings')) {
            $form_values = $this->getConfigSettingsFormValues();

            foreach (array_keys($form_values) as $key) {
                $this->reverbConfig[$key] = Tools::getValue($key);
            }

            $this->saveReverbConfiguration();
        }
    }

    /**
    * Add the CSS & JavaScript files you want to be loaded in the BO.
    */
    public function hookDisplayBackOfficeHeader()
    {
        if (Tools::getValue('configure') == $this->name) {
            $this->context->controller->addJS($this->_path.'views/js/back.js','all');
            $this->context->controller->addCSS($this->_path.'views/css/back.css','all');
        }
    }

    public function hookDisplayAdminProductsExtra($params) {

        /* Exemple pour le front afin de setter le title et le content
        
        $array = array();
        $array[] = (new PrestaShop\PrestaShop\Core\Product\ProductExtraContent())
                ->setTitle('Reverb Sync')
                ->setContent('Reverb content: lorem ipsum...');
        return $array;*/

        $output = $this->context->smarty->fetch($this->local_path.'views/templates/admin/product/product-tab-content.tpl');
        return $output;
    }

    public function hookActionObjectOrderAddAfter()
    {
        /* Place your code here. */
    }

    public function hookActionObjectProductAddAfter()
    {
        /* Place your code here. */
    }

    public function hookActionObjectProductDeleteAfter()
    {
        /* Place your code here. */
    }

    public function hookActionObjectShopUpdateAfter()
    {
        /* Place your code here. */
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
                'title' => $this->l('Product'),
                'width' => 70,
                'type' => 'int',
                'filter_key' => 'p.id_product'
            ),
            'id_sync' => array(
                'title' => $this->l('Variant'),
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
            'status' => array(
                'title' => $this->l('Sync Status'),
                'width' => 100,
                'type' => 'select',
                'search' => true,
                'orderby' => true,
                'cast' => 'intval',
                'identifier' => 'name',
                'filter_key' => 'status',
                'badge_success' => true,
                'list' => array('success','error')
            ),
            'details' => array(
                'title' => $this->l('Sync Detail'),
                'width' => 300,
                'type' => 'text',
                'search' => 'true',
                'orderby' => 'true',
                'filter_key' => 'details'
            ),
            'last_synced' => array(
                'title' => $this->l('Last synced'),
                'width' => 140,
                'type' => 'text',
                'filter_key' => 'last_synced'
            ),
            'url_reverb' => array(
                'title' => '',
                'width' => 230,
                'type' => 'text',
                'filter_key' => 'last_synced',
                'search' => false,
                'orderby' => false,
            ),
        );

        //=========================================
        //         GET DATAS FOR LIST
        //=========================================
        $datas = ReverbSync::getListProductsWithStatus($this->fields_list);

        $helper->override_folder = 'ReverbSync/';
        $helper->table = self::LIST_ID;
        $helper->allow_export = true;
        $helper->shopLinkType = '';
        $helper->default_pagination = 20;
        $helper->listTotal = ReverbSync::getListProductsWithStatusTotals($this->fields_list);
        $helper->module = $this;
        $helper->no_link = true;

        $helper->simple_header = false;
        $helper->show_toolbar = true;
        $helper->identifier = 'id_product';

/*        $helper->toolbar_btn['new'] =  array(
            'href' => AdminController::$currentIndex.'&configure='.$this->name.'&add'.$this->name.'&token='.Tools::getAdminTokenLite('AdminModules'),
            'desc' => $this->l('Add new')
        );*/

        $helper->bulk_actions = array(
            'Syncronize' => array(
                'text' => $this->l('Syncronize selected products'),
                'icon' => 'icon-trash',
                'confirm' => $this->l('Are you sure ?')
            )
        );

        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            .'&configure='.$this->name. '&module_name='.$this->name;

        //=========================================
        //              GENERATE VIEW
        //=========================================
        return $helper->generateList($datas, $this->fields_list);
    }

    /**
     *  Proccess ajax call from view
     *
     */
    public function ajaxProcessSyncronizeProduct(){
        die(json_encode(array(
            'result' => true,
        )));
    }

    /**
     * Checks if the page has been called from XmlHttpRequest (AJAX)
     * @return bool
     */
    private function isXmlHttpRequest()
    {
        return (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest');
    }
}

require_once(dirname(__FILE__) . '/classes/helper/HelperListReverb.php');
require_once(dirname(__FILE__) . '/classes/models/ReverbSync.php');
require_once(dirname(__FILE__) . '/classes/models/ReverbMapping.php');
require_once(dirname(__FILE__) . '/classes/ReverbLogs.php');
require_once(dirname(__FILE__) . '/classes/ReverbClient.php');
require_once(dirname(__FILE__) . '/classes/ReverbAuth.php');
require_once(dirname(__FILE__) . '/classes/ReverbCategories.php');
