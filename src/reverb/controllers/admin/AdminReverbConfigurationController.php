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

        if (isset($productId)) {
            $reverbProduct = new \Reverb\ReverbProduct($this->module);

            $sql = new DbQuery();
            $sql->select('distinct(p.id_product),
                          p.*,
                          pl.*,
                          m.name as manufacturer_name,
                          ra.*');

            $sql->from('product', 'p');
            $sql->leftJoin('product_lang', 'pl', 'pl.`id_product` = p.`id_product`');
            $sql->leftJoin('reverb_attributes', 'ra', 'ra.`id_product` = p.`id_product` AND ra.`id_lang` = pl.`id_lang`');
            $sql->leftJoin('manufacturer', 'm', 'm.`id_manufacturer` = p.`id_manufacturer`');

            $sql->where('p.`id_product` = ' . (int) $productId);
            $sql->where('pl.`id_lang` = '.(int)$this->module->language_id);

            $product = Db::getInstance()->executeS($sql);

            if ($product[0]['reverb_enabled']) {
                $reverbProduct->syncProduct($product[0]);
            }else{
                //TODO Gérer un retour Success/Erreur
            }
        }else{
            //TODO Gérer un retour Success/Erreur
            die('An error is occured');
        }
    }

    protected function sendErrorRequest($response)
    {
        http_response_code(406);

        $output = Tools::jsonEncode($response);

        die($output);
    }
}
