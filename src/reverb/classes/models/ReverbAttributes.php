<?php

/**
 * Model Reverb Sync
 *
 * @package Reverb
 * @author Johan Protin
 * @copyright Copyright (c) 2017 - Johan Protin
 * @license
 */
class ReverbAttributes
{
    const ORIGIN_MANUAL_SYNC_SINGLE = 'manual_sync_single';
    const ORIGIN_MANUAL_SYNC_MULTIPLE = 'manual_sync_multiple';
    const ORIGIN_PRODUCT_UPDATE = 'product_update';
    const ORIGIN_ORDER = 'order';
    const ORIGIN_CRON = 'cron';

    protected $module;

    /**
     * ReverbSync constructor.
     * @param Reverb $module_instance
     */
    public function __construct(\Reverb $module_instance)
    {
        $this->module = $module_instance;
    }

    /**
     * Get product Reverb attributes
     * @param integer $productId
     * @return array
     */
    public function getAttributes($productId)
    {
        $sql = new DbQuery();
        $sql->select('*')
            ->from('reverb_attributes', 'ra')
            ->where('ra.id_product = ' . $productId)
            ->where('ra.id_lang = ' . $this->module->language_id)
        ;

        $result = Db::getInstance()->getRow($sql);

        return $result;
    }

    /**
     * Get product Reverb shipping methods
     * @param integer $attributeId
     * @return array
     */
    public function getShippingMethods($attributeId)
    {
        $sql = new DbQuery();
        $sql->select('*')
            ->from('reverb_shipping_methods', 'rsm')
            ->where('rsm.id_attribute = ' . $attributeId)
        ;

        $result = Db::getInstance()->executeS($sql);

        return $result;
    }
}
