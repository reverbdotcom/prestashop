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
    const ORIGIN_MANUAL_SYNC_SINGLE = 'manual_sync_single';
    const ORIGIN_MANUAL_SYNC_MULTIPLE = 'manual_sync_multiple';
    const ORIGIN_PRODUCT_UPDATE = 'product_update';
    const ORIGIN_ORDER = 'order';
    const ORIGIN_CRON = 'cron';

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
    public function getListProductsWithStatusTotals($list_field)
    {
        //=========================================
        //          SELECT CLAUSE
        //=========================================
        $sql = new DbQuery();
        $sql->select('count(*) as totals');

        $sql->from('product', 'p');
        $sql->innerJoin('reverb_attributes', 'ra', 'ra.`id_product` = p.`id_product`');
        $sql->leftJoin('reverb_sync', 'rs', 'rs.`id_product` = p.`id_product`');
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
            'rs.reverb_id as reverb_id,' .
            'rs.details as details,' .
            'rs.reverb_slug as reverb_slug, ' .
            'rs.date as last_sync');

        $sql->from('product', 'p');
        $sql->innerJoin('reverb_attributes', 'ra', 'ra.`id_product` = p.`id_product`');
        $sql->leftJoin('reverb_sync', 'rs', 'rs.`id_product` = p.`id_product`');
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
     * Generate WHERE Clause with actives filters
     * @param $list_field
     * @param $sql
     * @return mixed
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
     * @param integer $idProduct
     * @param string $status
     * @param string $details
     * @param integer $reverbId
     * @param string $reverbSlug
     * @param string $origin
     * @return array
     */
    public function insertOrUpdateSyncStatus($idProduct, $status, $details, $reverbId, $reverbSlug, $origin) {
        $syncStatus = $this->getSyncStatus($idProduct);

        if (!empty($syncStatus)) {
            $this->updateSyncStatus(
                $idProduct,
                $status,
                $details,
                $reverbId,
                $reverbSlug,
                $this->getConcatOrigins($syncStatus, $origin)
            );
        } else {
            $this->insertSyncStatus($idProduct, $origin, $status, $details, $reverbId, $reverbSlug);
        }

        $this->insertSyncHistory($idProduct, $origin, $status, $details);

        return $this->getSyncStatus($idProduct);
    }

    /**
     *  Update table Reverb Sync
     *
     * @param integer $idProduct
     * @param string $status
     * @param string $details
     * @param integer $reverbId
     * @param string $reverbSlug
     * @param string $origin
     */
    private function updateSyncStatus($idProduct, $status, $details, $reverbId, $reverbSlug, $origin)
    {
        Db::getInstance()->update(
            'reverb_sync',
            array(
                'date' => (new \DateTime())->format('Y-m-d H:i:s'),
                'status' => $status,
                'details' => addslashes($details),
                'reverb_id' => $reverbId,
                'reverb_slug' => addslashes($reverbSlug),
                'origin' => $origin,
            ),
            'id_product= ' . (int) $idProduct
        );

        $this->module->logs->infoLogs('Update sync ' . $idProduct . ' with status :' . $status);
    }

    /**
     *  Process an insert into table Reverb sync
     *
     * @param integer $idProduct
     * @param string $origin
     * @param string $status
     * @param string $details
     * @param integer $reverbId
     * @param string $reverbSlug
     * @return integer
     */
    private function insertSyncStatus($idProduct, $origin, $status = null, $details = null, $reverbId = null, $reverbSlug = null)
    {
        $exec = Db::getInstance()->insert(
            'reverb_sync',
            array(
                'date' => (new \DateTime())->format('Y-m-d H:i:s'),
                'status' => $status,
                'details' => $details,
                'reverb_id' => $reverbId,
                'reverb_slug' => $reverbSlug,
                'id_product' => (int)  $idProduct,
                'origin' => $origin,
            )
        );

        if ($exec) {
            $return = Db::getInstance()->Insert_ID();
        }

        $this->module->logs->infoLogs('Insert reverb sync ' . $idProduct . ' with status ' . $status . ' and origin ' . $origin);
        return $return;
    }

    /**
     * Return the mapping ID from PS category
     *
     * @param int idProduct
     * @return int|false
     */
    public function getSyncStatusId($idProduct)
    {
        $syncStatus = $this->getSyncStatus($idProduct);
        return !empty($syncStatus) ? $syncStatus['id_sync'] : false;
    }

    /**
     * Return the sync
     * @param int idProduct
     * @return array
     */
    public function getSyncStatus($idProduct)
    {
        $sql = new DbQuery();
        $sql->select('rs.*')
            ->from('reverb_sync', 'rs')
            ->where('rs.`id_product` = ' . $idProduct);

        $result = Db::getInstance()->getRow($sql);
        return $result;
    }

    /**
     * @param $productId
     * @return array|bool|null|object
     */
    public function getProductWithStatus($productId)
    {
        $sql = new DbQuery();
        $sql->select('distinct(p.id_product),
                          p.*,
                          pl.*,
                          m.name as manufacturer_name,
                          ra.*,
                          rs.id_sync, rs.reverb_id, rs.reverb_slug')

            ->from('product', 'p')

            ->leftJoin('product_lang', 'pl', 'pl.`id_product` = p.`id_product`')
            ->leftJoin('manufacturer', 'm', 'm.`id_manufacturer` = p.`id_manufacturer`')
            ->leftJoin('reverb_attributes', 'ra', 'ra.`id_product` = p.`id_product` AND ra.`id_lang` = pl.`id_lang`')
            ->leftJoin('reverb_sync', 'rs', 'rs.`id_product` = p.`id_product`')

            ->where('p.`id_product` = ' . (int) $productId)
            ->where('pl.`id_lang` = '.(int)$this->module->language_id)
        ;

        $result = Db::getInstance()->getRow($sql);

        return $result;
    }

    /**
     * @param $productId
     * @return array|bool|null|object
     */
    public function getProductSync($productId)
    {
        $sql = new DbQuery();
        $sql->select('rs.*')
            ->from('reverb_sync', 'rs')
            ->where('rs.`id_product` = ' . (int) $productId)
        ;

        $result = Db::getInstance()->getRow($sql);

        return $result;
    }

    /**
     * @return array|bool|null|object
     */
    public function getProductsToSync()
    {
        $sql = new DbQuery();
        $sql->select('distinct(p.id_product),
                          p.*,
                          pl.*,
                          m.name as manufacturer_name,
                          ra.*,
                          rs.id_sync, rs.reverb_id, rs.reverb_slug')

            ->from('product', 'p')

            ->leftJoin('product_lang', 'pl', 'pl.`id_product` = p.`id_product`')
            ->leftJoin('manufacturer', 'm', 'm.`id_manufacturer` = p.`id_manufacturer`')
            ->leftJoin('reverb_attributes', 'ra', 'ra.`id_product` = p.`id_product` AND ra.`id_lang` = pl.`id_lang`')
            ->leftJoin('reverb_sync', 'rs', 'rs.`id_product` = p.`id_product`')

            ->where('rs.`status` = \'' . \Reverb\ReverbProduct::REVERB_CODE_TO_SYNC . '\'')
        ;

        $result = Db::getInstance()->executeS($sql);

        return $result;
    }

    /**
     * Set a sync status product to 'to_sync'
     *
     * @param integer $idProduct
     * @param string $origin
     */
    public function setProductToSync($idProduct, $origin) {
        $productSync = $this->getProductSync($idProduct);

        if (empty($productSync)) {
            $this->insertSyncStatus($idProduct, $origin, \Reverb\ReverbProduct::REVERB_CODE_TO_SYNC);
        } else {
            Db::getInstance()->update(
                'reverb_sync',
                array(
                    'status' => \Reverb\ReverbProduct::REVERB_CODE_TO_SYNC,
                    'origin' => $this->getConcatOrigins($productSync, $origin),
                ),
                'id_product= ' . (int) $idProduct
            );

        }

        $this->module->logs->infoLogs('Update sync status set ' . \Reverb\ReverbProduct::REVERB_CODE_TO_SYNC . ' for product ' . $idProduct . ' from : ' . $origin);
    }

    /**
     * @param array $productSync
     * @param string $origin
     * @return string
     */
    private function getConcatOrigins($productSync, $origin)
    {
        // if product already to sync, concat origins
        if ($productSync['status'] == \Reverb\ReverbProduct::REVERB_CODE_TO_SYNC) {
            return $productSync['origin'] . ',' . $origin;
        }

        // else replace by new origin
        return $origin;
    }

    /**
     *  Process an insert into table Reverb sync history
     *
     * @param integer $idProduct
     * @param string $origin
     * @param string $status
     * @param string $details
     * @param integer $reverbId
     * @param string $reverbSlug
     * @return void
     */
    private function insertSyncHistory($idProduct, $origin, $status, $details)
    {
        Db::getInstance()->insert(
            'reverb_sync_history',
            array(
                'id_product' => (int)  $idProduct,
                'date' => (new \DateTime())->format('Y-m-d H:i:s'),
                'status' => $status,
                'details' => addslashes($details),
                'origin' => $origin,
            )
        );

        $this->module->logs->infoLogs('Insert reverb sync history ' . $idProduct . ' with status ' . $status . ' and origin ' . $origin);
    }
}
