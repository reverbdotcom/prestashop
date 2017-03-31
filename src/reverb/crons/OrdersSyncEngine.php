<?php


require_once dirname(__FILE__) . '/../classes/ReverbClient.php';
require_once dirname(__FILE__) . '/../classes/helper/HelperCron.php';
require_once dirname(__FILE__) . '/../classes/helper/ContextCron.php';
require_once dirname(__FILE__) . '/../classes/ReverbOrders.php';
require_once dirname(__FILE__) . '/../classes/models/ReverbSync.php';
require_once dirname(__FILE__) . '/../classes/ReverbProduct.php';
require_once dirname(__FILE__) . '/../reverb.php';

/**
 * Engine for process Orders from Reverb
 *
 * @package Reverb
 * @author Johan Protin
 * @copyright Copyright (c) 2017 - Johan Protin
 * @license Apache License Version 2.0, January 2004
 */
class OrdersSyncEngine
{
    const EMAIL_GENERIC_CUSTOMER = 'prestashop@reverb.com';
    const ADDRESS_GENERIC = 'pickup';

    protected $module;

    /**
     * OrdersSyncEngine constructor.
     */
    public function __construct($module)
    {
        $this->module = $module;
    }

    //TODO Créer un log cron et non Request
    //TODO Itérer sur les shop

    /**
     *  Processing an sync with orders from Reverb
     *
     */
    public function processSyncOrder()
    {
        try {
            // Init configuration for sync orders
            $helper = new \HelperCron($this->module);
            $context = new \ContextCron($this->module);

            $idCron = $helper->insertOrUpdateCronStatus(null, CODE_CRON_ORDERS, $helper::CODE_CRON_STATUS_PROGRESS);

            // Call and getting all orders from reverb
            $reverbOrders = new \Reverb\ReverbOrders($this->module);

            $date = $helper->getDateLastCronWithStatus(CODE_CRON_ORDERS, $helper::CODE_CRON_STATUS_END);
            $orders = $reverbOrders->getOrders($date);

            $this->logInfoCrons('# ' . count($orders) . ' order(s) to sync');

            // Process Order in Prestashop
            foreach ($orders as $order) {
                if (!$this->checkIfOrderAlreadySync($order)) {
                    $idOrder = $this->createPrestashopOrder($order, $context ,$idCron);
                    $this->logInfoCrons('Order ' . $order['order_number'] . ' is now synced with id : '  . $idOrder);
                } else {
                    $this->logInfoCrons('Order ' . $order['order_number'] . ' is already synced.');
                }
            }

            $helper->insertOrUpdateCronStatus($idCron, CODE_CRON_ORDERS, $helper::CODE_CRON_STATUS_END);
        } catch (\Exception $e) {
            $this->module->logs->errorLogsReverb('Error in cron ' . CODE_CRON_ORDERS . $e->getTraceAsString());
            $helper->insertOrUpdateCronStatus($idCron, CODE_CRON_ORDERS, $helper::CODE_CRON_STATUS_ERROR,
                $e->getMessage());
        }
    }


    /**
     *  Check if order has already
     *
     * @param $order
     * @return false|null|string
     */
    public function checkIfOrderAlreadySync($order)
    {
        $sql = new DbQuery();
        $sql->select('o.id_order')
            ->from('orders', 'o')
            ->where('o.`reference` = "' . $order['order_number'] . '"');

        $id = Db::getInstance()->getValue($sql);
        return $id;
    }

    /**
     *  Create a cart with products
     *
     * @param $id_customer
     * @param $id_shop
     * @param $id_shop_group
     */
    public function initCart($order, $context, $id_address, $id_currency)
    {
        $cart = new Cart();
        $cart->id_shop_group = $context->getIdShop();
        $cart->id_shop = $context->getIdShopGroup();
        $cart->id_customer = $context->getIdCustomer();
        $cart->id_carrier = 0;
        $cart->id_address_delivery = $id_address;
        $cart->id_address_invoice = $id_address;
        $cart->id_currency = $id_currency;
        $cart->add();

        // Add product in cart
        $id_product = Product::searchByName($context->getIdLang(), $order['sku']);
        $cart->updateQty(1, $id_product, null, false);

        return $cart->id;
    }

