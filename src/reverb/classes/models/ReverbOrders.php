<?php
/**
 *  Mapping Order
 *
 * @author Johan Protin
 * @copyright Copyright (c) 2017 - Johan Protin
 * @license Apache License Version 2.0, January 2004
 * @package Reverb
 */

class ReverbOrders
{
    const REVERB_ORDERS_STATUS_ORDER_SAVED = 'saved';
    const REVERB_ORDERS_STATUS_PAID = 'paid';
    const REVERB_ORDERS_STATUS_SHIPPING_SENT = 'shipping-sent';
    const REVERB_ORDERS_STATUS_IGNORED = 'ignored';
    const REVERB_ORDERS_STATUS_ERROR = 'error';
    const REVERB_ORDERS_SHIPPING_METHOD_SHIPPED = 'shipped';
    const REVERB_ORDERS_SHIPPING_METHOD_LOCAL = 'local';

    protected $module;

    /**
     * ReverbSync constructor.
     * @param Reverb $module_instance
     */
    public function __construct(\Reverb $module_instance)
    {
        $this->module = $module_instance;
    }

    /**
     * Find an Reverb order by reference
     * @param $id_order
     * @return array|false|mysqli_result|null|PDOStatement|resource
     */
    public function getOrderByPsId($id_order)
    {
        return $this->getOrders(array('id_order' => $id_order), true);
    }

    /**
     * Find Reverb orders by criteria
     * @param array $criteria
     * @param boolean $findOne
     * @return array|false|mysqli_result|null|PDOStatement|resource
     */
    public function getOrders($criteria = array(), $findOne = false)
    {
        //=========================================
        //          SELECT CLAUSE
        //=========================================
        $sql = new DbQuery();
        $sql->select('*')
            ->from('reverb_orders', 'ro');

        foreach ($criteria as $field => $value) {
            $sql->where("ro.`$field` = \"$value\"");
        }

        $sql->orderBy('ro.date DESC');

        if ($findOne) {
            return Db::getInstance()->getRow($sql);
        }
        return Db::getInstance()->executeS($sql);
    }

    /**
     * Find Reverb orders by criteria
     * @param array $criteria
     * @param boolean $findOne
     * @return array|false|mysqli_result|null|PDOStatement|resource
     */
    public function getOrdersTotals($criteria = array(), $findOne = false)
    {
        //=========================================
        //          SELECT CLAUSE
        //=========================================
        $sql = new DbQuery();
        $sql->select('count(*) as totals')
            ->from('reverb_orders', 'ro');

        foreach ($criteria as $field => $value) {
            $sql->where("ro.`$field` = \"$value\"");
        }

        $result = Db::getInstance()->getRow($sql);

        return $result['totals'];
    }

    /**
     * @param $idShop
     * @param $idShopGroup
     * @param $idOrder
     * @param $orderNumber
     * @param $sku
     * @param $status
     * @param $details
     * @param $shippingMethod
     * @param null $shippingTracker
     */
    public function insert(
        $idShop,
        $idShopGroup,
        $idOrder,
        $orderNumber,
        $sku,
        $status,
        $details,
        $shippingMethod,
        $shippingTracker = null
    ) {
        $this->module->logs->infoLogs('insertOrder');
        $this->module->logs->infoLogs(' - $idOrder = ' . $idOrder);
        $this->module->logs->infoLogs(' - $orderNumber = ' . $orderNumber);
        $this->module->logs->infoLogs(' - $sku = ' . $sku);
        $this->module->logs->infoLogs(' - $status = ' . $status);
        $this->module->logs->infoLogs(' - $details = ' . $details);
        $this->module->logs->infoLogs(' - $shippingMethod = ' . $shippingMethod);

        $params = array(
            'date' => (new \DateTime())->format('Y-m-d H:i:s'),
            'status' => pSQL($status),
            'details' => pSQL($details),
            'reverb_order_number' => pSQL($orderNumber),
            'reverb_product_sku' => pSQL($sku),
            'shipping_method' => $shippingMethod,
            'shipping_tracker' => $shippingTracker,
        );

        if ($idOrder) {
            $params['id_order'] = (int)$idOrder;
        }
        if ($idShop) {
            $params['id_shop'] = (int)$idShop;
        }
        if ($idShopGroup) {
            $params['id_shop_group'] = (int)$idShop;
        }

        Db::getInstance()->insert(
            'reverb_orders',
            $params
        );

        $this->module->logs->infoLogs(' reverb_orders inserted !');
    }

    /**
     *  Update table Reverb Orders
     *
     * @param integer $id_reverb_orders
     * @param array $params
     */
    public function update($id_reverb_orders, $params)
    {
        $this->module->logs->infoLogs('Update reverb_orders = ' . $id_reverb_orders . ' with params :' . var_export($params, true));

        Db::getInstance()->update(
            'reverb_orders',
            $params,
            'id_reverb_orders= ' . (int)$id_reverb_orders
        );
    }
}
