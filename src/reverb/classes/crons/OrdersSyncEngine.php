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
        $this->displayName = $this->l('Reverb order', array(), 'Admin.OrdersCustomers.Feature');
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

    /** @var  Reverb */
    protected $module;

    public $helper;

    /**
     * OrdersSyncEngine constructor.
     */
    public function __construct($module, \HelperCron $helper)
    {
        $this->module = $module;
        $this->helper = $helper;
    }

    //TODO Itérer sur les shop

    /**
     *  Processing an sync with orders from Reverb
     *
     */
    public function processSyncOrder($idCron)
    {
        try {
            $context = new \ContextCron($this->module);

            // Call and getting all orders from reverb
            $reverbOrders = new \Reverb\ReverbOrders($this->module);

            $date = $this->helper->getDateLastCronWithStatus(CODE_CRON_ORDERS, HelperCron::CODE_CRON_STATUS_END);
            $this->logInfoCrons('# Last orders sync : ' . var_export($date, true));
            $orders = $reverbOrders->getOrders($date);

            $this->logInfoCrons('# ' . count($orders) . ' order(s) to sync');

            // Process Order in Prestashop
            foreach ($orders as $order) {
                if (!$this->checkIfOrderAlreadySync($order)) {
                    $this->logInfoCrons('# Order ' . $order['order_number'] . ' is not synced yet.');
                    try {
                        $idOrder = $this->createPrestashopOrder($order, $context, $idCron);
                        $this->module->reverbOrders->insert($idOrder, $order['order_number'], ReverbOrders::REVERB_ORDERS_STATUS_ORDER_SAVED, 'Reverb order synced', $order['shipping_method']);
                        $this->logInfoCrons('# Order ' . $order['order_number'] . ' is now synced with id : ' . $idOrder);
                    } catch (Exception $e) {
                        $this->logInfoCrons('/!\ Error saving order : ' . $e->getMessage());
                    }
                } else {
                    $this->logInfoCrons('# Order ' . $order['order_number'] . ' is already synced.');
                }
            }

            $this->helper->insertOrUpdateCronStatus($idCron, CODE_CRON_ORDERS, HelperCron::CODE_CRON_STATUS_END);
        } catch (\Exception $e) {
            $error = '/!\ Error in cron ' . CODE_CRON_ORDERS . $e->getTraceAsString();
            $this->logInfoCrons($e->getMessage());
            $this->logInfoCrons($error);
            $this->module->logs->errorLogs($error);
            $this->helper->insertOrUpdateCronStatus(
                $idCron,
                CODE_CRON_ORDERS,
                HelperCron::CODE_CRON_STATUS_ERROR,
                $e->getMessage()
            );
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
        $this->logInfoCrons('# Check if order "' . $order['order_number'] . '" exists on prestashop');
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
    public function initCart($order, ContextCron $context, $id_address, $id_currency)
    {
        $this->logInfoCrons('# initCart');

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
        $this->logInfoCrons('## Try to find product = ' . $order['sku']);
        //$id_product = Product::searchByName($context->getIdLang(), $order['sku']);
        $reverbSync = new ReverbSync($this->module);
        $product = $reverbSync->getProductByReference($order['sku']);
        $this->logInfoCrons('## product: ' . var_export($product, true));
        if (empty($product)) {
            throw new Exception('Product "' . $order['sku'] .'" not found on Prestashop !');
        } elseif (count($product) > 1) {
            throw new Exception('More than one product "' . $order['sku'] .'" found on Prestashop !');
        }

        $product = $product[0];

        if (!$product['reverb_enabled']) {
            throw new Exception('Product "' . $order['sku'] .'" is not synced with Reverb !');
        }

        $cart->updateQty(1, $product['id_product'], $product['id_product_attribute'], false);
        $this->logInfoCrons('## 12');
        $this->logInfoCrons('# cart added : ' . $cart->id);

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
    public function initAddress($order, ContextCron $context)
    {
        $this->logInfoCrons('# initAddress');

        $address = new Address();
        $address->id_customer = $context->getIdCustomer();
        $address->firstname = $order['buyer_first_name'];
        $address->lastname = $order['buyer_last_name'];
        $address->alias = $order['buyer_last_name'];

        if (
            $order['shipping_method'] == 'shipped'
            && array_key_exists('shipping_address',$order)
            && !empty($order['shipping_address'])
        ) {
            $this->logInfoCrons('## Add buyer shipping address :');
            $shipping = $order['shipping_address'];
            $address->address1 = $shipping['street_address'];
            $address->address2 = $shipping['extended_address'];
            $address->postcode = $shipping['postal_code'];
            $address->id_state = State::getIdByName($shipping['region']);
            $address->city = $shipping['locality'];
            $address->id_country = Country::getByIso($shipping['country_code']);
        } else {
            $this->logInfoCrons('## Local shipping => add seller address');
            $country = Configuration::get('PS_SHOP_COUNTRY_ID');
            $this->logInfoCrons('## $country = ' . var_export($country, true));
            if (!$country) {
                throw new Exception('Unable to find configuration PS_SHOP_COUNTRY_ID');
            }
            $address->id_country = $country;

            $address1 = Configuration::get('PS_SHOP_ADDR1');
            if (!$address1) {
                throw new Exception('Unable to find configuration PS_SHOP_ADDR1');
            }
            $address->address1 = $address1;

            $address->address2 = Configuration::get('PS_SHOP_ADDR2');
            $address->city = Configuration::get('PS_SHOP_CITY');
            $address->id_state = Configuration::get('PS_SHOP_STATE_ID');
        }
        $address->add();
        $this->logInfoCrons('## address added = ' . var_export($address->id, true));

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
     * @param int $idCron
     * @return int|null
     */
    public function createPrestashopOrder($orderReverb, $context, $idCron)
    {
        $extra_vars = array();
        $id_currency = Currency::getIdByIsoCode($orderReverb['amount_product']['currency'], $context->getIdShop());
        if (empty($id_currency)) {
            throw new Exception('Reverb order is in currency ' . $orderReverb['amount_product']['currency'] . ' wich is not activataed on your shop ' . $context->getIdShop());
        }

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

        $this->logInfoCrons('# validateOrder');
        $payment_module->validateOrder(
            $this->module->getContext()->cart->id,
            (int)Configuration::get('PS_OS_PAYMENT'),
            (float)$orderReverb['total']['amount'],
            $this->module->displayName,
            '',
            $extra_vars,
            $id_currency,
            false,
            $this->module->customer->secure_key,
            $shop
        );

        $this->logInfoCrons('# validateOrder finish');

        $order = new Order((int)$payment_module->currentOrder);
        $order->reference = $orderReverb['order_number'];
        $order->total_shipping = str_replace(array(',', ' '), array('.', ''), $orderReverb['shipping']['amount']);
        $order->total_shipping_tax_excl = str_replace(array(',', ' '), array('.', ''), $orderReverb['shipping']['amount']);
        $order->total_shipping_tax_incl = str_replace(array(',', ' '), array('.', ''), $orderReverb['shipping']['amount']);
        $order->total_paid_real = (float) $orderReverb['total']['amount'];
        $order->total_paid_tax_incl = (float)  $orderReverb['total']['amount'];
        $order->total_paid = (float)  $orderReverb['total']['amount'];
        $order->current_state = (int)Configuration::get('PS_OS_PAYMENT');
        $order->update();

        $this->logInfoCrons('Order ' . $order->reference . ' : ' . $order->reference . ' is now synced');

        return  $order->id;
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
