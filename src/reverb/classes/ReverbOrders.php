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
     * Get all orders or one by uuid
     *
     * @param null $uuid
     * @return array
     */
    public function getOrders($date = null)
    {
        $params = null;
        $reverbUtils = new \Reverb\ReverbUtils($this->module);

        if ($date){
            $dateISO8601 = new DateTime($date);
            $params = array(
                'created_start_date' => $dateISO8601->format('Y-m-d\TH:i:s')
            );
        }

        return $reverbUtils->getListFromEndpoint(self::REVERB_CATEGORIES_ENDPOINT,self::REVERB_ROOT_KEY,null,$params);
    }

}