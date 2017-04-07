<?php

namespace Reverb;

use ICanBoogie\DateTime;

/**
 * Client order
 *
 * @package Reverb
 * @author Johan Protin
 * @copyright Copyright (c) 2017 - Johan Protin
 * @license
 */
class ReverbOrders extends ReverbClient
{

    CONST REVERB_CATEGORIES_ENDPOINT = 'my/orders/selling/all';
    CONST REVERB_ROOT_KEY = 'orders';

    /**
     * ReverbOrders constructor.
     */
    public function __construct($module)
    {
        parent::__construct($module);
        $this->setEndPoint(self::REVERB_CATEGORIES_ENDPOINT)
            ->setRootKey(self::REVERB_ROOT_KEY);
    }


    /**
     * Get all orders
     *
     * @param null $date
     * @return array
     */
    public function getOrders($date = null)
    {
        $params = null;
        if ($date) {
            $dateISO8601 = new DateTime($date);
            $params = array(
                'created_start_date' => $dateISO8601->format('Y-m-d\TH:i:s')
            );
        }

        return $this->getListFromEndpoint(null,$params);
    }

}