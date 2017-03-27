<?php

use Reverb\Mapper\Models\Category;

/**
 * Model Reverb Sync
 *
 * @package Reverb
 * @author Johan Protin
 * @copyright Copyright (c) 2017 - Johan Protin
 * @license
 */
class ProductMapper
{
    protected $request;

    protected $module;

    /**
     * ReverbProduct constructor.
     * @param \Reverb $module_instance
     *
     */
    public function __construct(\Reverb $module_instance)
    {
        $this->context = \Context::getContext();
        $this->module = $module_instance;
    }

    /**
     *  Map array prestashop To Reverb's model
     *
     * @param $product
     */
    public function processMapping($product_ps,$synced)
    {
        $product = new \ProductReverb();

        $product->make = $product_ps['manufacturer_name'];
        $product->model = $product_ps['name'];
        $product->has_inventory = $product_ps['quantity'] > 0 ? true : false;
        $product->inventory = $product_ps['quantity'];
        $product->sku = $product_ps['reference'];
        $product->upc = $product_ps['ean13'];
        $product->publish = false;
        $product->title = $product_ps['name'];;
        $product->categories = $this->mapCategories($product_ps);
        $product->location = $this->mapLocation();
        $product->sold_as_is =  $product_ps['sold_as_is'] ? true : false;
        $product->finish = $product_ps['finish'];
        $product->origin_country_code = $product_ps['origin_country_code'];
        $product->year = $product_ps['year'];;
        $product->seller_cost = $product_ps['wholesale_price'];

        $product->shipping_profile_id = null;
        $product->tax_exempt = null;

        $product = $this->processMappingAccordingSettings($product,$product_ps,$synced);

        $this->request = $product;
    }

    /**
     * @param $product
     * @param $synced
     */
    private function processMappingAccordingSettings($product,$product_ps,$synced) {
        if ($this->module->getReverbConfig(\Reverb::KEY_SETTINGS_DESCRIPTION) ){
            $product->description = $product_ps['description'];
        }

        if (!$synced || ($synced && $this->module->getReverbConfig(\Reverb::KEY_SETTINGS_PRICE)) ) {
            $product->price = $this->mapPrice($product_ps);
        }

        if (!$synced || ($synced && $this->module->getReverbConfig(\Reverb::KEY_SETTINGS_PHOTOS)) ) {
            $product->photos = $this->getImagesUrl($product_ps);
        }

        if (!$synced || ($synced && $this->module->getReverbConfig(\Reverb::KEY_SETTINGS_CONDITION)) ) {
            $product->condition = $this->mapCondition($product_ps);
        }

        if ($this->module->getReverbConfig(\Reverb::KEY_SETTINGS_AUTO_PUBLISH)) {
            $product->publish = true;
        }

        if ($this->module->getReverbConfig(\Reverb::KEY_SETTINGS_PAYPAL)) {
            $seller = new Reverb\Mapper\Models\Seller($this->module->getReverbConfig(\Reverb::KEY_SETTINGS_PAYPAL));
            $product->seller = $seller;
        }

        return $product;
    }

    /**
     *  Map condition for a product
     *
     * @param array $product_ps
     * @return array of Reverb\Mapper\Models\Categor
     */
    protected function mapCondition($product)
    {
        $condition = null;
        if ($product['id_condition']) {
            $condition = new Reverb\Mapper\Models\Condition($product['id_condition']);
        }
        return $condition;
    }

    /**
     *  Map Location with current store information
     *
     * @param array $product_ps
     * @return Reverb\Mapper\Models\Location
     */
    protected function mapLocation()
    {
        // Get defaults information on current store
        $country = Tools::strtolower(Country::getIsoById(Configuration::get('PS_SHOP_COUNTRY_ID')));
        $region = Tools::strtolower(State::getNameById(Configuration::get('PS_SHOP_STATE_ID')));
        $locality = Configuration::get('PS_SHOP_CITY');

        if (!$locality){
            $locality = "";
        }
        if (!$region){
            $region = "";
        }
        if (!$country){
            $country = "";
        }

        // Instantiate Model Location with extracts informations
        $location = new Reverb\Mapper\Models\Location($country, $region, $locality);
        return $location;
    }

    /**
     *  Map categories for a product
     *
     * @param array $product_ps
     * @return array of Reverb\Mapper\Models\Categor
     */
    protected function mapCategories($product_ps)
    {
        $list = null;

        $uuid = ReverbMapping::getReverbCode((int)$product_ps['id_category_default']);

        if ($uuid) {
            $list = array();
            $category = new Reverb\Mapper\Models\Category($uuid);
            $list[] = $category;
        }
        return $list;
    }

    /**
     *  Map price for a product
     *
     * @param array $product_ps
     * @return array of Reverb\Mapper\Models\Price
     */
    protected function mapPrice($product_ps)
    {
        $price = new Reverb\Mapper\Models\Price($product_ps['price'], $this->context->currency->iso_code);
        return $price;
    }

    /*
     *  Map Model to json object
     */
    public function getObjetForRequest()
    {
        $requestSerializer = new \RequestSerializer($this->request);
        return $requestSerializer->toJson();
    }

    /**
     *  Return fulls urls from product images
     *
     * @param $product
     * @return array
     */
    private function getImagesUrl($product)
    {
        $urls = array();
        $images = Image::getImages((int)$product['id_lang'], (int)$product['id_product']);
        foreach ($images as $image) {
            $urls[] = $this->context->link->getImageLink($product['link_rewrite'], $image['id_image'], 'large');
        }
        return $urls;
    }
}

require_once(dirname(__FILE__) . '/models/ProductReverb.php');
require_once(dirname(__FILE__) . '/models/Location.php');
