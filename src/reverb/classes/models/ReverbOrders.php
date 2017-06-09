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
    const REVERB_ORDERS_STATUS_UNPAID = 'unpaid';
    const REVERB_ORDERS_STATUS_PENDING_PAYMENT = 'payment_pending';
    const REVERB_ORDERS_STATUS_PENDING_REVIEW = 'pending_review';
    const REVERB_ORDERS_STATUS_BLOCKED = 'blocked';
    const REVERB_ORDERS_STATUS_PARTIALLY_PAID = 'partially_paid';
    const REVERB_ORDERS_STATUS_PAID = 'paid';
    const REVERB_ORDERS_STATUS_SHIPPED = 'shipped';
    const REVERB_ORDERS_STATUS_PICKED_UP = 'picked_up';
    const REVERB_ORDERS_STATUS_RECEIVED = 'received';
    const REVERB_ORDERS_STATUS_REFUNDED = 'refunded';
    const REVERB_ORDERS_STATUS_CANCELLED = 'cancelled';

    const REVERB_ORDERS_STATUS_IGNORED = 'ignored';
    const REVERB_ORDERS_STATUS_ERROR = 'error';

    const REVERB_ORDERS_SHIPPING_METHOD_SHIPPED = 'shipped';
    const REVERB_ORDERS_SHIPPING_METHOD_LOCAL = 'local';

    protected $module;

    public static function getAllReverbStatuses()
    {
        return array(
            self::REVERB_ORDERS_STATUS_UNPAID,
            self::REVERB_ORDERS_STATUS_PENDING_PAYMENT,
            self::REVERB_ORDERS_STATUS_PENDING_REVIEW,
            self::REVERB_ORDERS_STATUS_BLOCKED,
            self::REVERB_ORDERS_STATUS_PARTIALLY_PAID,
            self::REVERB_ORDERS_STATUS_PAID,
            self::REVERB_ORDERS_STATUS_SHIPPED,
            self::REVERB_ORDERS_STATUS_PICKED_UP,
            self::REVERB_ORDERS_STATUS_RECEIVED,
            self::REVERB_ORDERS_STATUS_REFUNDED,
            self::REVERB_ORDERS_STATUS_CANCELLED,
        );
    }

    public static function getAllInternalStatuses()
    {
        return array(
            self::REVERB_ORDERS_STATUS_ERROR,
            self::REVERB_ORDERS_STATUS_IGNORED,
        );
    }

    public static function getAllStatuses()
    {
        return self::getAllReverbStatuses() + self::getAllInternalStatuses();
    }

    public static function getAllStatusesWithKeys()
    {
        $return = array();
        foreach (self::getAllStatuses() as $status) {
            $return[$status] = $status;
        }
        return $return;
    }

    /**
     * PS order will be NOT updated if Reverb order is in one of this status
     * @return array
     */
    public static function getFinalStatuses()
    {
        // Finally, we always update PS order status
        return array(
            //self::REVERB_ORDERS_STATUS_PICKED_UP,
            //self::REVERB_ORDERS_STATUS_SHIPPED,
            //self::REVERB_ORDERS_STATUS_RECEIVED,
            //self::REVERB_ORDERS_STATUS_CANCELLED,
        );
    }

    /**
     * PS order will be NOT created if Reverb order is in one of this status
     * @return array
     */
    public static function getReverbStatusesIgnoredForOrderCreation()
    {
        return array(
            self::REVERB_ORDERS_STATUS_CANCELLED,
            //self::REVERB_ORDERS_STATUS_REFUNDED,
            self::REVERB_ORDERS_STATUS_BLOCKED,
        );
    }

    /**
     * PS order quantity will be updated (+1) if Reverb order is in one of this status
     * @return array
     */
    public static function getReverbStatusesWhichUpdateExstingOrder()
    {
        return array(
            self::REVERB_ORDERS_STATUS_CANCELLED,
            self::REVERB_ORDERS_STATUS_REFUNDED,
            self::REVERB_ORDERS_STATUS_BLOCKED,
        );
    }

    /**
     * PS order amounts, invoices amounts and payments amounts will be updated
     * with true reverb order amounts if Reverb order is in one of this status
     * @return array
     */
    public static function getReverbStatusesForInvoiceCreation()
    {
        return array(
            self::REVERB_ORDERS_STATUS_UNPAID,
            self::REVERB_ORDERS_STATUS_PAID,
            self::REVERB_ORDERS_STATUS_PARTIALLY_PAID,
            self::REVERB_ORDERS_STATUS_PICKED_UP,
            self::REVERB_ORDERS_STATUS_SHIPPED,
            self::REVERB_ORDERS_STATUS_RECEIVED,
        );
    }

    public static function getPsStateAccordingReverbStatus($reverbStatus)
    {
        switch ($reverbStatus) {

            /** Reverb statuses */
            case self::REVERB_ORDERS_STATUS_UNPAID:
            case self::REVERB_ORDERS_STATUS_PENDING_PAYMENT:
            case self::REVERB_ORDERS_STATUS_PENDING_REVIEW:
                return Configuration::get('REVERB_OS_PENDING_PAYMENT');
                break;

            case self::REVERB_ORDERS_STATUS_BLOCKED:
                return Configuration::get('REVERB_OS_BLOCKED');
                break;

            case self::REVERB_ORDERS_STATUS_PARTIALLY_PAID:
                return Configuration::get('REVERB_OS_PARTIALLY_PAID');
                break;

            /** Prestashop statuses */
            case self::REVERB_ORDERS_STATUS_PICKED_UP:
            case self::REVERB_ORDERS_STATUS_SHIPPED:
            case self::REVERB_ORDERS_STATUS_RECEIVED:
                return Configuration::get('PS_OS_DELIVERED');
                break;

            case self::REVERB_ORDERS_STATUS_PAID:
                return Configuration::get('PS_OS_PAYMENT');
                break;

            case self::REVERB_ORDERS_STATUS_REFUNDED:
                return Configuration::get('PS_OS_REFUND');
                break;

            case self::REVERB_ORDERS_STATUS_CANCELLED:
                return Configuration::get('PS_OS_CANCELED');
                break;

            default:
                return false;
        }
    }

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

        if (Tools::isSubmit('submitFilterps_reverb_orders')
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
            $sql->orderBy('ro.updated_at DESC');
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
                if (isset($list_field[$field])) {
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

        $sql->orderBy('ro.updated_at DESC');

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
            $sql->orderBy('ro.updated_at DESC');
        }

        $result = Db::getInstance()->getRow($sql);

        return $result['totals'];
    }

    /**
     * @param $idShop
     * @param $idShopGroup
     * @param $idOrder
     * @param $idProduct
     * @param $idProductAttribute
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
        $idProduct,
        $idProductAttribute,
        $orderNumber,
        $sku,
        $status,
        $details,
        $shippingMethod,
        $shippingTracker = null
    ) {
        $this->module->logs->infoLogs('insertOrder');
        $this->module->logs->infoLogs(' - $idOrder = ' . $idOrder);
        $this->module->logs->infoLogs(' - $idProduct = ' . $idProduct);
        $this->module->logs->infoLogs(' - $idProductAttribute = ' . $idProductAttribute);
        $this->module->logs->infoLogs(' - $orderNumber = ' . $orderNumber);
        $this->module->logs->infoLogs(' - $sku = ' . $sku);
        $this->module->logs->infoLogs(' - $status = ' . $status);
        $this->module->logs->infoLogs(' - $details = ' . $details);
        $this->module->logs->infoLogs(' - $shippingMethod = ' . $shippingMethod);

        $params = array(
            'status' => pSQL($status),
            'details' => pSQL($details),
            'reverb_order_number' => pSQL($orderNumber),
            'reverb_product_sku' => pSQL($sku),
            'shipping_method' => $shippingMethod,
            'shipping_tracker' => $shippingTracker,
            'created_at' => (new \DateTime())->format('Y-m-d H:i:s'),
        );

        if ($idOrder) {
            $params['id_order'] = (int)$idOrder;
        }
        if ($idProduct) {
            $params['id_product'] = (int)$idProduct;
        }
        if ($idProductAttribute) {
            $params['id_product_attribute'] = (int)$idProductAttribute;
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
    public function update($id_reverb_orders, $params = array())
    {
        $params['updated_at'] = (new \DateTime())->format('Y-m-d H:i:s');
        $this->module->logs->infoLogs('Update reverb_orders = ' . $id_reverb_orders . ' with params :' . var_export($params, true));

        Db::getInstance()->update(
            'reverb_orders',
            $params,
            'id_reverb_orders= ' . (int)$id_reverb_orders
        );
    }
}
