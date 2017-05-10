<?php
/**
 *
 *
 * @author Johan Protin
 * @copyright Copyright (c) 2017 - Johan Protin
 * @license Apache License Version 2.0, January 2004
 * @package Reverb
 */

namespace Reverb\Mapper\Models;

class Seller extends AbstractModel
{
    public $paypal_email;

    /**
     * Category constructor.
     *
     * @param string $uuid
     */
    public function __construct($paypal_email)
    {
        $this->paypal_email = $paypal_email;
    }
}
