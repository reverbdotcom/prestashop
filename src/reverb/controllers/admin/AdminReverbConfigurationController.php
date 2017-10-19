<?php
/**
 * Module Reverb
 *
 * @author Johan Protin
 * @copyright Copyright (c) 2017 - Johan Protin
 * @license Apache License Version 2.0, January 2004
 * @package Reverb
 */

require_once dirname(__FILE__) . '/../../classes/helper/HelperCron.php';
require_once dirname(__FILE__) . '/../../classes/crons/OrdersSyncEngine.php';

class AdminReverbConfigurationController extends ModuleAdminController
{
    public function __construct()
    {
        parent::__construct();

        if (!$this->module->active) {
            $this->sendErrorRequest('Invalid request.');
        }
    }

    /**
     * Upload ajax images payment button
     */
    public function ajaxProcessCategoryMapping()
    {
        $reverbMapping = new ReverbMapping($this->module);
        $psCategoryId = Tools::getValue('ps_category_id');
        $reverbCode = Tools::getValue('reverb_code');
        $mappingId = Tools::getValue('mapping_id');

        $newMappingId = $reverbMapping->createOrUpdateMapping($psCategoryId, $reverbCode, $mappingId);

        // return the saved mapping ID
        die($newMappingId);
    }

    /**
     *  Proccess ajax call from view Sync status
     */
    public function ajaxProcessSyncronizeProduct()
    {
        if (!$this->module instanceof Reverb) {
            die(json_encode(array('status' => 'error', 'message' => 'An error occured')));
        }

        $identifier = Tools::getValue('identifier');

        $identifiers = explode('-', $identifier);

        if (!empty($identifiers) && count($identifiers) == 2) {
            $id_product = $identifiers[0];
            $id_product_attribute = $identifiers[1];

            if (!empty($id_product)) {
                $reverbProduct = new \Reverb\ReverbProduct($this->module);

                $product = $this->module->reverbSync->getProductWithStatus($id_product, $id_product_attribute);

                if (!empty($product)) {
                    $res = $reverbProduct->syncProduct($product, ReverbSync::ORIGIN_MANUAL_SYNC_SINGLE);
                    die(json_encode($res));
                } else {
                    die(json_encode(array(
                        'status' => 'error',
                        'message' => 'No product found for ID ' . $id_product . ' and lang ' . $this->module->language_id
                    )));
                }
            } else {
                die(json_encode(array(
                    'status' => 'error',
                    'message' => 'No product found for ID ' . $id_product . ' and lang ' . $this->module->language_id
                )));
            }
        }
        die(json_encode(array('status' => 'error', 'An error occured')));
    }

    /**
     *  Proccess ajax call from order Sync status
     */
    public function ajaxProcessSyncronizeOrder()
    {
        if (!$this->module instanceof Reverb) {
            die(json_encode(array('status' => 'error', 'message' => 'An error occured')));
        }

        $reverbId = Tools::getValue('reverb-id');

        if (!empty($reverbId)) {
            // Call and getting an order from reverb
            $reverbOrders = new \Reverb\ReverbOrders($this->module);
            $distReverbOrder = $reverbOrders->getOrder($reverbId);

            if (!empty($distReverbOrder)) {
                $helper = new HelperCron($this->module);
                $context = new \ContextCron($this->module);
                $orderSyncEngine = new OrdersSyncEngine($this->module, $helper, $context);
                $response = $orderSyncEngine->syncOrder($distReverbOrder);
                die(json_encode($response));
            } else {
                die(json_encode(array(
                    'status' => ReverbOrders::REVERB_ORDERS_STATUS_ERROR,
                    'message' => 'No order found for ID ' . $reverbId
                )));
            }
        }
        die(json_encode(array('status' => 'error', 'message' => 'An error occured')));
    }

