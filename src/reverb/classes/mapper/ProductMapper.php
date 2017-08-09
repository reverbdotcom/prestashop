<?php
/**
 *  Map product reverb and prestashop
 *
 * @author Johan Protin
 * @copyright Copyright (c) 2017 - Johan Protin
 * @license Apache License Version 2.0, January 2004
 * @package Reverb
 */

class ProductMapper
{
    const MAX_CHAR = 255;

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
     *  Validate Product
     *
     * @param $product_ps
     * @throws Exception
     */
    private function validateProductForSync($product_ps)
    {
        if (empty($product_ps['manufacturer_name'])) {
            throw new Exception('Manufacturer is empty.', 1);
        }

        if (empty($product_ps['description'])) {
            throw new Exception('Description is empty.', 1);
        }

        if (Tools::strlen($product_ps['manufacturer_name']) > self::MAX_CHAR) {
            throw new Exception('Manufacturer is too long (' . self::MAX_CHAR . ' characters max).', 1);
        }

        if (Tools::strlen($product_ps['model']) > self::MAX_CHAR) {
            throw new Exception('Model is too long (' . self::MAX_CHAR . ' characters max).', 1);
        }

        if (Tools::strlen($product_ps['reference']) > self::MAX_CHAR) {
            throw new Exception('Reference is too long (' . self::MAX_CHAR . ' characters max).', 1);
        }

        if (Tools::strlen($product_ps['name']) > self::MAX_CHAR) {
            throw new Exception('Name is too long (' . self::MAX_CHAR . ' characters max).', 1);
        }

        if (Tools::strlen($product_ps['finish']) > self::MAX_CHAR) {
            throw new Exception('Finish is too long (' . self::MAX_CHAR . ' characters max).', 1);
        }

        if ($product_ps['year'] != ''
            && (
                (int)$product_ps['year'] != $product_ps['year']
                || Tools::strlen($product_ps['year']) != 4
            )
        ) {
            throw new Exception('Year is incorrect : ' . $product_ps['year'], 1);
        }
    }

    /**
     *  Map array prestashop To Reverb's model
     *
     * @param array $product_ps
     * @param bool $productExists
     * @throws Exception
     */
    public function processMapping($product_ps, $productExists)
    {
        $product = new \ProductReverb();

        $this->validateProductForSync($product_ps);

        $product->make = $product_ps['manufacturer_name'];
        $product->model = $product_ps['model'];
        $product->has_inventory = $product_ps['quantity_stock'] > 0 ? 1 : false;
        $product->inventory = $product_ps['quantity_stock'];
        $product->sku = $product_ps['reference'];
        $product->upc = !empty($product_ps['upc']) ? $product_ps['upc'] : $product_ps['ean13'] ;
        $product->publish = false;
        $product->title = $product_ps['name'];
        $product->categories = $this->mapCategories($product_ps);
        $product->location = $this->mapLocation();
        $product->offers_enabled = $product_ps['offers_enabled'] ? 1 : false;
        $product->finish = $product_ps['finish'];
        $product->origin_country_code = $product_ps['origin_country_code'];
        $product->year = $product_ps['year'];
        $product->seller_cost = $product_ps['wholesale_price'];
        $product->tax_exempt = null;
        $product = $this->mapShipping($product, $product_ps);
        $product = $this->processMappingAccordingSettings($product, $product_ps, $productExists);

        $this->request = $product;
    }

    /**
     * @param ProductReverb $product
     * @param array $product_ps
     * @param string|false $productExists
     * @return ProductReverb
     */
    private function processMappingAccordingSettings(ProductReverb $product, $product_ps, $productExists)
    {
        if ($this->module->getReverbConfig(\Reverb::KEY_SETTINGS_DESCRIPTION)) {
            $product->description = $product_ps['description'];
        }

        if (!$productExists || ($productExists && $this->module->getReverbConfig(\Reverb::KEY_SETTINGS_PRICE))) {
            $product->price = $this->mapPrice($product_ps);
        }

        if (!$productExists || ($productExists && $this->module->getReverbConfig(\Reverb::KEY_SETTINGS_PHOTOS))) {
            $product->photos = $this->getImagesUrl($product_ps);
        }

        if (!$productExists || ($productExists && $this->module->getReverbConfig(\Reverb::KEY_SETTINGS_CONDITION))) {
            $product->condition = $this->mapCondition($product_ps);
        }

        if ($this->module->getReverbConfig(\Reverb::KEY_SETTINGS_AUTO_PUBLISH)) {
            $product->publish = 1;
        }

        return $product;
    }

