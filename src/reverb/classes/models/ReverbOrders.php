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
     * @param $criteria
     * @return array|false|mysqli_result|null|PDOStatement|resource
     */
    public function getOrdersList($fields_list)
    {
        //=========================================
        //          SELECT CLAUSE
        //=========================================
        $sql = new DbQuery();
        $sql->select('ro.*, ro.reverb_order_number AS reverb_slug')
            ->from('reverb_orders', 'ro');

        if (
            Tools::isSubmit('submitFilterps_reverb_orders')
            && !Tools::isSubmit('submitResetps_reverb_orders')
        ) {
            $this->processFilter($fields_list, $sql);
        }

        //=========================================
        //          ORDER CLAUSE
        //=========================================
        if (Tools::getValue('ps_reverb_ordersOrderby')) {
            $sql->orderBy(Tools::getValue('ps_reverb_ordersOrderby') . ' ' . Tools::getValue('ps_reverb_ordersOrderway'));
        } else {
            $sql->orderBy('ro.date DESC');
        }

        //=========================================
        //          PAGINATION
        //=========================================
        $page = (int)Tools::getValue('submitFilterps_reverb_orders');
        if ($page > 1) {
            $sql->limit(Tools::getValue('ps_reverb_orders_pagination'), ($page-1) * Tools::getValue('ps_reverb_orders_pagination'));
        } else {
            $sql->limit(Tools::getValue('ps_reverb_orders_pagination', 50));
        }

        return Db::getInstance()->executeS($sql);
    }

    /**
     * Generate WHERE Clause with actives filters
     * @param $list_field
     * @param $sql
     * @return string
     */

    protected function processFilter($list_field, DbQuery $sql)
    {
        $values = Tools::getAllValues();

        foreach ($values as $key => $params) {
            if (preg_match('/' . Reverb::LIST_ORDERS_ID . 'Filter_/', $key) && !empty($params)) {
                $field = preg_replace('/' . Reverb::LIST_ORDERS_ID . 'Filter_/', '', $key);
                $filterKey = $field;
                if (isset($list_field[$field])){
                    if (isset($list_field[$field]['filter_key'])) {
                        $filterKey = preg_replace('/!/', '.',$list_field[$field]['filter_key']);
                    }
                    switch ($list_field[$field]['type']) {
                        case 'text':
                            $sql->where($filterKey . ' like "%' . pSQL($params) . '%"');
                            break;
                        case 'int':
                            $sql->where($filterKey . ' = ' . pSQL($params));
                            break;
                        case 'select':
                            $sql->where($filterKey . ' like "%' . pSQL($params) . '%"');
                            break;
                        case 'datetime':
                            if (isset($params[0]) && !empty($params[0])) {
                                if (!Validate::isDate($params[0])) {
                                    $this->errors[] = $this->trans('The \'From\' date format is invalid (YYYY-MM-DD)', array(), 'Admin.Notifications.Error');
                                } else {
                                    $sql->where($filterKey .' >= \''.pSQL(Tools::dateFrom($params[0])).'\'');
                                }
                            }

                            if (isset($params[1]) && !empty($params[1])) {
                                if (!Validate::isDate($params[1])) {
                                    $this->errors[] = $this->trans('The \'To\' date format is invalid (YYYY-MM-DD)', array(), 'Admin.Notifications.Error');
                                } else {
                                    $sql->where($filterKey . ' <= \''.pSQL(Tools::dateFrom($params[0])).'\'');
                                }
                            }
                            break;
                    }
                }
            };
        }
        return $sql;
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
        $sql->select('ro.*, ro.reverb_order_number AS reverb_slug')
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
     * @param array $fields_list
     * @return int
     */
    public function getOrdersTotals($fields_list)
    {
        //=========================================
        //          SELECT CLAUSE
        //=========================================
        $sql = new DbQuery();
        $sql->select('count(*) as totals')
            ->from('reverb_orders', 'ro');

        if (Tools::isSubmit('submitFilterps_reverb_orders')) {
            $this->processFilter($fields_list, $sql);
        }

        if (Tools::getValue('ps_reverb_ordersOrderby')) {
            $sql->orderBy(Tools::getValue('ps_reverb_ordersOrderby') . ' ' . Tools::getValue('ps_reverb_ordersOrderway'));
        } else {
            $sql->orderBy('ro.date DESC');
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
