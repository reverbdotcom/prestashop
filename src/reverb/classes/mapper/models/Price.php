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
class Price extends AbstractModel {

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