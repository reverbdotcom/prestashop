<?php
/**
 *  Manage sync status
 *
 * @author Johan Protin
 * @copyright Copyright (c) 2017 - Johan Protin
 * @license Apache License Version 2.0, January 2004
 * @package Reverb
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
            ->where('pl.`id_lang` = ' . (int)$this->module->language_id)
            ->groupBy('p.id_product, p.reference, rs.status, rs.reverb_id, rs.details, rs.reverb_slug, rs.date, pa.id_product_attribute, pa.upc');

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
        $sql->select(
            'IF (pa.id_product_attribute IS NULL, 
            CONCAT(p.id_product, \'-\', \'0\'), 
            CONCAT(pa.id_product, \'-\', pa.id_product_attribute)) as identifier,  ' .
            'p.id_product as id_product,' .
            'IF (pa.id_product_attribute IS NULL, p.reference, pa.reference) as reference,' .
            'IF (pa.upc IS NULL, p.upc, pa.upc) as upc,' .
            'IF (pa.id_product_attribute IS NULL, pl.name, CONCAT(pl.name, \' \', GROUP_CONCAT(CONCAT (agl.name, \' \', al.name) SEPARATOR \', \'))) as name,' .
            'rs.status as status,' .
            'rs.reverb_id as reverb_id,' .
            'rs.details as details,' .
            'rs.reverb_slug as reverb_slug, ' .
            'rs.date as date, ' .
            'pa.id_product_attribute as id_product_attribute'
        );

        $this->getListBaseSql($sql, $list_field);

        //=========================================
        //          ORDER CLAUSE
        //=========================================
        if (Tools::getValue('ps_product_reverbOrderby')) {
            $sql->orderBy(Tools::getValue('ps_product_reverbOrderby') . ' ' . Tools::getValue('ps_product_reverbOrderway'));
        }

        //=========================================
        //          PAGINATION
        //=========================================
        $page = (int)Tools::getValue('submitFilterps_product_reverb');
        if ($page > 1) {
            $sql->limit(Tools::getValue('selected_pagination'), $page * Tools::getValue('selected_pagination'));
        } else {
            $sql->limit(50);
        }

        $result = Db::getInstance()->executeS($sql);

        return $result;
    }

    /**
     *
     * Load list of products for sync view
     *
     * @param integer $id_product
     * @return array|false|mysqli_result|null|PDOStatement|resource
     */
    public function getListProductsWithStatusByProductId($id_product)
    {
        //=========================================
        //          SELECT CLAUSE
        //=========================================
        $sql = new DbQuery();
        $sql->select(
            'p.id_product as id_product,' .
            'pa.id_product_attribute as id_product_attribute'
        );

        $this->getListBaseSql($sql, array());

        $sql->where('p.id_product = ' . (int)$id_product);

        $result = Db::getInstance()->executeS($sql);

        return $result;
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
        $sql_filter = '';

        foreach ($values as $key => $params) {
            if (preg_match('/' . Reverb::LIST_ID . 'Filter_/', $key) && !empty($params)) {

                $fieldWithPrefix = preg_replace('/ps_product_reverbFilter_/', '', $key);
                $field = preg_replace('/pl!/', '', $fieldWithPrefix);
                $field = preg_replace('/rs!/', '', $field);
                $field = preg_replace('/p!/', '', $field);
                $filterKey = $fieldWithPrefix;
                if (isset($list_field[$field])){
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
     * @param integer $idProduct
     * @param integer|null $idProductAttribute
     * @param string $status
     * @param string $details
     * @param integer $reverbId
     * @param string $reverbSlug
     * @param string $origin
     * @param boolean $logHistory
     * @return array
     */
    public function insertOrUpdateSyncStatus(
        $idProduct,
        $idProductAttribute,
        $status,
        $details,
        $reverbId,
        $reverbSlug,
        $origin,
        $logHistory = false
    ) {
        $this->module->logs->infoLogs('insertOrUpdateSyncStatus');
        $this->module->logs->infoLogs(' - $idProduct = ' . $idProduct);
        $this->module->logs->infoLogs(' - $idProductAttribute = ' . var_export($idProductAttribute, true));
        $this->module->logs->infoLogs(' - $status = ' . $status);
        $this->module->logs->infoLogs(' - $details = ' . $details);
        $this->module->logs->infoLogs(' - $reverbId = ' . $reverbId);
        $this->module->logs->infoLogs(' - $reverbSlug = ' . $reverbSlug);
        $this->module->logs->infoLogs(' - $origin = ' . $origin);
        $syncStatus = $this->getSyncStatus($idProduct, $idProductAttribute);

        $this->module->logs->infoLogs(' - $syncStatus = ' . var_export($syncStatus, true));

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
            $this->insertSyncStatus(
                $idProduct,
                $idProductAttribute,
                $origin,
                $status,
                $details,
                $reverbId,
                $reverbSlug
            );
        }

        $this->module->logs->infoLogs(' - insert or update done');

        if ($logHistory) {
            $this->module->logs->infoLogs(' - Now, insert sync history');
            $this->insertSyncHistory($idProduct, $idProductAttribute, $origin, $status, $details);
        }

        return $this->getSyncStatus($idProduct, $idProductAttribute);
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
    private function updateSyncStatus(
        $idProduct,
        $idProductAttribute,
        $status,
        $details,
        $reverbId,
        $reverbSlug,
        $origin
    ) {
        $this->module->logs->infoLogs('Update sync ' . $idProduct . ' with status :' . $status);
        $this->module->logs->infoLogs(' # $idProductAttribute = ' . var_export($idProductAttribute, true));
        $this->module->logs->infoLogs(' # $details = ' . var_export($details, true));
        $this->module->logs->infoLogs(' # $reverbId = ' . var_export($reverbId, true));
        $this->module->logs->infoLogs(' # $reverbSlug = ' . var_export($reverbSlug, true));
        $this->module->logs->infoLogs(' # $origin = ' . var_export($origin, true));

        Db::getInstance()->update(
            'reverb_sync',
            array(
                'date' => (new \DateTime())->format('Y-m-d H:i:s'),
                'status' => $status,
                'details' => pSQL($details),
                'reverb_id' => $reverbId,
                'reverb_slug' => pSQL($reverbSlug),
                'origin' => pSQL($origin),
            ),
            'id_product= ' . (int)$idProduct . (!empty($idProductAttribute) ? ' AND id_product_attribute = ' . $idProductAttribute : '')
        );
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
     * @return void
     */
    private function insertSyncStatus(
        $idProduct,
        $idProductAttribute,
        $origin,
        $status = null,
        $details = null,
        $reverbId = null,
        $reverbSlug = null
    ) {
        $this->module->logs->infoLogs('Insert reverb sync for product ' . $idProduct . ' (attribute ' . $idProductAttribute . ') with status ' . $status . ' and origin ' . $origin);
        $this->module->logs->infoLogs(' # $idProductAttribute = ' . var_export($idProductAttribute, true));
        $this->module->logs->infoLogs(' # $details = ' . var_export($details, true));
        $this->module->logs->infoLogs(' # $reverbId = ' . var_export($reverbId, true));
        $this->module->logs->infoLogs(' # $reverbSlug = ' . var_export($reverbSlug, true));

        $params = array(
            'date' => (new \DateTime())->format('Y-m-d H:i:s'),
            'status' => pSQL($status),
            'details' => pSQL($details),
            'reverb_id' => $reverbId,
            'reverb_slug' => $reverbSlug,
            'id_product' => (int)$idProduct,
            'origin' => pSQL($origin),
        );

        if ($idProductAttribute) {
            $params['id_product_attribute'] = $idProductAttribute;
        }

        Db::getInstance()->insert(
            'reverb_sync',
            $params
        );

        $this->module->logs->infoLogs(' reverb_sync inserted !');
    }

    /**
     * Return the mapping ID from PS category
     *
     * @param int $idProduct
     * @param int|null $idProductAttribute
     * @return int|false
     */
    public function getSyncStatusId($idProduct, $idProductAttribute)
    {
        $syncStatus = $this->getSyncStatus($idProduct, $idProductAttribute);
        return !empty($syncStatus) ? $syncStatus['id_sync'] : false;
    }

    /**
     * Return the sync
     * @param int $idProduct
     * @param int|null $idProductAttribute
     * @return array
     */
    public function getSyncStatus($idProduct, $idProductAttribute)
    {
        $sql = new DbQuery();
        $sql->select('rs.*')
            ->from('reverb_sync', 'rs')
            ->where('rs.`id_product` = ' . $idProduct);

        if ($idProductAttribute) {
            $sql->where('rs.`id_product_attribute` = ' . $idProductAttribute);
        } else {
            $sql->where('rs.`id_product_attribute` IS NULL');
        }

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
        $sql->select(
            'distinct(p.id_product), ' .
            'p.*, pl.*, m.name as manufacturer_name, ra.*, ' .
            's.quantity AS quantity_stock, ' .
            'rs.id_sync, rs.reverb_id, rs.reverb_slug, ' .
            'pa.id_product_attribute, agl.name as attribute_group_name, al.name as attribute_name, ' .
            'IF (pa.id_product_attribute IS NULL, p.reference, pa.reference) as reference,' .
            'IF (pa.upc IS NULL, p.upc, pa.upc) as upc,' .
            'IF (pa.id_product_attribute IS NULL, pl.name, CONCAT(pl.name, \' \', GROUP_CONCAT(CONCAT (agl.name, \' \', al.name) SEPARATOR \', \'))) as name'
        );

        $this->getListBaseSql($sql);

        $sql->leftJoin('manufacturer', 'm', 'm.`id_manufacturer` = p.`id_manufacturer`')
            ->where('p.`id_product` = ' . (int)$productId);

        $sql->leftJoin('stock_available', 's', 's.`id_product` = p.`id_product`');

        if ($productAttributeId) {
            $sql->where('pa.`id_product_attribute` = ' . (int)$productAttributeId);
            $sql->where('s.`id_product_attribute` = ' . (int)$productAttributeId);
        } else {
            $sql->where('pa.`id_product_attribute` IS NULL');
            $sql->where('s.`id_product_attribute` = 0');
        }

        $result = Db::getInstance()->getRow($sql);

        return $result;
    }

    /**
     * @param string $reference
     * @return array|bool|null|object
     */
    public function getProductByReference($reference)
    {
        $sql = new DbQuery();
        $sql->select(
            'distinct(p.id_product), ' .
            'pa.id_product_attribute, ' .
            'IF (pa.id_product_attribute IS NULL, p.reference, pa.reference) as reference, ' .
            'ra.`reverb_enabled`'
        )
            ->from('product', 'p')
            ->leftJoin('product_attribute', 'pa', 'pa.`id_product` = p.`id_product`')
            ->leftJoin('reverb_attributes', 'ra', 'ra.`id_product` = p.`id_product`')
            ->where('p.`reference` = "' . $reference . '" OR pa.reference = "' . $reference . '"');

        //$result = Db::getInstance()->getRow($sql);
        $result = Db::getInstance()->executeS($sql);

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
            ->where('rs.`id_product` = ' . (int)$productId);

        if ($productAttributeId) {
            $sql->where('rs.`id_product_attribute` = ' . (int)$productAttributeId);
        } else {
            $sql->where('rs.`id_product_attribute` IS NULL');
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
        $sql->select(
            'distinct(p.id_product), ' .
            'p.*, pl.*, m.name as manufacturer_name, ra.*, ' .
            's.quantity AS quantity_stock, ' .
            'rs.id_sync, rs.reverb_id, rs.reverb_slug, ' .
            'pa.id_product_attribute, agl.name as attribute_group_name, al.name as attribute_name, ' .
            'IF (pa.id_product_attribute IS NULL, p.reference, pa.reference) as reference,' .
            'IF (pa.upc IS NULL, p.upc, pa.upc) as upc,' .
            'IF (pa.id_product_attribute IS NULL, pl.name, CONCAT(pl.name, \' \', GROUP_CONCAT(CONCAT (agl.name, \' \', al.name) SEPARATOR \', \'))) as name'
        );

        $this->getListBaseSql($sql);

        $sql->leftJoin('manufacturer', 'm', 'm.`id_manufacturer` = p.`id_manufacturer`')
            ->leftJoin('stock_available', 's', 's.`id_product` = p.`id_product`')
            ->where('rs.`status` = \'' . \Reverb\ReverbProduct::REVERB_CODE_TO_SYNC . '\'')
            ->where('(pa.`id_product_attribute` IS NULL AND s.`id_product_attribute` = 0) OR pa.`id_product_attribute` = s.`id_product_attribute`');

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
    public function setProductToSync($id_product, $id_product_attribute, $origin)
    {
        $this->module->logs->infoLogs(
            'Set sync status : ' . \Reverb\ReverbProduct::REVERB_CODE_TO_SYNC . ' for product ' . $id_product .
             ' (attribute ' . var_export($id_product_attribute, true) . ') from : ' . $origin
        );

        $productSync = $this->getProductSync($id_product, $id_product_attribute);

        if (empty($productSync)) {
            $this->insertSyncStatus(
                $id_product,
                $id_product_attribute,
                $origin,
                \Reverb\ReverbProduct::REVERB_CODE_TO_SYNC
            );
        } else {
            $this->module->logs->infoLogs('UPDATE sync status');
            Db::getInstance()->update(
                'reverb_sync',
                array(
                    'status' => \Reverb\ReverbProduct::REVERB_CODE_TO_SYNC,
                    'origin' => $this->getConcatOrigins($productSync, $origin),
                ),
                'id_product = ' . (int)$id_product .
                ' AND id_product_attribute ' . ($id_product_attribute ? ' = ' . $id_product_attribute : 'IS NULL')
            );
        }
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
     * @param integer|null $idProductAttribute
     * @param string $origin
     * @param string $status
     * @param string $details
     * @return void
     */
    private function insertSyncHistory($idProduct, $idProductAttribute, $origin, $status, $details)
    {
        $this->module->logs->infoLogs('insertSyncHistory');
        $this->module->logs->infoLogs(' - $idProduct = ' . $idProduct);
        $this->module->logs->infoLogs(' - $idProductAttribute = ' . var_export($idProductAttribute, true));
        $this->module->logs->infoLogs(' - $status = ' . $status);
        $this->module->logs->infoLogs(' - $details = ' . $details);
        $this->module->logs->infoLogs(' - $origin = ' . $origin);

        $params = array(
            'id_product' => (int)$idProduct,
            'date' => (new \DateTime())->format('Y-m-d H:i:s'),
            'status' => $status,
            'details' => pSQL($details),
            'origin' => pSQL($origin),
        );

        if ($idProductAttribute) {
            $params['id_product_attribute'] = $idProductAttribute;
        }

        if (!empty($params['details']) && !empty($params['status']) && !empty($params['origin'])) {
            Db::getInstance()->insert(
                'reverb_sync_history',
                $params
            );
            $this->module->logs->infoLogs('Insert reverb sync history done !');
        } else {
            $this->module->logs->infoLogs('Insert reverb sync history skip because of null value(s) !');
        }
    }
}
