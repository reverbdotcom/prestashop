<?php

/**
 * Payment for order
 *
 * @author Johan Protin
 * @copyright Copyright (c) 2017 - Johan Protin
 * @license Apache License Version 2.0, January 2004
 * @package Reverb
 */
class ReverbPayment extends PaymentModule
{
    public $active = 1;
    public $name = 'reverb';

    public function __construct()
    {
        $this->displayName = $this->l('Reverb order', array(), 'Admin.OrdersCustomers.Feature');
    }
}