    /**
     *  Proccess ajax activation synchronization
     */
    public function ajaxProcessToggleActiveSyncronization()
    {
        if (!$this->module instanceof Reverb) {
            die(json_encode(array('status' => 'error', 'message' => 'An error occured')));
        }

        $id_product = Tools::getValue('id_product');

        if (!empty($id_product)) {
            try {
                $this->module->logs->infoLogs('### Toggle sync enabled for product : ' . $id_product);

                $attribute = $this->module->getAttribute($id_product, TRUE);
                $db = Db::getInstance();

                if (!empty($attribute)) {
                    $enabled = $attribute['reverb_enabled'] ? 0 : 1;
                    $this->module->logs->infoLogs('### $idAttribute = ' . $attribute['id_attribute']);
                    $db->update(
                        'reverb_attributes',
                        array('reverb_enabled' => $enabled),
                        'id_attribute = ' . (int)$attribute['id_attribute']
                    );
                    $this->module->logs->infoLogs('### fin update');
                } else {
                    $this->module->logs->infoLogs('### debut insert');
                    $enabled = 1;
                    $db->insert('reverb_attributes', array(
                        'reverb_enabled' => $enabled,
                        'id_lang' => pSql($this->module->language_id),
                        'id_product' => pSql($id_product),
                    ));

                    $this->module->logs->infoLogs('### get idAttribute');
                    $idAttribute = (int)$db->Insert_ID();
                    $this->module->logs->infoLogs('### fin insert - $idAttribute = ' . $idAttribute);
                }

                die(json_encode(array(
                    'status' => 'success',
                    'enabled' => $enabled,
                    'message' => 'Synchronization updated'
                )));

            } catch (\Exception $e) {
                $this->module->logs->errorLogs($e->getMessage());
            }
        } else {
            die(json_encode(array(
                'status' => 'error',
                'message' => 'No product found for ID ' . $id_product . ' and lang ' . $this->module->language_id
            )));
        }
        die(json_encode(array('status' => 'error', 'message' => 'An error occured')));
    }

    /**
     *  Proccess ajax load product
     */
    public function ajaxProcessLoadProduct()
    {
        if (!$this->module instanceof Reverb) {
            die(json_encode(array('status' => 'error', 'message' => 'An error occured')));
        }

        $id_product = Tools::getValue('id_product');

        if (!empty($id_product)) {
            try {
                $products = $this->module->reverbSync->getProductsByIds(array($id_product));
                if (!empty($id_product)) {
                    $product = $products[0];
                    if ($product['shippings']) {
                        $shippings = explode('|', $product['shippings']);
                        $formattedShippings = array_map(function($shipping){
                            $explode = explode(':', $shipping);
                            return array('location' => $explode[0], 'rate' => $explode[1]);
                        }, $shippings);

                        $product['shippings'] = $formattedShippings;
                    } else {
                        $product['shippings'] = array();
                    }
                    die(json_encode($product));
                } else {
                    die(json_encode(array(
                        'status' => 'error',
                        'message' => 'No product found for ID ' . $id_product . ' and lang ' . $this->module->language_id
                    )));
                }
            } catch (\Exception $e) {
                $this->module->logs->errorLogs($e->getMessage());
            }
        } else {
            die(json_encode(array(
                'status' => 'error',
                'message' => 'No products selected'
            )));
        }
        die(json_encode(array('status' => 'error', 'message' => 'An error occured')));
    }

    public function ajaxProcessSearchProductMassEdit()
    {
        if (!$this->module instanceof Reverb) {
            die(json_encode(array('status' => 'error', 'message' => 'An error occured')));
        }

        $search = array();
        if (Tools::getValue('tags_reverb_search')) {
            $search = explode(',', Tools::getValue('tags_reverb_search'));
        }

        $page = Tools::getValue('page_reverb_search', 1);

        $orderBy = Tools::getValue('order_by_reverb_search','p.reference');
        $orderWay = Tools::getValue('order_by_reverb_search','ASC');
        $nbPerPage = Tools::getValue('nb_per_page_reverb_search',100);

        $reverbSync = new \ReverbSync($this->module);
        $result = $reverbSync->getAllProductsPagination($search, $orderBy, $orderWay, $page, $nbPerPage);
        die(json_encode($result));
    }

