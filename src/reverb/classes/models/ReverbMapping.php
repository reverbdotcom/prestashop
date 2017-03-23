<?php

/**
 * Model Reverb Sync
 *
 * @package Reverb
 * @author Johan Protin
 * @copyright Copyright (c) 2017 - Johan Protin
 * @license
 */
class ReverbMapping
{
    const DEFAULT_PAGINATION = 20;
    protected $module = false;

    public function __construct(Module $module_instance)
    {
        $this->module = $module_instance;
    }

    /**
     * Return formatted PS categories for mapping select form
     *
     * @param int $languageId
     * @return array
     */
    public static function getFormattedPsCategories($languageId)
    {
        $sql = new DbQuery();
        $sql->select('c.id_category as id_category,  ' .
            'c.id_parent as id_parent,' .
            'cl.name as name,' .
            'rm.reverb_code as reverb_code,' .
            'rm.id_mapping as id_mapping')

            ->from('category', 'c')
            ->leftJoin('category_lang', 'cl', 'cl.`id_category` = c.`id_category`')
            ->leftJoin('reverb_mapping', 'rm', 'rm.`id_category` = c.`id_category`')

            ->where('c.`id_parent` != 0 AND `id_lang` = '.(int) $languageId)

            ->orderBy('c.`id_parent` ASC, cl.`name` ASC')
        ;

        //=========================================
        //          PAGINATION
        //=========================================
        $page = (int)Tools::getValue('submitFilterps_mapping_category');

        if ($page > 1) {
            $sql->limit(Tools::getValue('selected_pagination'), ($page-1) * Tools::getValue('selected_pagination'));
        }else{
            $sql->limit(self::DEFAULT_PAGINATION);
        }

        $result = Db::getInstance()->executeS($sql);

        $indexedCategories = $categories = array();
        foreach ($result as $row) {
            $indexedCategories[$row['id_category']] = $row;
        }

        foreach ($indexedCategories as $categoryId => $category) {
            $categories[$categoryId] = array(
                'ps_category_id' => $categoryId,
                'ps_category' => self::getPsFullName($category, $indexedCategories),
                'reverb_code' => $category['reverb_code'],
                'id_mapping' => $category['id_mapping'],
            );
        }

        return $categories;
    }

    /**
     * Return number of PS categories for mapping
     *
     * @param int $languageId
     * @return int
     */
    public static function countPsCategories($languageId)
    {
        $sql = new DbQuery();
        $sql->select('count(*) as totals')

            ->from('category', 'c')
            ->leftJoin('category_lang', 'cl', 'cl.`id_category` = c.`id_category`')
            ->leftJoin('reverb_mapping', 'rm', 'rm.`id_category` = c.`id_category`')

            ->where('c.`id_parent` != 0 AND `id_lang` = '.(int) $languageId)
        ;

        $result = Db::getInstance()->executeS($sql);

        return $result[0]['totals'];
    }

    /**
     * Return the full name category included parent name
     *
     * @param array $category
     * @param array $indexedCategories
     * @return string
     */
    private static function getPsFullName($category, $indexedCategories) {
        if (!isset($category['id_parent']) || !isset($indexedCategories[$category['id_parent']])) {
            return $category['name'];
        }
        return self::getPsFullName($indexedCategories[$category['id_parent']], $indexedCategories) . ' / ' . $category['name'];
    }

    /**
     * Create or update a mapping on DB
     *
     * @param int $psCategoryId
     * @param string $reverbCode
     * @param int $mappingId
     * @return bool
     */
    public function createOrUpdateMapping($psCategoryId, $reverbCode, $mappingId)
    {
        if (empty($mappingId)) {
            return $this->insertMapping($psCategoryId, $reverbCode);
        }
        return $this->updateMapping($mappingId, $psCategoryId, $reverbCode);
    }

    /**
     * Return the mapping ID from PS category
     *
     * @param int $psCategoryId
     * @return int|false
     */
    private function getMappingId($psCategoryId)
    {
        $sql = new DbQuery();
        $sql->select('rm.id_mapping')
            ->from('reverb_mapping', 'rm')
            ->where('rm.`id_category` = ' . $psCategoryId)
            ;
        $result = Db::getInstance()->getValue($sql);
        return $result;
    }

    /**
     * Update a mapping
     *
     * @param $mappingId
     * @param $reverbCode
     * @return bool
     */
    private function updateMapping($mappingId, $psCategoryId, $reverbCode)
    {
        $return = false;

        $exec = Db::getInstance()->update(
            'reverb_mapping',
            array('reverb_code' => $reverbCode),
            'id_mapping = '. (int) $mappingId . ' AND id_category = ' . (int) $psCategoryId
        );
        if ($exec) {
            $return = $mappingId;
        }

        $this->module->logs->infoLogs('Update mapping ' . $mappingId . ' for category ' . $psCategoryId . ' => ' . $reverbCode . ' : ' . var_export($return, true));

        return $return;
    }

    /**
     * Insert new mapping
     *
     * @param $psCategoryId
     * @param $reverbCode
     * @return bool
     */
    private function insertMapping($psCategoryId, $reverbCode)
    {
        $return = false;

        $exec = Db::getInstance()->insert(
            'reverb_mapping',
            array(
                'id_category' => $psCategoryId,
                'reverb_code' => $reverbCode,
        ));
        if ($exec) {
            $return = Db::getInstance()->Insert_ID();
        }
        $this->module->logs->infoLogs('Insert mapping for category ' . $psCategoryId . ' => ' . $reverbCode . ' : result = ' . var_export($return, true));

        return $return;
    }
}