    /**
     * Map condition for a product
     *
     * @param array $product
     * @return null|\Reverb\Mapper\Models\Condition
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
     * @return Reverb\Mapper\Models\Location
     */
    protected function mapLocation()
    {
        // Get defaults information on current store
        $country = Tools::strtolower(Country::getIsoById(Configuration::get('PS_SHOP_COUNTRY_ID')));
        $region = Tools::strtolower(State::getNameById(Configuration::get('PS_SHOP_STATE_ID')));
        $locality = Configuration::get('PS_SHOP_CITY');

        if (!$locality) {
            $locality = "";
        }
        if (!$region) {
            $region = "";
        }
        if (!$country) {
            $country = "";
        }

        // Instantiate Model Location with extracts informations
        return new Reverb\Mapper\Models\Location($country, $region, $locality);
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

        $id_category = (int)$product_ps['id_category_default'];
        $uuid = ReverbMapping::getReverbCode($id_category);

        if ($uuid) {
            $list = array();
            $category = new Reverb\Mapper\Models\Category($uuid);
            $list[] = $category;
        } else {
            $psCategory = new Category($id_category);
            throw new Exception('Category "' . $psCategory->getName((int)$product_ps['id_lang']) . '" is not mapped with a Reverb category', 1);
        }
        return $list;
    }

    /**
     *  Map price for a product
     *
     * @param array $product_ps
     * @return Reverb\Mapper\Models\Price
     */
    protected function mapPrice($product_ps)
    {
        if ($this->context->currency) {
            $isoCode = $this->context->currency->iso_code;
        } else {
            $currency = Currency::getDefaultCurrency();
            $isoCode = $currency->iso_code;
        }
        return new Reverb\Mapper\Models\Price($product_ps['price'], $isoCode);
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
        if (strstr(getenv('PS_DOMAIN'), 'localhost')) {
            $urls[] = 'https://www.easyzic.com/common/datas/dossiers/6/6/acoustique-yamaha-c40-1.jpg';
        } else {
            $images = Image::getImages((int)$product['id_lang'], (int)$product['id_product']);
            foreach ($images as $image) {
                $urls[] = $this->context->link->getImageLink($product['link_rewrite'], $image['id_image'], $this->getImageTypeFormattedName('large'));
            }
        }
        return $urls;
    }

    /**
     * Get image type formatted name according to the Prestashop version
     * @param $name
     * @return string
     */
    private function getImageTypeFormattedName($name)
    {
        if (version_compare(_PS_VERSION_, '1.7', '<')) {
            return ImageType::getFormatedName($name);
        }
        return ImageType::getFormattedName($name);
    }

    /**
     * @param ProductReverb $product
     * @param $product_ps
     * @return ProductReverb
     */
    private function mapShipping(ProductReverb $product, $product_ps)
    {
        if ($product_ps['id_shipping_profile']) {
            $product->shipping_profile_id = $product_ps['id_shipping_profile'];
        } else {
            $reverbAttribute = new ReverbAttributes($this->module);
            $shippingMethods = $reverbAttribute->getShippingMethods($product_ps['id_attribute']);

            $rates = array();
            foreach ($shippingMethods as $shippingMethod) {
                $rates[] = array(
                    'rate' => array(
                        'amount' => $shippingMethod['rate'],
                        'currency' => $this->module->getContext()->currency->iso_code,
                    ),
                    'region_code' => $shippingMethod['region_code']
                );
            }
            $product->shipping = array(
                'rates' => $rates,
                'local' => $product_ps['shipping_local'] ? 1 : false,
            );
        }

        return $product;
    }
}

require_once(dirname(__FILE__) . '/models/ProductReverb.php');
require_once(dirname(__FILE__) . '/models/Location.php');
