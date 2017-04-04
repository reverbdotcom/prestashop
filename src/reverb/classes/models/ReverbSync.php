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
     * Construct base of query
     * @param DbQuery $sql
     * @param array $list_field
     */
    private function getListBaseSql(DbQuery $sql, $list_field = array())
    {
        $sql->from('reverb_attributes', 'ra')
            ->innerJoin('product', 'p', 'ra.`id_product` = p.`id_product`')
            ->innerJoin('product_lang', 'pl', 'pl.`id_product` = p.`id_product`')
            ->leftJoin('product_attribute', 'pa', 'pa.`id_product` = p.`id_product`')
            ->leftJoin('reverb_sync', 'rs', 'rs.`id_product` = p.`id_product` AND (pa.`id_product_attribute` IS NULL OR rs.`id_product_attribute` = pa.`id_product_attribute`)')
            ->leftJoin('product_attribute_combination', 'pac', 'pac.`id_product_attribute` = pa.`id_product_attribute`')
            ->leftJoin('attribute', 'a', 'a.`id_attribute` = pac.`id_attribute`')
            ->leftJoin('attribute_group', 'ag', 'ag.`id_attribute_group` = a.`id_attribute_group`')
            ->leftJoin('attribute_lang', 'al', 'al.`id_attribute` = a.`id_attribute` AND al.`id_lang` = ' . $this->module->language_id)
            ->leftJoin('attribute_group_lang', 'agl', 'agl.`id_attribute_group` = ag.`id_attribute_group` AND agl.`id_lang` = ' . $this->module->language_id)

            ->where('ra.`reverb_enabled` = 1')
            ->where('pl.`id_lang` = '.(int) $this->module->language_id)
            ->groupBy('p.id_product, p.reference, rs.status, rs.reverb_id, rs.details, rs.reverb_slug, rs.date, pa.id_product_attribute');

        //=========================================
        //          WHERE CLAUSE
        //=========================================
        if (Tools::isSubmit('submitFilter')) {
            $this->processFilter($list_field, $sql);
        }
    }

    /**
     *  Load Total of products for sync view pagination
     *
     * @param $list_field
     * @return array|null
     */
    public function getListProductsWithStatusTotals($list_field)
    {
        //=========================================
        //          SELECT CLAUSE
        //=========================================
        $sql = new DbQuery();
        $sql->select('count(*) as totals');

        $this->getListBaseSql($sql, $list_field);

        $result = Db::getInstance()->getRow($sql);
      
        return $result['totals'];
    }

    /**
     *
     * Load list of products for sync view
     *
     * @param $list_field
     * @return array|false|mysqli_result|null|PDOStatement|resource
     */
    public function getListProductsWithStatus($list_field)
    {
        //=========================================
        //          SELECT CLAUSE
        //=========================================
        $sql = new DbQuery();
        $sql->select('IF (pa.id_product_attribute IS NULL, CONCAT(p.id_product, \'-\', \'0\'), CONCAT(pa.id_product, \'-\', pa.id_product_attribute)) as identifier,  ' .
            'p.id_product as id_product,' .
            'IF (pa.id_product_attribute IS NULL, p.reference, CONCAT(p.reference, \'-\', pa.id_product_attribute)) as reference,' .
            'IF (pa.id_product_attribute IS NULL, pl.name, CONCAT(pl.name, \' \', GROUP_CONCAT(CONCAT (agl.name, \' \', al.name) SEPARATOR \', \'))) as name,' .
            'rs.status as status,' .
            'rs.reverb_id as reverb_id,' .
            'rs.details as details,' .
            'rs.reverb_slug as reverb_slug, ' .
            'rs.date as last_sync, ' .
            'pa.id_product_attribute as id_product_attribute'
        );

        $this->getListBaseSql($sql, $list_field);

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
     * @return void
     */
    protected function processFilter($list_field, DbQuery $sql)
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
    public function insertOrUpdateSyncStatus($idProduct,$idProductAttribute, $status, $details, $reverbId, $reverbSlug, $origin) {
        $syncStatus = $this->getSyncStatus($idProduct);

        if (!empty($syncStatus)) {
            $this->updateSyncStatus(
                $idProduct,
                $idProductAttribute,
                $status,
                $details,
                $reverbId,
                $reverbSlug,
                $this->getConcatOrigins($syncStatus, $origin)
            );
        } else {
            $this->insertSyncStatus($idProduct,$idProductAttribute, $origin, $status, $details, $reverbId, $reverbSlug);
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
    private function updateSyncStatus($idProduct, $idProductAttribute, $status, $details, $reverbId, $reverbSlug, $origin)
    {
        Db::getInstance()->update(
            'reverb_sync',
            array(
                'date' => (new \DateTime())->format('Y-m-d H:i:s'),
                'status' => $status,
                'details' => pSQL($details),
                'reverb_id' => $reverbId,
                'reverb_slug' => pSQL($reverbSlug),
                'origin' => $origin,
            ),
            'id_product= ' . (int) $idProduct . (!empty($idProductAttribute) ? ' AND id_product_attribute = ' .  $idProductAttribute : '')
        );

        $this->module->logs->infoLogs('Update sync ' . $idProduct . ' with status :' . $status);
    }

    /**
     *  Process an insert into table Reverb sync
     *
     * @param integer $idProduct
     * @param integer $idProductAttribute
     * @param string $origin
     * @param string $status
     * @param string $details
     * @param integer $reverbId
     * @param string $reverbSlug
     * @return integer
     */
    private function insertSyncStatus($idProduct, $idProductAttribute, $origin, $status = null, $details = null, $reverbId = null, $reverbSlug = null)
    {
        $params = array(
            'date' => (new \DateTime())->format('Y-m-d H:i:s'),
            'status' => $status,
            'details' => $details,
            'reverb_id' => $reverbId,
            'reverb_slug' => $reverbSlug,
            'id_product' => (int)  $idProduct,
            'origin' => $origin,
        );

        if ($idProductAttribute) {
            $params['id_product_attribute'] = $idProductAttribute;
        }

        $exec = Db::getInstance()->insert(
            'reverb_sync',$params
        );

        if ($exec) {
            $return = Db::getInstance()->Insert_ID();
        }

        $this->module->logs->infoLogs('Insert reverb sync for product ' . $idProduct . ' (attribute ' . $idProductAttribute . ') with status ' . $status . ' and origin ' . $origin);
        return $return;
    }

    /**
     * Return the mapping ID from PS category
     *
     * @param int $idProduct
     * @return int|false
     */
    public function getSyncStatusId($idProduct)
    {
        $syncStatus = $this->getSyncStatus($idProduct);
        return !empty($syncStatus) ? $syncStatus['id_sync'] : false;
    }

    /**
     * Return the sync
     * @param int $idProduct
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
     * @param integer $productId
     * @param integer $productAttributeId
     * @return array|bool|null|object
     */
    public function getProductWithStatus($productId, $productAttributeId)
    {
        $sql = new DbQuery();
        $sql->select('distinct(p.id_product), ' .
            'p.*, pl.*, m.name as manufacturer_name, ra.*, ' .
            'rs.id_sync, rs.reverb_id, rs.reverb_slug, ' .
            'pa.id_product_attribute, agl.name as attribute_group_name, al.name as attribute_name, ' .
            'IF (pa.id_product_attribute IS NULL, p.reference, CONCAT(p.reference, \'-\', pa.id_product_attribute)) as reference,' .
            'IF (pa.id_product_attribute IS NULL, pl.name, CONCAT(pl.name, \' \', GROUP_CONCAT(CONCAT (agl.name, \' \', al.name) SEPARATOR \', \'))) as name'
        );

        $this->getListBaseSql($sql);

        $sql->leftJoin('manufacturer', 'm', 'm.`id_manufacturer` = p.`id_manufacturer`')
            ->where('p.`id_product` = ' . (int) $productId)
        ;

        if ($productAttributeId == 0) {
            $sql->where('pa.`id_product_attribute` IS NULL');
        } else {
            $sql->where('pa.`id_product_attribute` = ' . (int) $productAttributeId);
        }

        $result = Db::getInstance()->getRow($sql);

        return $result;
    }

    /**
     * @param $productId
     * @param $productAttributeId
     * @return array|bool|null|object
     */
    public function getProductSync($productId, $productAttributeId)
    {
        $sql = new DbQuery();
        $sql->select('rs.*')
            ->from('reverb_sync', 'rs')
            ->where('rs.`id_product` = ' . (int) $productId)
        ;

        if ($productAttributeId == 0) {
            $sql->where('rs.`id_product_attribute` IS NULL');
        } else {
            $sql->where('rs.`id_product_attribute` = ' . (int) $productAttributeId);
        }

        $result = Db::getInstance()->getRow($sql);

        return $result;
    }

    /**
     * @return array|bool|null|object
     */
    public function getProductsToSync()
    {
        $sql = new DbQuery();
        $sql->select('distinct(p.id_product), ' .
            'p.*, pl.*, m.name as manufacturer_name, ra.*, ' .
            'rs.id_sync, rs.reverb_id, rs.reverb_slug, ' .
            'pa.id_product_attribute, agl.name as attribute_group_name, al.name as attribute_name, ' .
            'IF (pa.id_product_attribute IS NULL, p.reference, CONCAT(p.reference, \'-\', pa.id_product_attribute)) as reference,' .
            'IF (pa.id_product_attribute IS NULL, pl.name, CONCAT(pl.name, \' \', GROUP_CONCAT(CONCAT (agl.name, \' \', al.name) SEPARATOR \', \'))) as name'
        );

        $this->getListBaseSql($sql);

        $sql->leftJoin('manufacturer', 'm', 'm.`id_manufacturer` = p.`id_manufacturer`')
            ->where('rs.`status` = \'' . \Reverb\ReverbProduct::REVERB_CODE_TO_SYNC . '\'')
        ;

        $result = Db::getInstance()->executeS($sql);

        return $result;
    }

    /**
     * Set a sync status product to 'to_sync'
     *
     * @param integer $id_product
     * @param integer $id_product_attribute
     * @param string $origin
     */
    public function setProductToSync($id_product, $id_product_attribute, $origin) {
        $productSync = $this->getProductSync($id_product, $id_product_attribute);

        if (empty($productSync)) {
            $this->insertSyncStatus($id_product, $id_product_attribute, $origin, \Reverb\ReverbProduct::REVERB_CODE_TO_SYNC);
        } else {
            Db::getInstance()->update(
                'reverb_sync',
                array(
                    'status' => \Reverb\ReverbProduct::REVERB_CODE_TO_SYNC,
                    'origin' => $this->getConcatOrigins($productSync, $origin),
                ),
                'id_product = ' . (int) $id_product .
                ' AND id_product_attribute ' . ($id_product_attribute == 0 ? 'IS NULL' : ' = ' . $id_product_attribute)
            );

        }

        $this->module->logs->infoLogs('Update sync status set ' . \Reverb\ReverbProduct::REVERB_CODE_TO_SYNC . ' for product ' . $id_product . ' (attribute ' . $id_product_attribute . ') from : ' . $origin);
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
                'details' => pSQL($details),
                'origin' => $origin,
            )
        );

        $this->module->logs->infoLogs('Insert reverb sync history ' . $idProduct . ' with status ' . $status . ' and origin ' . $origin);
    }
}
