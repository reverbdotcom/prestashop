<?php

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
     *
     */
    public function ajaxProcessSyncronizeProduct() {
        if (!$this->module instanceof Reverb) {
            die(array('status' => 'error', 'An error occured'));
        }

        $productId = Tools::getValue('id_product');

        if (isset($productId) && !empty($productId)) {
            $reverbProduct = new \Reverb\ReverbProduct($this->module);

            $product = $this->module->reverbSync->getProductWithStatus($productId);

            if (!empty($product)) {
                if ($product['reverb_enabled']) {
                    $res = $reverbProduct->syncProduct($product);
                    die(json_encode($res));
                } else {
                    die(json_encode(array('status' => 'error', 'message' => 'Product ' . $productId . ' not enabled for reverb sync')));
                }
            } else {
                die(json_encode(array('status' => 'error', 'message' => 'No product found for ID ' . $productId . ' and lang ' . $this->module->language_id)));
            }

        } else{
            die(array('status' => 'error', 'An error occured'));
        }
    }

    protected function sendErrorRequest($response)
    {
        http_response_code(406);

        $output = Tools::jsonEncode($response);

        die($output);
    }
}