    /**
     *  Create an address for one customer
     *
     * @param $order
     * @param $id_customer
     * @param $id_shop
     * @param $id_shop_group
     * @return int idAdress
     */
    public function initAddress($order, $context)
    {
        $address = new Address();
        $address->id_customer = $context->getIdCustomer();
        $address->firstname = $order['buyer_first_name'];
        $address->lastname = $order['buyer_last_name'];
        $address->alias = $order['buyer_last_name'];

        if ($order['shipping_address']) {
            $shipping = $order['shipping_address'];
            $address->address1 = $shipping['street_address'];
            $address->address2 = $shipping['extended_address'];
            $address->postcode = $shipping['postal_code'];
            $address->id_state = State::getIdByName($shipping['region']);
            $address->city = $shipping['locality'];
            $address->id_country = Country::getByIso($shipping['country_code']);
        } else {
            $country = Tools::strtolower(Country::getIsoById(Configuration::get('PS_SHOP_COUNTRY_ID')));
            $region = Tools::strtolower(State::getNameById(Configuration::get('PS_SHOP_STATE_ID')));
            $locality = Configuration::get('PS_SHOP_CITY');

            $address->id_state = $region;
            $address->city = $locality;
            $address->id_country = $country;
        }

        $address->add();
        return $address->id;
    }

    /**
     * Create Basic customer for Order
     *
     * @param $order
     * @param ContextCron
     * @return int
     */
    public function initCustomer($order, $context,$idCron)
    {
        $customer = new Customer();
        $customer->lastname = $order['buyer_last_name'];
        $customer->firstname = $order['buyer_first_name'];
        $customer->email = $idCron . $order['order_number'] . self::EMAIL_GENERIC_CUSTOMER;
        $customer->passwd = $idCron . $order['order_number'] . $order['buyer_last_name'];
        $customer->id_shop = $context->getIdShop();
        $customer->id_shop_group = $context->getIdShopGroup();
        $customer->active = false;
        $customer->add();

        $context->setIdCustomer($customer->id);
        return $context;
    }

    /**
     *  Create order in prestashop
     *
     * @param $order
     * @param $context ContextCron
     * @return int|null
     */
    public function createPrestashopOrder($orderReverb, $context , $idCron)
    {
        /** @var Order $order */
        $order = new Order();
        $id_currency = Currency::getIdByIsoCode($orderReverb['amount_product']['currency'], $context->getIdShop());

        // Create Customer
        $context = $this->initCustomer($orderReverb, $context,$idCron);

        $orderDetail = new OrderDetail();

        // Create an Address for Customer
        $id_address = $this->initAddress($orderReverb, $context);

        // Create Cart with products
        $id_cart = $this->initCart($orderReverb, $context, $id_address, $id_currency);

        $order->id_cart = $id_cart;
        $order->id_address_delivery = $id_address;
        $order->id_address_invoice = $id_address;
        $order->reference = $orderReverb['order_number'];
        $order->id_carrier = 0;
        $order->id_customer = $context->getIdCustomer();
        $order->round_mode = Configuration::get('PS_PRICE_ROUND_MODE');
        $order->round_type = Configuration::get('PS_ROUND_TYPE');
        $order->invoice_date = '0000-00-00 00:00:00';
        $order->delivery_date = '0000-00-00 00:00:00';
        $order->id_currency = $id_currency;
        $order->id_shop = $context->getIdShop();
        $order->id_shop_group = $context->getIdShopGroup();
        $order->payment = $orderReverb['payment_method'];
        $order->module = 0;
        $order->current_state = (int)Configuration::get('PS_OS_PAYMENT');
        $order->total_paid = $orderReverb['total']['amount'];
        $order->total_products = $orderReverb['amount_product']['amount'];
        $order->total_products_wt = $orderReverb['amount_product']['amount'];
        $order->total_paid_real = $orderReverb['total']['amount'];
        $order->total_shipping = $orderReverb['shipping']['amount'];
        $order->conversion_rate = 0;
        $order->secure_key = 0;
        $id_order = $order->add();

        return $id_order;
    }

    /**
     * @param $message
     */
    private function logInfoCrons($message)
    {
        $this->module->logs->requestLogs($message);
    }
}
