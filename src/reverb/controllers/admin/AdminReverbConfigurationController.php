<?php
/**
 * Module Reverb
 *
 * @author Johan Protin
 * @copyright Copyright (c) 2017 - Johan Protin
 * @license Apache License Version 2.0, January 2004
 * @package Reverb
 */

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
            die(json_encode(array('status' => 'error', 'An error occured')));
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
                    if ($product['reverb_enabled']) {
                        $res = $reverbProduct->syncProduct($product, ReverbSync::ORIGIN_MANUAL_SYNC_SINGLE);
                        die(json_encode($res));
                    } else {
                        die(json_encode(array(
                            'status' => 'error',
                            'message' => 'Product ' . $id_product . ' not enabled for reverb sync'
                        )));
                    }
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

    protected function sendErrorRequest($response)
    {
        http_response_code(406);

        $output = Tools::jsonEncode($response);

        die($output);
    }
}
