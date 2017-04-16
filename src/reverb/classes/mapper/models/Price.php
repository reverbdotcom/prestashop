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

class Price extends AbstractModel
{

    /**
     * @var
     */
    public $amount;

    /**
     * @var
     */
    public $currency;

    /**
     * Price constructor.
     *
     * @param $amount
     * @param $currency
     */
    public function __construct($amount, $currency)
    {
        $this->amount = $amount;
        $this->currency = $currency;
    }
}