    public function ajaxProcessMassEdit()
    {
        if (!$this->module instanceof Reverb) {
            die(json_encode(array('status' => 'error', 'message' => 'An error occured')));
        }

        $this->module->logs->infoLogs('# Mass edit products');

        $productIds = Tools::getValue('productIds');
        if (empty($productIds)) {
            $this->module->logs->errorLogs('## No products selected');
            die(json_encode(array('status' => 'error', 'message' => 'Please select at least one product')));
        }

        $bulkAction = Tools::getValue('bulkAction');
        if (empty($bulkAction)) {
            $this->module->logs->errorLogs('## No actions selected');
            die(json_encode(array('status' => 'error', 'message' => 'An error occured')));
        }

        if ($bulkAction == 'mass-edit-all') {
            if ($productIds !== 'all') {
                $this->module->logs->errorLogs('## Mass edit ALL action asked but $productIds is not \'all\'');
                die(json_encode(array('status' => 'error', 'message' => 'An error occured')));
            }
            // Get filtered products
            $search = array();
            if (Tools::getValue('tags_reverb_search')) {
                $search = explode(',', Tools::getValue('tags_reverb_search'));
            }
            $productIds = $this->_getAllProductsIds($search);
        }

        switch ($bulkAction) {
            case 'mass-edit-all':
            case 'mass-edit':
                $this->_massEditProducts($productIds);
                break;
            case 'mass-synchronize':
                $this->_massSynchronize($productIds);
                break;
            case 'mass-offer':
                $this->_massOffer($productIds);
                break;
            case 'mass-local-pickup':
                $this->_massLocalPickup($productIds);
                break;
            default:
                $this->module->logs->errorLogs('## Action ' . $bulkAction . ' unknown');
                die(json_encode(array('status' => 'error', 'message' => 'TODO bulk action ' . $bulkAction)));
        }

        $reverbSync = new \ReverbSync($this->module);
        $products = $reverbSync->getProductsByIds($productIds);

        die(json_encode(array(
            'status' => 'success',
            'message' => count($productIds) . ' product(s) updated',
            'products' => $products
        )));
    }

    private function _massLocalPickup($productIds)
    {
        $this->_treatmentMassEditBoolean ('shipping_local', $productIds);
    }

    private function _massOffer($productIds)
    {
        $this->_treatmentMassEditBoolean ('offers_enabled', $productIds);
    }

    private function _massSynchronize($productIds)
    {
        $this->_treatmentMassEditBoolean('reverb_enabled', $productIds);
    }

    /**
     * @param $search array
     * @return array
     */
    private function _getAllProductsIds($search)
    {
        // Get all products by filters
        $reverbSync = new \ReverbSync($this->module);
        $products = $reverbSync->getAllProductsIds($search);
        $productIds = array();
        foreach ($products as $product) {
            $productIds[] = $product['id_product'];
        }
        return $productIds;
    }

