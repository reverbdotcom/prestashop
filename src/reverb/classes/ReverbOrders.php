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

    CONST REVERB_MY_SELLING = 'my/orders/selling/all';
    CONST REVERB_MY_SELLING_SHIP = 'my/orders/selling/[ID]/ship';
    CONST REVERB_MY_SELLING_PICKED_UP = 'my/orders/selling/[ID]/mark_picked_up';
    CONST REVERB_ROOT_KEY = 'orders';

    public static $statusToSync = array('paid', 'shipped', 'picked_up', 'received');
    /**
     * ReverbOrders constructor.
     */
    public function __construct($module)
    {
        parent::__construct($module);
        $this->setEndPoint(self::REVERB_MY_SELLING)
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
                'updated_start_date' => $dateISO8601->format('Y-m-d\TH:i:s')
            );
        }

        return $this->getListFromEndpoint(null,$params);
    }

    public function setOrderShip($reverbOrderId, $provider, $trackingNumber)
    {
        $endPoint = str_replace('[ID]', $reverbOrderId, self::REVERB_MY_SELLING_SHIP);

        $this->setEndPoint($endPoint);
        $this->sendPost(json_encode(array(
            'provider' => $provider,
            'tracking_number' => $trackingNumber,
            'send_notification' => 1,
        )));
    }

    public function setOrderPickedUp($reverbOrderId)
    {
        $endPoint = str_replace('[ID]', $reverbOrderId, self::REVERB_MY_SELLING_PICKED_UP);

        $this->setEndPoint($endPoint);
        $this->sendPost(json_encode(array(
            'date' => (new \DateTime())->format('Y-m-d H:i:s')
        )));
    }

}