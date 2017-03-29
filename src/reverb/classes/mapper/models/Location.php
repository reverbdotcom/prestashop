<?php

namespace Reverb\Mapper\Models;

/**
 * Model Reverb Sync
 *
 * @package Reverb
 * @author Johan Protin
 * @copyright Copyright (c) 2017 - Johan Protin
 * @license
 */
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
