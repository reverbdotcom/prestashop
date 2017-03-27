<?php

use Reverb\Mapper\Models\AbstractModel;
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
     *  Load Total of products for sync view pagination
     *
     * @param $list_field
     */
    public function getListProductsWithStatusTotals($list_field){
        //=========================================
        //          SELECT CLAUSE
        //=========================================
        $sql = new DbQuery();
        $sql->select('count(*) as totals');

        $sql->from('product', 'p');
        $sql->leftJoin('reverb_sync', 'rs', 'rs.`id_product` = p.`id_product`');
        $sql->leftJoin('reverb_attributes', 'ra', 'ra.`id_product` = p.`id_product`');
        $sql->where('ra.`reverb_enabled` = 1');

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
    public function getListProductsWithStatus($list_field)
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
            'rs.url_reverb as url_reverb, ' .
            'rs.date as last_sync');

        $sql->from('product', 'p');
        $sql->leftJoin('reverb_sync', 'rs', 'rs.`id_product` = p.`id_product`');
        $sql->leftJoin('reverb_attributes', 'ra', 'ra.`id_product` = p.`id_product`');
        $sql->where('ra.`reverb_enabled` = 1');

        //=========================================
        //          WHERE CLAUSE
        //=========================================
        if (Tools::isSubmit('submitFilter')) {
            $sql = $this->processFilter($list_field, $sql);
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
    protected function processFilter($list_field, $sql)
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

    /**
     * @param $response
     */
    public function insertOrUpdateSyncStatus($idProduct,$status,$details,$reverb,$url) {
        $idSyncStatus = $this->getSyncStatusId($idProduct);

        if ($idSyncStatus) {
            $this->updateSyncStatus($idProduct,$status,$details,$reverb,$url);
        }else{
            $this->insertSyncStatus($idProduct,$status,$details,$reverb,$url);
        }

    }

    /**
     *  Update table Reverb Sync
     *
     * @param $idProduct
     * @param $status
     * @param $details
     * @param $reverb
     * @param $url
     */
    private function updateSyncStatus($idProduct,$status,$details,$reverb,$url) {
            $exec = Db::getInstance()->update(
            'reverb_sync',
            array('date' => (new \DateTime())->format('Y-m-d H:i:s'),
                  'status' => $status,
                  'details' => $details,
                  'reverb_ref' => $reverb,
                  'url_reverb' => $url),
            'id_product= ' . (int) $idProduct
        );

        $this->module->logs->infoLogs('Update sync ' . $idProduct . ' with status :' . $status);
    }

    /**
     *  Process an insert into table Reverb sync
     *
     * @param $idProduct
     * @param $status
     * @param $details
     * @param $reverb
     * @param $url
     */
    private function insertSyncStatus($idProduct,$status,$details,$reverb,$url) {

        $exec = Db::getInstance()->insert(
            'reverb_sync',
            array('date' => (new \DateTime())->format('Y-m-d H:i:s'),
                'status' => $status,
                'details' => $details,
                'reverb_ref' => $reverb,
                'url_reverb' => $url,
                'id_product' => (int)  $idProduct)
        );

        if ($exec) {
            $return = Db::getInstance()->Insert_ID();
        }

        $this->module->logs->infoLogs('Insert reverb sync ' . $idProduct . ' with status :' . $status);
    }

    /**
     * Return the mapping ID from PS category
     *
     * @param int idProduct
     * @return int|false
     */
    public static function getSyncStatusId($idProduct)
    {
        $sql = new DbQuery();
        $sql->select('rs.id_sync')
            ->from('reverb_sync', 'rs')
            ->where('rs.`id_product` = ' . $idProduct);

        $result = Db::getInstance()->getValue($sql);
        return $result;
    }

    /**
     * Return the sync
     * TODO Factoriser avec la méthode précédente
     * @param int idProduct
     * @return int|false
     */
    public static function getSyncStatus($idProduct)
    {
        $sql = new DbQuery();
        $sql->select('rs.*')
            ->from('reverb_sync', 'rs')
            ->where('rs.`id_product` = ' . $idProduct);

        $result = Db::getInstance()->executeS($sql);
        return $result;
    }
}
