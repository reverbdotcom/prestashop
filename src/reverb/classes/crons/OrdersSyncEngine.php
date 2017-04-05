<?php


require_once dirname(__FILE__) . '/../../classes/ReverbClient.php';
require_once dirname(__FILE__) . '/../../classes/helper/HelperCron.php';
require_once dirname(__FILE__) . '/../../classes/helper/ContextCron.php';
require_once dirname(__FILE__) . '/../../classes/ReverbOrders.php';
require_once dirname(__FILE__) . '/../../classes/models/ReverbSync.php';
require_once dirname(__FILE__) . '/../../classes/ReverbProduct.php';
require_once dirname(__FILE__) . '/../../reverb.php';

/**
 * Class for reverb order
 *
 * @package Reverb
 * @author Johan Protin
 * @copyright Copyright (c) 2017 - Johan Protin
 * @license Apache License Version 2.0, January 2004
 */
class ReverbPayment extends PaymentModule
{
    public $active = 1;
    public $name = 'reverb';

    public function __construct()
    {
        $this->displayName = $this->trans('Reverb order', array(), 'Admin.OrdersCustomers.Feature');
    }
}


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

    public $helper;

    /**
     * OrdersSyncEngine constructor.
     */
    public function __construct($module)
    {
        $this->module = $module;
    }

    //TODO Itérer sur les shop

    /**
     *  Processing an sync with orders from Reverb
     *
     */
    public function processSyncOrder($idCron)
    {
        try {
            // Init configuration for sync orders
            $helper = new \HelperCron($this->module);
            $this->helper = $helper;

            $context = new \ContextCron($this->module);

            // Call and getting all orders from reverb
            $reverbOrders = new \Reverb\ReverbOrders($this->module);

            $date = $helper->getDateLastCronWithStatus(CODE_CRON_ORDERS, $helper::CODE_CRON_STATUS_END);
            $orders = $reverbOrders->getOrders($date);

            $this->logInfoCrons('# ' . count($orders) . ' order(s) to sync');

            // Process Order in Prestashop
            foreach ($orders as $order) {
                if (!$this->checkIfOrderAlreadySync($order)) {
                    $idOrder = $this->createPrestashopOrder($order, $context, $idCron);
                    $this->logInfoCrons('Order ' . $order['order_number'] . ' is now synced with id : ' . $idOrder);
                } else {
                    $this->logInfoCrons('Order ' . $order['order_number'] . ' is already synced.');
                }
            }

            $helper->insertOrUpdateCronStatus($idCron, CODE_CRON_ORDERS, $helper::CODE_CRON_STATUS_END);
        } catch (\Exception $e) {
            $error = 'Error in cron ' . CODE_CRON_ORDERS . $e->getTraceAsString();
            $this->logInfoCrons($error);
            $this->module->logs->errorLogsReverb($error);
            $helper->insertOrUpdateCronStatus($idCron, CODE_CRON_ORDERS, $helper::CODE_CRON_STATUS_ERROR,
                $e->getMessage());
        }
    }


    /**
     *  Check if order has already synced
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
     *  Get product attribute
     *
     * @param $order
     * @return false|null|string
     */
    public function getProductAttribute($order)
    {
        $sql = new DbQuery();
        $sql->select('pa.id_order')
            ->from('product_attributes', 'pa')
            ->where('pa.`reference` = "' . $order['order_number'] . '"');

        $id = Db::getInstance()->getValue($sql);
        return $id;
    }

    /**
     *  Create a cart with products
     *
     * @param $id_customer
     * @param $id_shop
     * @param $id_shop_group
     * @return int id Cart
     */
    public function initCart($order, $context, $id_address, $id_currency)
    {
        $cart = new Cart();
        $carrier = new Carrier();
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

        // TODO Recupérer l'id Attribute
        //$ip_product_attribute = $this->getProductAttribute();
        //$cart->updateQty(1, $ip_product_attribute, null, false);

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

        if (array_key_exists('shipping_address',$order) &&  $order['shipping_address']) {
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
    public function initCustomer($order, $context, $idCron)
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
    public function createPrestashopOrder($orderReverb, $context, $idCron)
    {
        $extra_vars = array();
        $id_currency = Currency::getIdByIsoCode($orderReverb['amount_product']['currency'], $context->getIdShop());

        // Create Customer
        $context = $this->initCustomer($orderReverb, $context, $idCron);

        // Create an Address for Customer
        $id_address = $this->initAddress($orderReverb, $context);

        // Create Cart with products
        $id_cart = $this->initCart($orderReverb, $context, $id_address, $id_currency);

        $this->module->getContext()->cart = new Cart((int)$id_cart);
        $this->module->customer = new Customer($context->getIdCustomer());
        $this->module->currency = new Currency((int)$this->module->getContext()->cart->id_currency);
        $this->module->language = new Language((int)$this->module->getContext()->customer->id_lang);
        $shop = new Shop($context->getIdShop());
        Shop::setContext(Shop::CONTEXT_SHOP, $context->getIdShop());

        $payment_module = new \ReverbPayment();
        $payment_module->validateOrder(
                $this->module->getContext()->cart->id,
                (int)Configuration::get('PS_OS_PAYMENT'),
                (float)$orderReverb['total']['amount'],
                $this->module->displayName,
                '',
                $extra_vars,
                $this->module->getContext()->cart->id_currency,
                false,
            $this->module->customer->secure_key ,$shop
            );

        $order = new Order((int)$payment_module->currentOrder);
        $order->reference = $orderReverb['order_number'];
        $order->total_shipping = $orderReverb['shipping']['amount'];
        $order->total_shipping_tax_excl = $orderReverb['shipping']['amount'];
        $order->total_shipping_tax_incl = $orderReverb['shipping']['amount'];
        $order->total_paid_real = (float) $orderReverb['total']['amount'];
        $order->total_paid_tax_incl = (float)  $orderReverb['total']['amount'];
        $order->total_paid = (float)  $orderReverb['total']['amount'];
        $order->current_state = (int)Configuration::get('PS_OS_PAYMENT');
        $order->update();

        $this->logInfoCrons('Order ' . $order->reference . ' is now synced');

        return  $this->currentOrder;
    }

    /**
     *  Log infos
     *
     * @param $message
     */
    private function logInfoCrons($message)
    {
        $this->module->logs->cronLogs($message);
    }
}
