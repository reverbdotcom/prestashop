<?php
namespace Reverb;

/**
 * Client ShippingRegions
 *
 * @package Reverb
 * @author Johan Protin
 * @copyright Copyright (c) 2017 - Johan Protin
 * @license
 */
class ReverbShippingRegions extends ReverbClient
{

    CONST REVERB_SHIPPING_REGIONS_ENDPOINT = 'shipping/regions';
    CONST REVERB_ROOT_KEY = 'shipping_regions';
    CONST REVERB_DISPLAYNAME= 'display_name';

    public function __construct($module)
    {
        parent::__construct($module);
        $this->setEndPoint(self::REVERB_SHIPPING_REGIONS_ENDPOINT)
            ->setRootKey(self::REVERB_ROOT_KEY);
    }

    /**
     * Return formatted shipping regions
     */
    public function getFormattedShippingRegions()
    {
        $return = array();
        $regions = $this->getListFromEndpoint();
        $this->formatRegions($return, $regions);

        return $return;
    }

    public function formatRegions(&$return, $regions)
    {
        foreach ($regions as $region) {
            $return[$region['code']] = $region['name'];
            if (!empty($region['children'])) {
                $this->formatRegions($return, $region['children']);
            }
        }
    }
}