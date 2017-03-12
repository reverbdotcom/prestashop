<?php

/**
 * Model Reverb Sync
 *
 * @package Reverb
 * @author Johan Protin
 * @copyright Copyright (c) 2017 - Johan Protin
 * @license
 */
class ReverbSync
{

    /**
     * @param $list_field
     */
    public static function getListProductsWithStatusTotals($list_field){
        //=========================================
        //          SELECT CLAUSE
        //=========================================
        $sql = new DbQuery();
        $sql->select('count(*) as totals');

        $sql->from('product', 'p');
        $sql->leftJoin('reverb_sync', 'rs', 'rs.`id_product` = p.`id_product`');
        $sql->where('p.`reverb_enabled` = 1');

        //=========================================
        //          WHERE CLAUSE
        //=========================================
        if (Tools::isSubmit('submitFilter')) {
            $sql = ReverbSync::processFilter($list_field, $sql);
        }

        $result = Db::getInstance()->executeS($sql);

        return $result[0]['totals'];
    }

    /**
     *
     * Load list of products for sync view
     *
     * @return array|false|mysqli_result|null|PDOStatement|resource
     */
    public static function getListProductsWithStatus($list_field)
    {
        //=========================================
        //          SELECT CLAUSE
        //=========================================
        $sql = new DbQuery();
        $sql->select('p.id_product as id_product,  ' .
            'p.reference as reference,' .
            'rs.status as status,' .
            'rs.id_sync as id_sync,' .
            'rs.details as details,' .
            'rs.url_reverb as url_reverb',
            'rs.date as date');

        $sql->from('product', 'p');
        $sql->leftJoin('reverb_sync', 'rs', 'rs.`id_product` = p.`id_product`');
        $sql->where('p.`reverb_enabled` = 1');

        //=========================================
        //          WHERE CLAUSE
        //=========================================
        if (Tools::isSubmit('submitFilter')) {
            $sql = ReverbSync::processFilter($list_field, $sql);
        }

        //=========================================
        //          ORDER CLAUSE
        //=========================================
        if (Tools::getValue('ps_productOrderby')) {
            $sql->orderBy(Tools::getValue('ps_productOrderby') . ' ' . Tools::getValue('ps_productOrderway'));
        }

        //=========================================
        //          PAGINATION
        //=========================================
        $page = (int)Tools::getValue('submitFilterps_product');
        if ($page > 1){
            $sql->limit(Tools::getValue('selected_pagination'), $page * Tools::getValue('selected_pagination'));
        }else{
            $sql->limit(50);
        }

        $result = Db::getInstance()->executeS($sql);

        return $result;
    }

    /**
     *  Generate WHERE Clause with actives filters
     */
    protected static function processFilter($list_field, $sql)
    {
        $values = Tools::getAllValues();

        foreach ($values as $key => $params) {
            if (preg_match('/' . Reverb::LIST_ID . 'Filter_/', $key) && !empty($params)) {
                $fieldWithPrefix = preg_replace('/ps_productFilter_/', '', $key);
                $field = preg_replace('/p_/', '', $fieldWithPrefix);
                $filterKey = $field;
                switch ($list_field[$field]['type']) {
                    case 'text':
                        if (isset($list_field[$field]['filter_key'])) {
                            $filterKey = $list_field[$field]['filter_key'];
                        }
                        $sql->where($filterKey . ' like "%' . $params . '%"');
                        break;
                    case 'int':
                        if (isset($list_field[$field]['filter_key'])) {
                            $filterKey = $list_field[$field]['filter_key'];
                        }
                        $sql->where($filterKey . ' = ' . $params);
                        break;
                }
            };
        }
        return $sql;
    }
}
