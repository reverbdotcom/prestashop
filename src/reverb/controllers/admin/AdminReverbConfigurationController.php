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
            $helper = new HelperCron($this->module);
            $orderSyncEngine = new OrdersSyncEngine($this->module, $helper);

            // Call and getting an order from reverb
            $reverbOrders = new \Reverb\ReverbOrders($this->module);
            $reverbOrder = $reverbOrders->getOrder($reverbId);

            $order = $this->module->reverbOrders->getOrders(array('reverb_order_number' => $reverbId), true);

            //var_dump($reverbOrder); exit;

            if (!empty($reverbOrder)) {

                $lastSynced = (new \DateTime())->format('Y-m-d H:i:s');

                try {
                    $context = new \ContextCron($this->module);
                    $idOrder = $orderSyncEngine->createPrestashopOrder($reverbOrder, $context, 0);

                    $this->module->reverbOrders->update($order['id_reverb_orders'],
                        array(
                            'id_order' => $idOrder,
                            'id_shop' => $context->getIdShop(),
                            'id_shop_group' => $context->getIdShopGroup(),
                            'status' => 'success',
                            'details' => 'Reverb order synced',
                            'date' => $lastSynced,
                            'shipping_method' => $reverbOrder['shipping_method'],
                        )
                    );
                    die(json_encode(array(
                        'status' => 'success',
                        'message' => 'Reverb order synced',
                        'last-synced' => $lastSynced,
                        'reverb-id' => $reverbId,
                    )));
                } catch (Exception $e) {
                    $this->module->reverbOrders->update($order['id_reverb_orders'],
                        array(
                            'id_shop' => $context->getIdShop(),
                            'id_shop_group' => $context->getIdShopGroup(),
                            'status' => 'error',
                            'details' => $e->getMessage(),
                            'date' => $lastSynced,
                        )
                    );
                    die(json_encode(array(
                        'status' => 'error',
                        'message' => $e->getMessage(),
                        'last-synced' => $lastSynced,
                        'reverb-id' => $reverbId,
                    )));
                }
            } else {
                die(json_encode(array(
                    'status' => 'error',
                    'message' => 'No order found for ID ' . $reverbOrder
                )));
            }
        }
        die(json_encode(array('status' => 'error', 'message' => 'An error occured')));
    }

    protected function sendErrorRequest($response)
    {
        http_response_code(406);

        $output = Tools::jsonEncode($response);

        die($output);
    }
}
