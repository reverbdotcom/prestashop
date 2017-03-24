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
class Seller extends AbstractModel
{

    public $paypal_email;

    /**
     * Category constructor.
     *
     * @param string uuid
     */
    public function __construct($paypal_email)
    {
        $this->paypal_email = $paypal_email;
    }
}
