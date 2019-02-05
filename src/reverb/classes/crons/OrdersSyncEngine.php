<?php
/**
 * Synchronize order from Reverb
 *
 * @author Johan Protin
 * @copyright Copyright (c) 2017 - Johan Protin
 * @license Apache License Version 2.0, January 2004
 * @package Reverb
 */

require_once dirname(__FILE__) . '/../../classes/ReverbClient.php';
require_once dirname(__FILE__) . '/../../classes/helper/HelperCron.php';
require_once dirname(__FILE__) . '/../../classes/helper/ContextCron.php';
require_once dirname(__FILE__) . '/../../classes/ReverbOrders.php';
require_once dirname(__FILE__) . '/../../classes/models/ReverbSync.php';
require_once dirname(__FILE__) . '/../../classes/ReverbProduct.php';
require_once dirname(__FILE__) . '/../../reverb.php';
require_once dirname(__FILE__) . '/ReverbPayment.php';

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

    const CODE_CRON_ORDERS = 'orders';

    const ERROR_IGNORED = 2;

    const DEBUG_MODE = true;

    /** @var  Reverb */
    protected $module;

    /** @var HelperCron  */
    public $helper;

    /** @var ContextCron  */
    public $context;

    public $nbOrdersTotal = 0;
    public $nbOrdersSynced = 0;
    public $nbOrdersError = 0;
    public $nbOrdersIgnored = 0;
    public $currentDate;

    public $id_product = null;
    public $id_product_attribute = null;

    public $customerAddressId;

    /** @var ReverbSync */
    public $reverbSync;

    public $playUpdate = false;
    public $originShipped = false;

    private $result_tax;

    /**
     * OrdersSyncEngine constructor.
     * @param $module
     * @param HelperCron $helper
     * @param ContextCron $context
     */
    public function __construct($module, \HelperCron $helper, ContextCron $context)
    {
        $this->module = $module;
        $this->helper = $helper;
        $this->context = $context;
        $this->currentDate = (new \DateTime())->format('Y-m-d H:i:s');
        $this->reverbSync = new ReverbSync($this->module);
    }

    /**
     * Processing orders sync from Reverb
     *
     * @param $idCron
     * @param bool $reconciliation
     */
    public function processSyncOrder($idCron, $reconciliation = false)
    {
        try {
            // Call and getting all orders from reverb
            $reverbOrders = new \Reverb\ReverbOrders($this->module);

            if ($reconciliation) {
                $startDate = new \DateTime('yesterday');
                $endDate = new \DateTime('midnight');
            } else {
                $date = $this->helper->getDateLastCronWithStatus(HelperCron::CODE_CRON_STATUS_END);
                $this->logInfoCrons('# Last orders sync : ' . $date);
                if ($date) {
                    $startDate = new \DateTime($date);
                    $startDate->sub(new DateInterval('PT12H'));
                    $this->logInfoCrons('# sub 12 hours : ' . $startDate->format('Y-m-d\TH:i:s'));
                } else {
                    $startDate = null;
                }

                $endDate = null;
            }
            $distReverbOrders = $reverbOrders->getOrders($startDate, $endDate);

            $this->nbOrdersTotal = count($distReverbOrders);

            $this->logInfoCrons('# ' . $this->nbOrdersTotal . ' order(s) to sync');

            // Process Order in Prestashop
            foreach ($distReverbOrders as $distReverbOrder) {
                $this->syncOrder($distReverbOrder);
            }

            $this->helper->insertOrUpdateCronStatus(
                $idCron,
                CODE_CRON_ORDERS,
                ($this->nbOrdersError > 1 ? HelperCron::CODE_CRON_STATUS_ERROR : HelperCron::CODE_CRON_STATUS_END),
                $this->nbOrdersSynced . '/' . $this->nbOrdersTotal . ' order(s) synced, ' . $this->nbOrdersError . ' error(s), ' . $this->nbOrdersIgnored . ' ignored',
                $this->nbOrdersTotal,
                $this->nbOrdersSynced
            );
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

    public function syncOrder($distReverbOrder)
    {
        $reverbOrder = $this->checkIfOrderAlreadySync($distReverbOrder);

        if (empty($reverbOrder)) {
            $this->logInfoCrons('# Order ' . $distReverbOrder['order_number'] . ' is not synced yet => insert');
            return $this->createOrderSync($distReverbOrder);
        } else {
            $this->logInfoCrons('# Order ' . $distReverbOrder['order_number'] . ' already synced before => update');
            return $this->updateOrderSync($distReverbOrder, $reverbOrder);
        }
    }

    /**
     * Sync order for the first time
     *
     * @param array $distReverbOrder
     * @return array
     */
    public function createOrderSync($distReverbOrder)
    {
        try {
            // If status is ignored, we do not create PS order !
            if (in_array($distReverbOrder['status'], ReverbOrders::getReverbStatusesIgnoredForOrderCreation())) {
                $message = 'Order ' . $distReverbOrder['order_number'] . ' status not synced : ' . $distReverbOrder['status'];
                $this->nbOrdersIgnored++;
                $this->logInfoCrons('# ' . $message);
                $this->module->reverbOrders->insert(
                    $this->context->getIdShop(),
                    $this->context->getIdShopGroup(),
                    null,
                    null,
                    null,
                    $distReverbOrder['order_number'],
                    $distReverbOrder['sku'],
                    ReverbOrders::REVERB_ORDERS_STATUS_IGNORED,
                    $message,
                    isset($distReverbOrder['shipping_method']) ? $distReverbOrder['shipping_method'] : null
                );
                return array(
                    'status' => ReverbOrders::REVERB_ORDERS_STATUS_IGNORED,
                    'message' => $message,
                    'last-synced' => $this->currentDate,
                    'reverb-id' => $distReverbOrder['order_number'],
                );
            }

            // We create the PS order
            $idOrder = $this->createPrestashopOrder($distReverbOrder);
            $this->module->reverbOrders->insert(
                $this->context->getIdShop(),
                $this->context->getIdShopGroup(),
                $idOrder,
                $this->id_product,
                $this->id_product_attribute,
                $distReverbOrder['order_number'],
                $distReverbOrder['sku'],
                $distReverbOrder['status'],
                'Reverb order synced',
                isset($distReverbOrder['shipping_method']) ? $distReverbOrder['shipping_method'] : null
            );
            $this->nbOrdersSynced++;
            $this->logInfoCrons('# Order ' . $distReverbOrder['order_number'] . ' is now synced with id : ' . $idOrder);

            if ($this->originShipped) {
                // Origin status = shipped -> update status to Payment accepted
                $distReverbOrder['status'] = ReverbOrders::REVERB_ORDERS_STATUS_PAID;
                $this->updatePaidOrder($idOrder, $distReverbOrder);
                // Origin status = shipped -> update status to Shipped after the payment accepted
                $distReverbOrder['status'] = ReverbOrders::REVERB_ORDERS_STATUS_SHIPPED;
                $this->updatePaidOrder($idOrder, $distReverbOrder);

            } else if ($this->playUpdate && !$this->originShipped) {
                $this->updatePaidOrder($idOrder, $distReverbOrder);
            }

            return array(
                'status' => $distReverbOrder['status'],
                'message' => 'Reverb order synced',
                'last-synced' => $this->currentDate,
                'reverb-id' => $distReverbOrder['order_number'],
                'order-id' => $idOrder,
            );
        } catch (Exception $e) {
            $this->logInfoCrons('/!\ Error saving order : ' . $e->getMessage());
            $this->logInfoCrons($e->getTraceAsString());
            $this->nbOrdersError++;
            $this->module->reverbOrders->insert(
                $this->context->getIdShop(),
                $this->context->getIdShopGroup(),
                (isset($idOrder) && !empty($idOrder)) ? $idOrder : null,
                $this->id_product,
                $this->id_product_attribute,
                $distReverbOrder['order_number'],
                $distReverbOrder['sku'],
                ReverbOrders::REVERB_ORDERS_STATUS_ERROR,
                $e->getMessage(),
                isset($distReverbOrder['shipping_method']) ? $distReverbOrder['shipping_method'] : null
            );
            return array(
                'status' => ReverbOrders::REVERB_ORDERS_STATUS_ERROR,
                'message' => $e->getMessage(),
                'last-synced' => $this->currentDate,
                'reverb-id' => $distReverbOrder['order_number'],
            );
        }
    }

    public function updatePaidOrder($idOrder, $distReverbOrder)
    {
        $order = new Order($idOrder);
        $reverbOrder = $this->checkIfOrderAlreadySync($distReverbOrder);
        $this->updatePsOrderByReverbOrder($order, $reverbOrder, $distReverbOrder);
    }

    /**
     * Sync order already synced before
     *
     * @param array $distReverbOrder
     * @param array $reverbOrder
     * @return array
     */
    public function updateOrderSync($distReverbOrder, $reverbOrder)
    {
        try {
            // Reverb status unknown
            if (!in_array($distReverbOrder['status'], ReverbOrders::getAllReverbStatuses())) {
                $message = 'Order ' . $distReverbOrder['order_number'] . ' status not synced : ' . $distReverbOrder['status'];
                $this->nbOrdersIgnored++;
                $this->logInfoCrons('# ' . $message);
                $this->module->reverbOrders->update($reverbOrder['id_reverb_orders'], array(
                    'status' => ReverbOrders::REVERB_ORDERS_STATUS_IGNORED,
                    'details' => $message,
                ));
                return array(
                    'status' => ReverbOrders::REVERB_ORDERS_STATUS_IGNORED,
                    'message' => $message,
                    'last-synced' => $this->currentDate,
                    'reverb-id' => $distReverbOrder['order_number'],
                );
            }

            // PS order does not exist yet
            if (empty($reverbOrder['id_order'])) {
                // If status is ignored, we do not create PS order !
                if (in_array($distReverbOrder['status'], ReverbOrders::getReverbStatusesIgnoredForOrderCreation())) {
                    $message = 'Order ' . $distReverbOrder['order_number'] . ' status not synced : ' . $distReverbOrder['status'];
                    $this->nbOrdersIgnored++;
                    $this->logInfoCrons('# ' . $message);
                    $this->module->reverbOrders->update($reverbOrder['id_reverb_orders'], array(
                        'reverb_order_number' => $distReverbOrder['order_number'],
                        'reverb_product_sku' => $distReverbOrder['sku'],
                        'status' => $distReverbOrder['status'],
                        'details' => $message,
                        'shipping_method' => isset($distReverbOrder['shipping_method']) ? $distReverbOrder['shipping_method'] : null,
                    ));
                    return array(
                        'status' => $distReverbOrder['status'],
                        'message' => $message,
                        'last-synced' => $this->currentDate,
                        'reverb-id' => $distReverbOrder['order_number'],
                    );
                }

                // Create PS order
                $idOrder = $this->createPrestashopOrder($distReverbOrder);
                $this->module->reverbOrders->update($reverbOrder['id_reverb_orders'], array(
                    'id_order' => $idOrder,
                    'id_product' => $this->id_product,
                    'id_product_attribute' => $this->id_product_attribute,
                    'reverb_order_number' => $distReverbOrder['order_number'],
                    'reverb_product_sku' => $distReverbOrder['sku'],
                    'status' => $distReverbOrder['status'],
                    'details' => 'Reverb order synced',
                    'shipping_method' => isset($distReverbOrder['shipping_method']) ? $distReverbOrder['shipping_method'] : null,
                ));
                $this->nbOrdersSynced++;
                $this->logInfoCrons('# Order ' . $distReverbOrder['order_number'] . ' is now synced with id : ' . $idOrder);

                if ($this->originShipped) {
                    // Origin status = shipped -> update status to Payment accepted
                    $distReverbOrder['status'] = ReverbOrders::REVERB_ORDERS_STATUS_PAID;
                    $this->updatePaidOrder($idOrder, $distReverbOrder);
                    // Origin status = shipped -> update status to Shipped after the payment accepted
                    $distReverbOrder['status'] = ReverbOrders::REVERB_ORDERS_STATUS_SHIPPED;
                    $this->updatePaidOrder($idOrder, $distReverbOrder);

                } else if ($this->playUpdate && !$this->originShipped) {
                    $this->updatePaidOrder($idOrder, $distReverbOrder);
                }

                return array(
                    'status' => $distReverbOrder['status'],
                    'message' => 'Reverb order synced',
                    'last-synced' => $this->currentDate,
                    'reverb-id' => $distReverbOrder['order_number'],
                );
            }

            // PS order already exists
            $this->logInfoCrons('# Prestashop order already saved : ' . $reverbOrder['id_order'] . ' - ' . $reverbOrder['status'] . ' => update');
            $psOrder = new Order($reverbOrder['id_order']);
            return $this->updatePsOrderByReverbOrder($psOrder, $reverbOrder, $distReverbOrder);
        } catch (Exception $e) {
            $this->logInfoCrons('/!\ Error saving order : ' . $e->getMessage());
            $this->logInfoCrons($e->getTraceAsString());
            $this->nbOrdersError++;
            $this->module->reverbOrders->update($reverbOrder['id_reverb_orders'], array(
                'status' => ReverbOrders::REVERB_ORDERS_STATUS_ERROR,
                'details' => pSQL($e->getMessage()),
                'shipping_method' => isset($distReverbOrder['shipping_method']) ? $distReverbOrder['shipping_method'] : null,
            ));
            return array(
                'status' => ReverbOrders::REVERB_ORDERS_STATUS_ERROR,
                'message' => $e->getMessage(),
                'last-synced' => $this->currentDate,
                'reverb-id' => $distReverbOrder['order_number'],
            );
        }
    }


    /**
     *  Check if order has already synced
     *
     * @param array $distReverbOrder
     * @return false|null|array
     */
    public function checkIfOrderAlreadySync($distReverbOrder)
    {
        $this->logInfoCrons('# Check if order "' . $distReverbOrder['order_number'] . '" exists on prestashop');
        $this->logInfoCrons('# ' . json_encode($distReverbOrder));
        $sql = new DbQuery();
        $sql->select('o.*')
            ->from('reverb_orders', 'o')
            ->where('o.`reverb_order_number` = "' . $distReverbOrder['order_number'] . '"');

        return Db::getInstance()->getRow($sql);
    }

    /**
     * @param Order $psOrder
     * @param array $localReverbOrder
     * @param array $distReverbOrder
     */
    public function updatePsOrderByReverbOrder(Order $psOrder, $localReverbOrder, $distReverbOrder)
    {
        // if Reverb status has no changed, we do nothing
        if ($localReverbOrder['status'] == $distReverbOrder['status']
            && !$this->playUpdate) {
            $this->nbOrdersIgnored++;
            $this->logInfoCrons('# Order ' . $localReverbOrder['reverb_order_number'] . ' status has not changed : ' . $localReverbOrder['status'] . ' =  ' . $distReverbOrder['status']);
            $this->module->reverbOrders->update($localReverbOrder['id_reverb_orders']);
            return array(
                'status' => $localReverbOrder['status'],
                'message' => $localReverbOrder['details'],
                'last-synced' => $this->currentDate,
                'reverb-id' => $distReverbOrder['order_number'],
            );
        }

        // check and get tax informations
        $this->checkTaxReverb($psOrder, $distReverbOrder);

        // if Reverb order is in a final status, we do nothing
        if (in_array($localReverbOrder['status'], ReverbOrders::getFinalStatuses())) {
            $this->nbOrdersIgnored++;
            $this->logInfoCrons('# Order ' . $localReverbOrder['reverb_order_number'] . ' in final status : ' . $localReverbOrder['status']);
            $this->module->reverbOrders->update($localReverbOrder['id_reverb_orders']);

            // Update quantity if needed
            $this->updateOrderQuantity($localReverbOrder, $distReverbOrder);

            // Update PS order address
            $this->updateOrderAddress($psOrder, $distReverbOrder);

            // Update PS order amounts
            if (in_array($distReverbOrder['status'], ReverbOrders::getReverbStatusesForInvoiceCreation())) {
                $this->updatePsOrderAmounts($psOrder, $distReverbOrder);
            }

            // Update PS order details amounts
            $this->updatePsOrderDetailsAmounts($psOrder, $distReverbOrder);

            return array(
                'status' => $localReverbOrder['status'],
                'message' => $localReverbOrder['details'],
                'last-synced' => $this->currentDate,
                'reverb-id' => $distReverbOrder['order_number'],
            );
        }

        $this->updateOrderStatus($psOrder, $distReverbOrder);

        // Update PS order address
        $this->updateOrderAddress($psOrder, $distReverbOrder);

        if (in_array($distReverbOrder['status'], ReverbOrders::getReverbStatusesForInvoiceCreation())) {
            $this->updatePsOrderAmounts($psOrder, $distReverbOrder);
        }

        // Update PS order details amounts
        $this->updatePsOrderDetailsAmounts($psOrder, $distReverbOrder);

        // Update quantity if needed
        $this->updateOrderQuantity($localReverbOrder, $distReverbOrder);

        $message = 'Order ' . $distReverbOrder['order_number'] . ' sync updated : ' . $distReverbOrder['status'];
        $this->nbOrdersSynced++;
        $this->logInfoCrons('# ' . $message);
        $this->module->reverbOrders->update($localReverbOrder['id_reverb_orders'], array(
            'status' => $distReverbOrder['status'],
            'details' => $message,
        ));

        return array(
            'status' => $distReverbOrder['status'],
            'message' => $message,
            'last-synced' => $this->currentDate,
            'reverb-id' => $distReverbOrder['order_number'],
        );
    }

    /**
     * Update PS order status according reverb order status
     * @param Order $psOrder
     * @param $distReverbOrder
     */
    public function updateOrderStatus(Order $psOrder, $distReverbOrder)
    {
        // Update PS order according reverb order status
        $id_order_state = ReverbOrders::getPsStateAccordingReverbStatus($distReverbOrder['status']);

        if ($psOrder->current_state != $id_order_state) {
            $order_history = new OrderHistory();
            $order_history->id_order = $psOrder->id;
            $order_history->id_order_state = $id_order_state;
            $order_history->changeIdOrderState($id_order_state, $psOrder->id);
            $order_history->add();

            $psOrder->current_state = $id_order_state;
            $psOrder->update();
        }
    }

    /**
     * @param array $localReverbOrder
     * @param array $distReverbOrder
     * @return boolean
     */
    public function updateOrderQuantity($localReverbOrder, $distReverbOrder)
    {
        $this->logInfoCrons('# Check if quantity has to be updated');
        if (!isset($localReverbOrder['id_product'])) {
            $this->logInfoCrons('## id_product is empty');
            return false;
        }
        if (in_array($distReverbOrder['status'], ReverbOrders::getReverbStatusesWhichUpdateExstingOrder())) {
            $productFull = $this->reverbSync->getProductWithStatus($localReverbOrder['id_product'], $localReverbOrder['id_product_attribute']);

            if (empty($productFull)) {
                $this->logInfoCrons('## id_product : ' . $localReverbOrder['id_product'] . ' not found - id_product_attribute: ' . $localReverbOrder['id_product_attribute']);
                return false;
            }

            $quantity = $productFull['quantity_stock'];

            $qty = isset($distReverbOrder['quantity']) ? (int) $distReverbOrder['quantity'] : 1;

            $this->logInfoCrons('## Change quantity for id_product: ' . $localReverbOrder['id_product'] . ' => from ' . $quantity . ' to ' . ($quantity+$qty));

            StockAvailable::setQuantity(
                $localReverbOrder['id_product'],
                $localReverbOrder['id_product_attribute'],
                ($quantity + $qty),
                $this->context->getIdShop()
            );
            return true;
        }
        return false;
    }

    /**
     * @param array $localReverbOrder
     * @param array $distReverbOrder
     */
    public function updateOrderAddress($psOrder, $distReverbOrder)
    {
        $this->logInfoCrons('### Update shipping address if needed');

        $address = new Address($psOrder->id_address_delivery);
        if (isset($distReverbOrder['shipping_method'])
            && $distReverbOrder['shipping_method'] == 'shipped'
            && array_key_exists('shipping_address', $distReverbOrder)
            && !empty($distReverbOrder['shipping_address'])
        ) {
            $shipping = $distReverbOrder['shipping_address'];

            $this->logInfoCrons('## Update buyer shipping address : ' . json_encode($shipping));

            $address->address1 = $shipping['street_address'];
            $address->address2 = $shipping['extended_address'];
            $address->postcode = $shipping['postal_code'];
            $address->id_state = State::getIdByName($shipping['region']);
            $address->city = $shipping['locality'];
            $address->id_country = Country::getByIso($shipping['country_code']);
        } else {
            $this->logInfoCrons('## Local shipping => update shipping with seller address');
            $country = Configuration::get('PS_SHOP_COUNTRY_ID');
            if (!$country) {
                throw new Exception('Unable to find configuration PS_SHOP_COUNTRY_ID : Please fill your address in prestashop');
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

        $address->save();
    }

    /**
     *  Create a cart with products
     *
     * @param $id_address
     * @param $id_currency
     * @param $product
     * @return Cart
     */
    public function initCart($id_address, $id_currency, $product, $orderReverb)
    {
        $this->logInfoCrons('# initCart : ' . $id_address . ' ' . $id_currency);

        $cart = new Cart();
        $cart->id_shop_group = $this->context->getIdShop();
        $cart->id_shop = $this->context->getIdShopGroup();
        $cart->id_customer = $this->context->getIdCustomer();
        $cart->id_carrier = 0;
        $cart->id_address_delivery = $id_address;
        $cart->id_address_invoice = $id_address;
        $cart->id_currency = $id_currency;
        $cart->add();

        $qty = isset($orderReverb['quantity']) ? (int) $orderReverb['quantity'] : 1;

        // Add product in cart
        $cart->updateQty($qty, $product['id_product'], $product['id_product_attribute'], false);
        $this->logInfoCrons('## cart added : ' . $cart->id);

        return $cart;
    }

    /**
     *  Create an address for one customer
     *
     * @param $order
     * @param Customer $customer
     * @return Address
     */
    public function initAddress($order, Customer $customer)
    {
        $this->logInfoCrons('# initAddress');

        $address = new Address();
        $address->id_customer = $customer->id;
        $address->firstname = $order['buyer_first_name'];
        $address->lastname = $order['buyer_last_name'];
        $address->alias = $order['buyer_last_name'];

        if (isset($order['shipping_method'])
            && $order['shipping_method'] == 'shipped'
            && array_key_exists('shipping_address', $order)
            && !empty($order['shipping_address'])
        ) {
            $this->logInfoCrons('## Add buyer shipping address');
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
                throw new Exception('Unable to find configuration PS_SHOP_COUNTRY_ID : Please fill your address in prestashop');
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

        return $address;
    }

    /**
     * Create Basic customer for Order
     *
     * @param $order
     * @return Customer $customer
     */
    public function initCustomer($order)
    {
        // Try to find customer if exists
        $customer = $this->findCustomerByReverbOrder($order);

        if (!$customer) {
            $this->logInfoCrons('### customer does not exist, create it');
            $customer = new Customer();
            $customer->lastname = $order['buyer_last_name'];
            $customer->firstname = $order['buyer_first_name'];
            $customer->email = $order['buyer_id'] . self::EMAIL_GENERIC_CUSTOMER;
            $customer->passwd = $order['buyer_id'] . $order['buyer_last_name'];
            $customer->id_shop = $this->context->getIdShop();
            $customer->id_shop_group = $this->context->getIdShopGroup();
            $customer->active = false;
            $customer->add();
            $this->logInfoCrons('### customer created : ' . $customer->id);

            // Create a new Address for Customer
            $this->logInfoCrons('### we create also an address');
            $address = $this->initAddress($order, $customer);
            $this->customerAddressId = $address->id;
        }

        return $customer;
    }

    /**
     *  Create order in prestashop
     *
     * @param array $orderReverb
     * @return int|null
     */
    public function createPrestashopOrder($orderReverb)
    {
        $this->playUpdate = false;
        $this->originShipped = false;

        // Check if currency exists and is active
        $extra_vars = array('reverb_order_number' => Tools::safeOutput($orderReverb['order_number']));
        $id_currency = Currency::getIdByIsoCode($orderReverb['amount_product_subtotal']['currency'], $this->context->getIdShop());
        if (empty($id_currency)) {
            throw new Exception('Reverb order is in currency ' . $orderReverb['amount_product_subtotal']['currency'] . ' wich is not activated on your shop ' . $this->context->getIdShop());
        }

        // Check if product exists
        $product = $this->findProductByReverbOrder($orderReverb);

        // Create Customer
        $customer = $this->initCustomer($orderReverb);

        $this->context->setIdCustomer($customer->id);
        // Add customer to Context if empty
        if (!Context::getContext()->customer) {
            Context::getContext()->customer = $customer;
        }

        // Create Cart with products
        $cart = $this->initCart($this->customerAddressId, $id_currency, $product, $orderReverb);

        $this->module->getContext()->cart = $cart;
        $this->module->customer = new Customer($this->context->getIdCustomer());
        $this->module->currency = new Currency((int)$this->module->getContext()->cart->id_currency);
        $this->module->language = new Language((int)$this->module->getContext()->customer->id_lang);

        $this->module->getContext()->language->id = (int)$this->module->getContext()->customer->id_lang;

        $shop = new Shop($this->context->getIdShop());
        Shop::setContext(Shop::CONTEXT_SHOP, $this->context->getIdShop());

        $payment_module = new \ReverbPayment();

        $payment_method = $this->module->displayName;

        $messages = array();
        $message = array(
            "Environment" => $this->module->getReverbConfig(Reverb::KEY_SANDBOX_MODE) ? 'SANDBOX' : 'PRODUCTION',
            "Payment method" => Tools::safeOutput($payment_method),
            "Reverb order number" => Tools::safeOutput($orderReverb['order_number']),
        );

        // Check if product is synced with Reverb
        if (!$product['reverb_enabled']) {
            $messages[] = 'Warning ! This product is not synced to Reverb !';
            $this->logInfoCrons('## Product "' . $orderReverb['sku'] . '" is not synced to Reverb !');
        }

        // Check inventory
        $productFull = $this->reverbSync->getProductWithStatus($product['id_product'], $product['id_product_attribute']);
        $this->logInfoCrons('## product inventory = ' . $productFull['quantity_stock']);
        if ($productFull['quantity_stock'] < 1) {
            $id_order_state = Configuration::get('PS_OS_OUTOFSTOCK_PAID');
            $this->logInfoCrons('## Product "' . $orderReverb['sku'] . '" has no more inventory on Prestashop !');
            $messages[] = 'Warning ! This product is out of stock !';
        } else {
            $id_order_state = ReverbOrders::getPsStateAccordingReverbStatus($orderReverb['status']);
            if (!$id_order_state) {
                throw new \Exception('Status ' . $orderReverb['status'] . ' not implemented');
            }
            $this->logInfoCrons('## Order state set to ' . $id_order_state);
        }

        if (!empty($messages)) {
            $message['message'] = implode('<br />', $messages);
        }

        // Exception for paid status => pending payment anyway
        if ($orderReverb['status'] == ReverbOrders::REVERB_ORDERS_STATUS_PAID) {
            $id_order_state = Configuration::get('REVERB_OS_PENDING_PAYMENT');
            $this->playUpdate = true;
            $this->logInfoCrons('## no origin shipped - If status = '.$orderReverb['status'].' then Order state set to ' . $id_order_state);
        }

        // Exception for shipped status => pending payment anyway
        if ($orderReverb['status'] == ReverbOrders::REVERB_ORDERS_STATUS_SHIPPED) {
            $id_order_state = Configuration::get('REVERB_OS_PENDING_PAYMENT');
            $this->playUpdate = true;
            $this->originShipped = true;
            $this->logInfoCrons('## origin shipped - If status = '.$orderReverb['status'].' then Order state set to ' . $id_order_state);
        }

        // Validate order with amount paid without shipping cost
        $this->logInfoCrons('## validateOrder');
        $amount_tax = isset($orderReverb['amount_tax']) ? (float)$orderReverb['amount_tax']['amount']:0;
        $amount_without_shipping = (float)$orderReverb['amount_product_subtotal']['amount']+$amount_tax;
        Configuration::set('PS_TAX',0);
        $cart_delivery_option = false;

        // fix no duplicate order if error or can't load status
        //$order_id = Order::getByCartId($cart->id);
        if (_PS_VERSION_ >= '1.7.1.0') {
            $order_id = Order::getIdByCartId($cart->id);
        } else {
            $order_id = Order::getOrderByCartId($cart->id);
        }
        if (is_int($order_id)) {
            // order exist, update only status
            // update order status
            $this->logInfoCrons('## update status');
            $objOrder = new Order((int)$order_id, $this->module->getContext()->language->id);
            // check if the status is lie the current status
            if ($objOrder->getCurrentState() != $id_order_state) {
                $this->logInfoCrons('## status is different = ' . $objOrder->getCurrentState() . '!=' . $id_order_state);
                $this->updateOrderStatus($order_id,$id_order_state);
                $this->logInfoCrons('## update status ok');
            } else {
                $this->logInfoCrons('## Status is same like current status ' . $objOrder->getCurrentState() . '!=' . $id_order_state);
            }
        } else {
            // create new order
            $this->logInfoCrons('## validateOrder start');


            $this->logInfoCrons('## $cart->id = '.$cart->id);
            $this->logInfoCrons('## (int)$id_order_state = '.(int)$id_order_state);
            $this->logInfoCrons('## $amount_without_shipping = '.$amount_without_shipping);
            $this->logInfoCrons('## $payment_method = '.$payment_method);
            $this->logInfoCrons('## json_encode($message) = '.json_encode($message));
            $this->logInfoCrons('## $extra_vars = '.json_encode($extra_vars));
            $this->logInfoCrons('## $id_currency = '.$id_currency);
            $this->logInfoCrons('## $this->module->customer->secure_key = '.$this->module->customer->secure_key);


            $payment_module->validateOrder(
                $cart->id,
                (int)$id_order_state,
                $amount_without_shipping,
                $payment_method,
                json_encode($message),
                $extra_vars,
                $id_currency,
                false,
                $this->module->customer->secure_key,
                $shop
            );
            $this->logInfoCrons('## validateOrder finished');
        }

        // Update order object with real amounts paid (product price + shipping)
        $this->logInfoCrons('## Update order');
        $this->logInfoCrons('##id order = ' . (int)$payment_module->currentOrder );
        try{
            $order = new Order((int)$payment_module->currentOrder, $this->module->getContext()->language->id);
        }catch(Exception $e){
            $this->module->logs->errorLogs($e->getMessage());
        }


        // check and get tax informations
        $this->logInfoCrons('## execute checkTaxReverb');

        $this->checkTaxReverb($order, $orderReverb);

        $this->logInfoCrons('## end checkTaxReverb');

        if (in_array($orderReverb['status'], ReverbOrders::getReverbStatusesForInvoiceCreation())) {
            $this->updatePsOrderAmounts($order, $orderReverb);
        } else {
            $this->logInfoCrons('## Order amounts / invoices / payment not changed by Reverb status : ' . $orderReverb['status']);
        }

        // Update PS order details amounts
        $this->updatePsOrderDetailsAmounts($order, $orderReverb);

        // Update shipping amounts
        $this->logInfoCrons('## Update order shipping cost');
        $orderShippings = $order->getShipping();
        $this->logInfoCrons(var_export($orderShippings,true));
        foreach ($orderShippings as $orderShipping) {
            if ($orderShipping['id_order_carrier']) {
                $orderCarrier = new OrderCarrier($orderShipping['id_order_carrier']);
                $orderCarrier->shipping_cost_tax_excl = (float)$orderReverb['shipping']['amount'];
                $orderCarrier->shipping_cost_tax_incl = (float)$orderReverb['shipping']['amount'];
                $orderCarrier->update();
            }
        }
        Configuration::set('PS_TAX',1);
        $this->logInfoCrons('## Order ' . $order->reference . ' : ' . $orderReverb['order_number'] . ' is now synced');

        return $order->id;
    }

    public function updatePsOrderAmounts(Order $order, array $orderReverb)
    {
        $total = $this->result_tax['total'];
        // Update order amounts
        $this->logInfoCrons('## Update order amounts');
        $order->total_shipping = $this->result_tax['total_shipping_tax_incl'];
        $order->total_shipping_tax_excl = $this->result_tax['total_shipping_tax_excl'];
        $order->total_shipping_tax_incl = $this->result_tax['total_shipping_tax_incl'];
        if (isset($orderReverb['total'])) {
            $order->total_paid_real = (float)$orderReverb['total']['amount'];
        } else {
            $order->total_paid_real = 0;
        }
        $order->total_paid_tax_incl = (float)$this->result_tax['total_paid_tax_incl'];
        $order->total_paid_tax_excl = (float)$this->result_tax['total_paid_tax_excl'];
        $order->total_paid = (float)$this->result_tax['total_paid_tax_incl'];
        $amount_tax = isset($orderReverb['amount_tax']) ? (float)$orderReverb['amount_tax']['amount']:0;
        $order->total_products = (float)$orderReverb['amount_product_subtotal']['amount'];
        $order->total_products_wt = (float)$orderReverb['amount_product_subtotal']['amount']+(float)$this->result_tax['product_tax'];
        $order->carrier_tax_rate = $this->result_tax['carrier_tax_rate'];
        $order->update();

        // Update invoice amounts
        $this->logInfoCrons('## Update order invoices amounts');
        /** @var OrderInvoice[] $orderInvoices */
        $orderInvoices = $order->getInvoicesCollection();
        $id_invoice = 0;
        foreach ($orderInvoices as $orderInvoice) {
            $orderInvoice->total_shipping_tax_incl = (float)$this->result_tax['total_shipping_tax_incl'];
            $orderInvoice->total_shipping_tax_excl = (float)$this->result_tax['total_shipping_tax_excl'];
            $orderInvoice->total_paid_tax_incl = (float)$this->result_tax['total_paid_tax_incl'];
            $orderInvoice->total_paid_tax_excl = (float)$this->result_tax['total_paid_tax_excl'];
            $orderInvoice->total_products = (float)$orderReverb['amount_product_subtotal']['amount'];
            $orderInvoice->update();

            $id_invoice =  $orderInvoice->id;

            if((int)$this->result_tax['id_tax'] > 0){
                $sql = 'UPDATE `'._DB_PREFIX_.'order_invoice_tax` SET `id_tax`=\''.(int)$this->result_tax['id_tax'].'\', `amount`=\''.$this->result_tax['carrier_tax'].'\')
                    WHERE `type` = \'shipping\' AND `id_order_invoice`=\''.(int)$id_invoice.'\'';
                Db::getInstance()->execute($sql);
            }
        }

        if ($id_invoice > 0) {
            //update order with invoice id and valid order
            $order->invoice_number = $id_invoice;
            $order->valid = 1;
            $order->update();
        }

        // Update payments amount
        $this->logInfoCrons('## Update payments amount');
        /** @var OrderPayment[] $orderPayments */
        $orderPayments = $order->getOrderPayments();
        foreach ($orderPayments as $orderPayment) {
            $orderPayment->amount = (float)$total;
            $orderPayment->update();
        }
    }

    public function updatePsOrderDetailsAmounts(Order $order, array $orderReverb)
    {
        // Update order details amounts
        $this->logInfoCrons('## Update order details amounts');

        $orderDetails = $order->getOrderDetailList();
        foreach ($orderDetails as $orderDetail) {
            $orderDetailObject = new OrderDetail($orderDetail['id_order_detail']);
            $orderDetailObject->product_price = (float)$orderReverb['amount_product']['amount']+(float)$this->result_tax['product_unit_tax'];
            $orderDetailObject->total_price_tax_incl = (float)$orderReverb['amount_product_subtotal']['amount']+(float)$this->result_tax['product_tax'];
            $orderDetailObject->total_price_tax_excl = (float)$orderReverb['amount_product_subtotal']['amount'];
            $orderDetailObject->unit_price_tax_incl = (float)$orderReverb['amount_product']['amount']+(float)$this->result_tax['product_unit_tax'];
            $orderDetailObject->unit_price_tax_excl = (float)$orderReverb['amount_product']['amount'];
            $orderDetailObject->id_tax_rules_group = (int)$this->result_tax['id_tax_rules_group'];
            $orderDetailObject->tax_rate = $this->result_tax['rate'];
            $orderDetailObject->tax_name = 'reverb_tax';
            $orderDetailObject->total_shipping_price_tax_incl = (float)$this->result_tax['total_shipping_tax_incl'];
            $orderDetailObject->total_shipping_price_tax_excl = (float)$this->result_tax['total_shipping_tax_excl'];
            $orderDetailObject->update();

            Db::getInstance()->execute('DELETE FROM `'._DB_PREFIX_.'order_detail_tax` WHERE id_order_detail='.(int)$orderDetail['id_order_detail']);
            $sql = 'INSERT INTO `'._DB_PREFIX_.'order_detail_tax` (id_order_detail, id_tax, unit_amount, total_amount)
                VALUES (\''.$orderDetail['id_order_detail'].'\',\''.$this->result_tax['id_tax'].'\',\''.$this->result_tax['product_unit_tax'].'\',\''.$this->result_tax['product_tax'].'\')';
            Db::getInstance()->execute($sql);
        }
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

    /**
     * @param array $order
     * @return array|bool|null|object
     * @throws Exception
     */
    private function findProductByReverbOrder(array $order)
    {
        $this->logInfoCrons('## Try to find product = ' . $order['sku']);
        $product = $this->reverbSync->getProductByReference($order['sku']);
        $this->logInfoCrons('## product: ' . var_export($product, true));
        if (empty($product)) {
            throw new Exception('Product "' . $order['sku'] . '" not found on Prestashop !');
        } elseif (count($product) > 1) {
            throw new Exception('More than one product "' . $order['sku'] . '" found on Prestashop !');
        }

        $product = $product[0];
        $this->id_product = $product['id_product'];
        $this->id_product_attribute = $product['id_product_attribute'];
        return $product;
    }

    /**
     * @param $order
     * @return bool|Customer
     */
    private function findCustomerByReverbOrder($order)
    {

        // Find customer by address, name, lastname
        $this->logInfoCrons('### Try to found customer');

        $sql = new DbQuery();
        $this->logInfoCrons('### init dbsql');

        $sql->select('a.*')
            ->from('address', 'a')
            ->where('a.`firstname` = "' . $order['buyer_first_name'] . '"')
            ->where('a.`lastname` = "' . $order['buyer_last_name'] . '"');

        $this->logInfoCrons('### with firstname: ' . $order['buyer_first_name'] . ', lastname: ' . $order['buyer_last_name']);
        $this->logInfoCrons($sql);
        $res = Db::getInstance()->executeS($sql);
        $this->logInfoCrons(print_r($res,TRUE));

        $customerIds = array();

        // check if an address matches
        if (count($res)) {
            if (isset($order['shipping_address'])) {
                $country = Country::getByIso($order['shipping_address']['country_code']);

                foreach ($res as $row) {
                    if (isset($order['shipping_method'])
                        && $order['shipping_method'] == 'shipped'
                        && array_key_exists('shipping_address', $order)
                        && !empty($order['shipping_address'])
                    ) {
                        $address1 = $order['shipping_address']['street_address'];
                        $address2 = $order['shipping_address']['extended_address'];
                        $postcode = $order['shipping_address']['postal_code'];
                        $city = $order['shipping_address']['locality'];
                    } else {
                        $address1 = Configuration::get('PS_SHOP_ADDR1');
                        $address2 = Configuration::get('PS_SHOP_ADDR2');
                        $postcode = '';
                        $city = Configuration::get('PS_SHOP_CITY');
                    }

                    // checks address match
                    if ($row['address1'] == $address1 &&
                        $row['address2'] == $address2 &&
                        $row['postcode'] == $postcode &&
                        $row['city'] == $city &&
                        //$row['id_state'] == $state &&
                        $row['id_country'] == $country
                    ) {
                        // Firstname, lastname and address OK
                        $this->logInfoCrons('### Customer found by firstname, lastname and address : ' . json_encode($row));
                        $this->customerAddressId = $row['id_address'];
                        return new Customer($row['id_customer']);
                    }
                    // Stock customers ID
                    $customerIds[] = $row['id_customer'];
                }
            }
            // Firstname, lastname OK - address KO => Check email
            $resCustomer = $this->findCustomerByEmail($order['buyer_id']);
            if (!empty($resCustomer) && count($resCustomer)) {
                foreach ($resCustomer as $customerEmail) {
                    if (in_array($customerEmail['id_customer'], $customerIds)) {
                        $customer = new Customer($customerEmail['id_customer']);
                        // Create a new Address for Customer
                        $this->logInfoCrons('### Customer found by firstname, lastname and email : we create address');
                        $address = $this->initAddress($order, $customer);
                        $this->customerAddressId = $address->id;
                        return $customer;
                    }
                }
            }
        }
        $this->logInfoCrons('### No Customer found with firstname, lastname');

        return false;
    }

    /**
     * @param $reverbBuyerId
     * @return array|false|mysqli_result|null|PDOStatement|resource
     */
    private function findCustomerByEmail($reverbBuyerId)
    {
        $email = $reverbBuyerId . self::EMAIL_GENERIC_CUSTOMER;
        $sql = new DbQuery();
        $sql->select('c.*')
            ->from('customer', 'c')
            ->where('c.`email` = "' . $email . '"');

        $res = Db::getInstance()->executeS($sql);
        return $res;
    }

    /**
     *
     */
    private function checkTaxReverb(Order $order, array $orderReverb)
    {
        if (isset($orderReverb['total'])) {
            $total = $orderReverb['total']['amount'];
        } else {
            $total = $orderReverb['amount_product_subtotal']['amount']+$orderReverb['shipping']['amount'];
        }
        $productReverb  = $this->findProductByReverbOrder($orderReverb);
        $product        = new Product($this->id_product);
        $address        = new Address($order->id_address_delivery);
        $rate           = $product->getTaxesRate($address);
        $id_tax_rules_group = Product::getIdTaxRulesGroupByIdProduct($this->id_product);
        $sql = 'SELECT id_tax FROM '._DB_PREFIX_.'tax_rule WHERE id_country='. $address->id_country . ' AND id_tax_rules_group = '.$id_tax_rules_group;
        $id_tax = Db::getInstance()->getValue($sql);
        $shipping_tax = (float)Tools::ps_round(($orderReverb['shipping']['amount'] * (abs($rate) / 100)), _PS_PRICE_COMPUTE_PRECISION_);
        $amount_tax = (float)Tools::ps_round(($orderReverb['amount_product_subtotal']['amount'] * (abs($rate) / 100)), _PS_PRICE_COMPUTE_PRECISION_);
        $shipping_rate = 0;
        $rate_product = 0;

        $this->logInfoCrons('### amount_tax = '.$amount_tax);
        $this->logInfoCrons('### shipping_tax = '.$shipping_tax);
        $this->logInfoCrons('### rate = '.$rate);

        if ($orderReverb['amount_tax']['amount'] != ($amount_tax+$shipping_tax)) {
            $this->logInfoCrons('### amount_tax != amount+shipping:'.($amount_tax+$shipping_tax));
            $shipping_tax = 0;
            if ($orderReverb['amount_tax']['amount'] != $amount_tax) {
                $this->logInfoCrons('### amount_tax != amount:'.$amount_tax);
                $amount_tax = $orderReverb['amount_tax']['amount'];
                $rate_product = (abs($amount_tax) * 100) / $total;
            }
        } else {
            $shipping_rate = $rate;
            $rate_product = $rate;
        }

        $qty = isset($orderReverb['quantity']) ? (int) $orderReverb['quantity'] : 1;

        $total_shipping_tax_incl = str_replace(array(',', ' '), array('.', ''), $orderReverb['shipping']['amount']) + $shipping_tax;
        $total_shipping_tax_excl = str_replace(array(',', ' '), array('.', ''), $orderReverb['shipping']['amount']);
        $total_paid_tax_incl = $total;
        $total_paid_tax_excl = $total-($amount_tax + $shipping_tax);
        $product_unit_tax = $amount_tax/$qty;

        $this->result_tax = array (
            'id_tax' => $id_tax,
            'id_tax_rules_group' => $id_tax_rules_group,
            'rate' => $rate,
            'carrier_tax' => $shipping_tax,
            'carrier_tax_rate' => $shipping_rate,
            'product_tax' => $amount_tax,
            'product_unit_tax' => $product_unit_tax,
            'product_tax_rate' => $rate_product,
            'total_shipping_tax_incl' => $total_shipping_tax_incl,
            'total_shipping_tax_excl' => $total_shipping_tax_excl,
            'total_paid_tax_incl' => $total_paid_tax_incl,
            'total_paid_tax_excl' => $total_paid_tax_excl,
            'total' => $total,
        );
        $this->logInfoCrons('### Result Tax');
        $this->logInfoCrons(print_r($this->result_tax,true));
    }
 }
 