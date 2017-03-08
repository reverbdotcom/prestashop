<?php
/**
* 2007-2015 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author    PrestaShop SA <contact@prestashop.com>
*  @copyright 2007-2015 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

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

    protected $config_form = false;
    public $configReverb;
    public $logs;

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
        $this->description = $this->l('Sync your inventory to and from Reverb, and streamline shipping.');

        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);

        $this->configReverb = $this->getConfigReverb();
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
            `id_product` int(11) NOT NULL,
            `reverb_ref` varchar(32) NOT NULL,
            `status` varchar(32) NOT NULL,
            `details` text,
            `date` datetime,
            PRIMARY KEY  (`id_sync`)
        ) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8;';

        $sql[] = 'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'reverb_mapping` (
            `id_mapping` int(11) NOT NULL AUTO_INCREMENT,
            `id_category` int(11) NOT NULL,
            `reverb_code` varchar(32) NOT NULL,
            PRIMARY KEY  (`id_mapping`)
        ) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8;';

        foreach ($sql as $query) {
            if (Db::getInstance()->execute($query) == false) {
                return false;
            }
        }

        return parent::install() &&
            $this->registerHook('backOfficeHeader') &&
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

        foreach ($sql as $query) {
            if (Db::getInstance()->execute($query) == false) {
                return false;
            }
        }

        return parent::uninstall();
    }

    /**
     * Load the configuration form
     */
    public function getContent()
    {

        $this->postProcess();

        /* @deprecated: Build request access uri */
        if (false && empty($this->configReverb[self::KEY_API_TOKEN])) {
            $reverbAuth = new \Reverb\ReverbAuth($this);
            $randomState = uniqid();
            $this->updateReverbConfiguration(self::KEY_RANDOM_STATE, $randomState);
            $this->context->smarty->assign([
                self::KEY_APP_REDIRECT_URI => $reverbAuth->getRequestAccessUrl($randomState),
            ]);
        }

        if (!empty($this->configReverb[self::KEY_API_TOKEN])) {
            $reverbCategories = new \Reverb\ReverbCategories($this);
            $this->context->smarty->assign([
                'categories' => $reverbCategories->getCategories(),
            ]);
        }

        $this->context->smarty->assign([
            'module_dir' => $this->_path,
            'reverb_form' => $this->renderForm(),
        ]);

        $output = $this->context->smarty->fetch($this->local_path.'views/templates/admin/configure.tpl');

        return $output;
    }

    /**
     * Create the form that will be displayed in the configuration of your module.
     */
    protected function renderForm()
    {
        $helper = new HelperForm();

        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitReverbModuleConfiguration';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            .'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFormValues(), /* Add values for your inputs */
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );

        return $helper->generateForm(array($this->getConfigForm()));
    }

    /**
     * Create the structure of your form.
     */
    protected function getConfigForm()
    {
        return array(
            'form' => array(
                'legend' => array(
                'title' => $this->l('Settings'),
                'icon' => 'icon-cogs',
                ),
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
     * Set values for the inputs.
     */
    protected function getConfigFormValues()
    {
        return array(
            self::KEY_SANDBOX_MODE => $this->getConfigReverb(self::KEY_SANDBOX_MODE),
            self::KEY_API_TOKEN => $this->getConfigReverb(self::KEY_API_TOKEN),
        );
    }

    /**
     * Functions to init the reverb configuration
     * or get all or specific reverb configuration
     */
    public function getConfigReverb($key = null)
    {
        // init multistore
        $id_shop = (int)$this->context->shop->id;
        $id_shop_group = (int)Shop::getContextShopGroupID();
        $confReverb = Configuration::get(self::KEY_REVERB_CONFIG, null, $id_shop_group, $id_shop);

        // if config exist but empty, init new object for confReverb
        if (!$confReverb || empty($confReverb)) {
            // init multistore
            $id_shop = (int)$this->context->shop->id;
            $id_shop_group = (int)Shop::getContextShopGroupID();

            $for_json_reverb = [
                self::KEY_SANDBOX_MODE => true,
            ];

            // the config is stacked in JSON
            if (!Configuration::updateValue(self::KEY_REVERB_CONFIG, Tools::jsonEncode($for_json_reverb), false, $id_shop_group, $id_shop)) {
                throw new Exception($this->l('Update failed, try again.'));
            }
        }

        // not empty in bdd and the config is stacked in JSON
        $result = Tools::jsonDecode(Configuration::get(self::KEY_REVERB_CONFIG, null, $id_shop_group, $id_shop), true);

        if ($key) {
            return isset($result[$key]) ? $result[$key] : null;
        }
        return $result;
    }

    protected function updateReverbConfiguration($key, $value)
    {
        $reverbConfig = $this->getConfigReverb();

        $reverbConfig[$key] = $value;

        // init multistore
        $id_shop = (int)$this->context->shop->id;
        $id_shop_group = (int)Shop::getContextShopGroupID();

        if (!Configuration::updateValue(self::KEY_REVERB_CONFIG, Tools::jsonEncode($reverbConfig), false, $id_shop_group, $id_shop)) {
            throw new Exception($this->l('Update failed, try again.'));
        }
        $this->configReverb = $reverbConfig;
    }

    /**
     * Save form data.
     */
    protected function postProcess()
    {
        // First form with api token and env mode
        if (Tools::isSubmit('submitReverbModuleConfiguration')) {
            $form_values = $this->getConfigFormValues();

            foreach (array_keys($form_values) as $key) {
                $this->updateReverbConfiguration($key, Tools::getValue($key));
            }
        }
    }

    /**
    * Add the CSS & JavaScript files you want to be loaded in the BO.
    */
    public function hookBackOfficeHeader()
    {
        if (Tools::getValue('module_name') == $this->name) {
            $this->context->controller->addJS($this->_path.'views/js/back.js');
            $this->context->controller->addCSS($this->_path.'views/css/back.css');
        }
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
}

require_once(dirname(__FILE__) . '/classes/ReverbLogs.php');
require_once(dirname(__FILE__) . '/classes/ReverbClient.php');
require_once(dirname(__FILE__) . '/classes/ReverbAuth.php');
require_once(dirname(__FILE__) . '/classes/ReverbCategories.php');
