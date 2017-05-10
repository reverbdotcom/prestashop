<?php
/**
 * Context for cron
 *
 * @author Johan Protin
 * @copyright Copyright (c) 2017 - Johan Protin
 * @license Apache License Version 2.0, January 2004
 * @package Reverb
 */

class ContextCron
{

    /**
     * @var
     */
    private $id_shop;

    /**
     * @var
     */
    private $id_shop_group;

    /**
     * @var
     */
    private $id_customer;

    /**
     * @var
     */
    private $id_cart;

    /**
     * @var
     */
    private $id_lang;

    /**
     * ContextCron constructor.
     */
    public function __construct($module)
    {
        $this->setIdLang($module->language_id);
        $this->setIdShop((int)$module->getContext()->shop->id);
        $this->setIdShopGroup((int)Shop::getContextShopGroupID());
    }

    /**
     * @return mixed
     */
    public function getIdShop()
    {
        return $this->id_shop;
    }

    /**
     * @param mixed $id_shop
     */
    public function setIdShop($id_shop)
    {
        $this->id_shop = $id_shop;
    }

    /**
     * @return mixed
     */
    public function getIdShopGroup()
    {
        return $this->id_shop_group;
    }

    /**
     * @param mixed $id_shop_group
     */
    public function setIdShopGroup($id_shop_group)
    {
        $this->id_shop_group = $id_shop_group;
    }

    /**
     * @return mixed
     */
    public function getIdCustomer()
    {
        return $this->id_customer;
    }

    /**
     * @param mixed $id_customer
     */
    public function setIdCustomer($id_customer)
    {
        $this->id_customer = $id_customer;
    }

    /**
     * @return mixed
     */
    public function getIdCart()
    {
        return $this->id_cart;
    }

    /**
     * @param mixed $id_cart
     */
    public function setIdCart($id_cart)
    {
        $this->id_cart = $id_cart;
    }

    /**
     * @return mixed
     */
    public function getIdLang()
    {
        return $this->id_lang;
    }

    /**
     * @param mixed $id_lang
     */
    public function setIdLang($id_lang)
    {
        $this->id_lang = $id_lang;
    }
}
