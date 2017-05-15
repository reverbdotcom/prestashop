<?php
/**
 *  Map product reverb and prestashop
 *
 * @author Johan Protin
 * @copyright Copyright (c) 2017 - Johan Protin
 * @license Apache License Version 2.0, January 2004
 * @package Reverb
 */

namespace Reverb\Mapper\Models;

class Location extends AbstractModel
{
    /**
     * @var string
     */
    public $locality;

    /**
     * @var string
     */
    public $region;

    /**
     * @var null
     */
    public $country_code;

    /**
     * Category constructor.
     *
     * @param string $uuid
     */
    public function __construct($country = null, $region = null, $locality = null)
    {
        $this->country_code = $country;
        $this->region = $region;
        $this->locality = $locality;
    }
}