    private function _massEditProducts($productIds)
    {
        $fields = array(
            "reverb_enabled" => "reverb_enabled",
            "reverb_condition" => "id_condition",
            "reverb_model" => "model",
            "reverb_finish" => "finish",
            "reverb_year" => "year",
            "reverb_offers_enabled" => "offers_enabled",
            "reverb_country" => "origin_country_code",
            "reverb_tax_exempt" => "tax_exempt",
            "reverb_shipping_profile" => "id_shipping_profile",
            "reverb_shipping_local" => "shipping_local"
        );
        $attributes = array();
        foreach ($fields as $formField => $dbField) {
            if (Tools::getValue($formField) !== false) {
                $attributes[$dbField] = Tools::getValue($formField);
            }
        }

        if (empty($attributes)) {
            $this->module->logs->errorLogs('## No attributes sent');
            die(json_encode(array('status' => 'error', 'message' => 'An error occured')));
        }

        if (Tools::getValue('reverb_shipping') == 'reverb') {
            $attributes['id_shipping_profile'] = pSQL(Tools::getValue('reverb_shipping_profile'));
            $attributes['shipping_local'] = 0;
        } else {
            $attributes['id_shipping_profile'] = '';
            $attributes['shipping_local'] = Tools::getValue('reverb_shipping_local');
        }

        $db = Db::getInstance();

        foreach ($productIds as $productId) {
            // Get reverb attributes
            $attribute = $this->module->getAttribute($productId, TRUE);

            if (!empty($attribute)) {
                $idAttribute = (int)$attribute['id_attribute'];
                $this->module->logs->infoLogs('## Update product #' . $productId);
                $db->update(
                    'reverb_attributes',
                    $attributes,
                    'id_attribute = ' . $idAttribute
                );
                // Remove all shipping methods
                $db->delete('reverb_shipping_methods', 'id_attribute = ' . (int)$attribute['id_attribute'], false);
                $this->module->logs->infoLogs('### fin update');
            } else {
                $this->module->logs->infoLogs('## Insert product #' . $productId);
                $db->insert('reverb_attributes', array_merge(
                    $attributes,
                    array(
                        'id_lang' => pSql($this->module->language_id),
                        'id_product' => pSql($productId),
                    )
                ));
                $idAttribute = (int)$db->Insert_ID();
            }

            // Save new shipping methods
            if (Tools::getValue('reverb_shipping') == 'custom') {
                $reverb_shipping_methods_region = Tools::getValue('reverb_shipping_methods_region');
                $reverb_shipping_methods_rate = Tools::getValue('reverb_shipping_methods_rate');
                $this->module->logs->infoLogs('shipping_regions = ' . var_export($reverb_shipping_methods_region, true));
                $this->module->logs->infoLogs('shipping_rates = ' . var_export($reverb_shipping_methods_rate, true));
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
            $this->module->flagSyncProductForReverbToSync($productId, ReverbSync::ORIGIN_PRODUCT_UPDATE);
        }
    }

    private function _treatmentMassEditBoolean ($type, $productIds) {
        $this->module->logs->infoLogs('## Mass ' . $type . ' START #');
        $db = Db::getInstance();
        foreach ($productIds as $productId) {
            $this->module->logs->infoLogs('## Mass ' . $type . ' product: #' . $productId);
            // Get reverb attributes
            $attribute = $this->module->getAttribute($productId, TRUE);
            if (!empty($attribute)) {
                $this->module->logs->infoLogs('## Update product #' . $productId);

                $synchro = array(
                    $type=> ($attribute[$type]==1 ? 0:1),
                );

                $db->update(
                    'reverb_attributes',
                    $synchro,
                    'id_attribute = ' . (int)$attribute['id_attribute']
                );
                $this->module->logs->infoLogs('### fin update');
            } else {
                $this->module->logs->infoLogs('## Insert product #' . $productId);
                $db->insert('reverb_attributes', array_merge(
                    array(
                        $type => 1,
                    ),
                    array(
                        'id_lang' => pSql($this->module->language_id),
                        'id_product' => pSql($productId),
                    )
                ));
            }
            // Update sync status
            $this->module->flagSyncProductForReverbToSync($productId, ReverbSync::ORIGIN_PRODUCT_UPDATE);
        }
        $this->module->logs->infoLogs('## Mass Offers END #');
    }

    /**
     *  Ajax Listing sync
     *
     */
    public function ajaxProcessProductCron()
    {
        if (!$this->module instanceof Reverb) {
            die(json_encode(array('status' => 'error', 'message' => 'An error occured')));
        }

        $this->module->logs->cronLogs('start ajax cron manually products');
        $return = $this->curlCron('products');
        $this->module->logs->cronLogs($return);
        die($return);
    }
    /**
     *  Ajax Order sync
     *
     */
    public function ajaxProcessOrderCron()
    {
        if (!$this->module instanceof Reverb) {
            die(json_encode(array('status' => 'error', 'message' => 'An error occured')));
        }

        $this->module->logs->cronLogs('start ajax cron manually orders');
        $return = $this->curlCron('orders');
        $this->module->logs->cronLogs($return);
        die($return);
    }

    /**
     * Curl action to launch cron with a param
     *
     * @param $param
     */
    private function curlCron($param)
    {
        try {
            $helper = new \HelperCron($this->module);
            $code_cron = $param;

            if (!isset($code_cron) || !in_array($code_cron, array('orders',  'products'))) {
                throw new \Exception('No code cron corresponding. ' . $code_cron);
            }

            $this->module->logs->cronLogs('##########################');
            $this->module->logs->cronLogs('# BEGIN ' . $code_cron . ' sync CRON');
            $this->module->logs->cronLogs('##########################');
            $idCron = $helper->insertOrUpdateCronStatus(null, $code_cron, $helper::CODE_CRON_STATUS_PROGRESS);

            if ($this->module->isApiTokenAvailable()) {
                $context = new \ContextCron($this->module);
                switch ($code_cron) {
                    case 'orders':
                        $engine = new \OrdersSyncEngine($this->module, $helper, $context);
                        $engine->processSyncOrder($idCron);
                        break;
                    case 'products':
                        $reverbProduct = new \Reverb\ReverbProduct($this->module);
                        $products = $this->module->reverbSync->getProductsToSync();
                        $this->module->logs->cronLogs('# ' . count($products) . ' product(s) to sync');
                        foreach ($products as $product) {
                            $res = $reverbProduct->syncProduct($product, ReverbSync::ORIGIN_CRON);
                            $this->module->logs->cronLogs('# ' . json_encode($res));
                        }
                        break;
                }
            } else {
                throw new \Exception('No valid API token is defined for the shop');
            }

            $this->module->logs->cronLogs('##########################');
            $this->module->logs->cronLogs('# END ' . $code_cron . ' sync CRON SUCCESS');
            $this->module->logs->cronLogs('##########################');
        } catch (\Exception $e) {
            $error = 'Error in cron ' . (isset($code_cron) ? $code_cron . ' ' : ' ') . $e->getMessage();
            $this->module->logs->cronLogs($error);
            $this->module->logs->errorLogs($error);

            if (isset($code_cron) && isset($idCron) && $idCron) {
                $helper->insertOrUpdateCronStatus($idCron, $code_cron, $helper::CODE_CRON_STATUS_ERROR, $e->getMessage());
            }

            $this->module->logs->cronLogs('##########################');
            $this->module->logs->cronLogs('# END sync CRON FAILED');
            $this->module->logs->cronLogs('##########################');
        }
    }

    public function ajaxProcessChildCategories()
    {
        if (!$this->module instanceof Reverb) {
            die(json_encode(array('status' => 'error', 'message' => 'An error occured')));
        }
        // load category parent
        $category_parent = '';
        $category_parent_name = '';
        if (Tools::getValue('category_parent')) {
            $category = new Category((int)Tools::getValue('category_parent'));
            $category_parent = $category->id;
            $category_parent_name = $category->getName();
        }

        // load all children categories
        $ps_categories = array();
        $cat = new Category($category_parent);
        // get all category children
        $all_cat = $cat->getAllChildren();
        foreach ($all_cat as $subcat) {
            if (isset($subcat->id) && $subcat->id > 0) {
                $ps_categories[] = array(
                    "id_mapping" => ReverbMapping::getMappingId($subcat->id),
                    "ps_category_id" => $subcat->id,
                    "reverb_code" => ReverbMapping::getReverbCode($subcat->id),
                    "name" => '<i>level '. $subcat->level_depth . '</i> - ' . $subcat->name,
                );
            }
        }

        //load reverb categories list
        $reverb_categories = array();
        $reverbCategories = new \Reverb\ReverbCategories($this->module);
        foreach($reverbCategories->getFormattedCategories() as $code=>$name) {
            $reverb_categories[] = array('code'=>$code,'name'=>$name);
        }

        $result = array(
            'status' => 'ok',
            'category_parent' => array(
                'id' =>  $category_parent,
                'name'=> $category_parent_name
            ),
            'reverb_categories' => $reverb_categories,
            'data'=> $ps_categories,
        );


        die(json_encode($result));
    }

    protected function sendErrorRequest($response)
    {
        http_response_code(406);

        $output = Tools::jsonEncode($response);

        die($output);
    }

    /**
     *  Log infos
     *
     * @param $message
     */
    private function logInfoCrons($message)
    {
        $this->module->logs->cronLogs($message);
    }
}
