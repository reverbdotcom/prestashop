<?php
/**
 *
 *
 *
 * @author Johan Protin
 * @copyright Copyright (c) 2017 - Johan Protin
 * @license Apache License Version 2.0, January 2004
 * @package Reverb
 */

namespace Reverb;

class ReverbShop extends ReverbClient
{
    const REVERB_SHOP_ENDPOINT = 'shop';
    const REVERB_SHOP_SHIPPING_PROFILES = 'shops/{shop_id}/shipping_profiles';
    const REVERB_SHOP_SHIPPING_PROFILES_ROOT_KEY = 'shipping_profiles';

    public function __construct($module)
    {
        parent::__construct($module);
        $this->setEndPoint(self::REVERB_SHOP_ENDPOINT);
    }


    /**
     * @return null
     */
    public function getShopId()
    {
        $shop = $this->sendGet();
        return isset($shop['id']) ? $shop['id'] : null;
    }

    /**
     * Return formatted conditions for mapping
     */
    public function getShoppingProfiles()
    {
        $shopId = $this->getShopId();
        if (!empty($shopId)) {
            $this->setEndPoint(str_replace('{shop_id}', $shopId, self::REVERB_SHOP_SHIPPING_PROFILES))
                ->setRootKey(self::REVERB_SHOP_SHIPPING_PROFILES_ROOT_KEY);
        }
        return $this->getListFromEndpoint();
    }
}
