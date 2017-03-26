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
        $productId = Tools::getValue('id_product');

        if (isset($productId) && !empty($productId)) {
            $reverbProduct = new \Reverb\ReverbProduct($this->module);

            $sql = new DbQuery();
            $sql->select('distinct(p.id_product),
                          p.*,
                          pl.*,
                          m.name as manufacturer_name,
                          ra.*,
                          rs.id_sync, rs.reverb_id, rs.reverb_slug')

                ->from('product', 'p')

                ->leftJoin('product_lang', 'pl', 'pl.`id_product` = p.`id_product`')
                ->leftJoin('manufacturer', 'm', 'm.`id_manufacturer` = p.`id_manufacturer`')
                ->leftJoin('reverb_attributes', 'ra', 'ra.`id_product` = p.`id_product` AND ra.`id_lang` = pl.`id_lang`')
                ->leftJoin('reverb_sync', 'rs', 'rs.`id_product` = p.`id_product`')

                ->where('p.`id_product` = ' . (int) $productId)
                ->where('pl.`id_lang` = '.(int)$this->module->language_id);

            $res = Db::getInstance()->executeS($sql);

            if (!empty($res) && isset($res[0])) {
                $product = $res[0];
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
