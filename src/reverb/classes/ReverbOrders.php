<?php
/**
 *
 *
 *
 * @author Johan Protin
 * @copyright Copyright (c) 2017 - Johan Protin
 * @license Apache License Version 2.0, January 2004
 * @package Reverb
 */

namespace Reverb;

use ICanBoogie\DateTime;

class ReverbOrders extends ReverbClient
{
    const REVERB_MY_SELLING = 'my/orders/selling/all';
    const REVERB_MY_SELLING_ORDER = 'my/orders/selling/[ID]';
    const REVERB_MY_SELLING_SHIP = 'my/orders/selling/[ID]/ship';
    const REVERB_MY_SELLING_PICKED_UP = 'my/orders/selling/[ID]/mark_picked_up';
    const REVERB_ROOT_KEY = 'orders';

    public static $statusToSync = array('paid', 'shipped', 'picked_up', 'received', 'partially_paid', 'unpaid');

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
     * @param \DateTime|null $startDate
     * @param \DateTime|null $endDate
     * @return mixed|string
     */
    public function getOrders(\DateTime $startDate = null, \DateTime $endDate = null)
    {
        $params = array();
        if ($startDate) {
            $params['updated_start_date'] = $startDate->format('Y-m-d\TH:i:s');
        }
        if ($endDate) {
            $params['updated_end_date'] = $endDate->format('Y-m-d\TH:i:s');
        }

        return $this->getListFromEndpoint(null, $params);
    }

    /**
     * Get a order by ID
     *
     * @param integer $reverbOrderId
     * @return array
     */
    public function getOrder($reverbOrderId)
    {
        $endPoint = str_replace('[ID]', $reverbOrderId, self::REVERB_MY_SELLING_ORDER);
        $this->setEndPoint($endPoint);
        return $this->sendGet();
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
