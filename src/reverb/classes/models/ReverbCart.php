<?php

/**
 *  Reverb Cart
 *
 * @author Johan Protin
 * @copyright Copyright (c) 2017 - Johan Protin
 * @license Apache License Version 2.0, January 2004
 * @package Reverb
 */

class ReverbCart extends CartCore {

    /**
     *  Override PS17
     *
     * Get the delivery option selected, or if no delivery option was selected,
     * the cheapest option for each address
     *
     * @param Country|null $default_country       Default country
     * @param bool         $dontAutoSelectOptions Do not auto select delivery option
     * @param bool         $use_cache             Use cache
     *
     * @return array|bool|mixed Delivery option
     */
    public function getDeliveryOption($default_country = null, $dontAutoSelectOptions = false, $use_cache = true)
    {
        return false;
    }
}