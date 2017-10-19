<?php
/**
 *   Mapping category
 *
 * @author Johan Protin
 * @copyright Copyright (c) 2017 - Johan Protin
 * @license Apache License Version 2.0, January 2004
 * @package Reverb
 */
class ReverbMapping
{
    const DEFAULT_PAGINATION = 50;
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
    public static function getFormattedPsCategories($languageId,$id_shop)
    {
        $sql = new DbQuery();
        $sql->select('c.id_category as id_category,  ' .
            'c.id_parent as id_parent,' .
            'c.is_root_category,' .
            'cl.name as name,' .
            'rm.reverb_code as reverb_code,' .
            'rm.id_mapping as id_mapping')
            ->from('category', 'c')
            ->leftJoin('category_lang', 'cl', 'cl.`id_category` = c.`id_category`')
            ->leftJoin('category_shop', 'cs', 'cs.`id_category` = c.`id_category`')
            ->leftJoin('reverb_mapping', 'rm', 'rm.`id_category` = c.`id_category`')
            ->where('((c.`id_parent` != 0 AND c.`id_parent`=' . (int)Category::getRootCategory()->id . ') OR (c.id_category='. (int)Category::getRootCategory()->id . ')) AND cs.`id_shop`= '.$id_shop.' AND `id_lang` = ' . (int)$languageId)
            ->orderBy('c.`id_parent` ASC, cl.`name` ASC');
        //=========================================
        //          PAGINATION
        //=========================================
        $page = (int)Tools::getValue('submitFilterps_mapping_category');
        $nbByPage = self::DEFAULT_PAGINATION;
        if (Tools::getValue('ps_mapping_category_pagination')) {
            $nbByPage = Tools::getValue('ps_mapping_category_pagination');
        } elseif (Tools::getValue('selected_pagination')) {
            $nbByPage = Tools::getValue('selected_pagination');
        }
        if ($page > 1) {
            $sql->limit($nbByPage, ($page - 1) * $nbByPage);
        } else {
            $sql->limit($nbByPage);
        }
        $result = Db::getInstance()->executeS($sql);
        $indexedCategories = $categories = array();
        foreach ($result as $row) {
            $indexedCategories[$row['id_category']] = $row;
        }
        foreach ($indexedCategories as $categoryId => $category) {
            if (!$category['is_root_category']) {
                $categories[$categoryId] = array(
                    'ps_category_id' => $categoryId,
                    'ps_category' => self::getPsFullName($category, $indexedCategories),
                    'reverb_code' => $category['reverb_code'],
                    'id_mapping' => $category['id_mapping'],
                );
            }
        }
        return $categories;
    }
    /**
     * Return formatted PS child-categories for mapping select form
     *
     * @param int $languageId
     * @return array
     */
    public static function getFormattedPsChildCategories($id_parent, $languageId, $shop_id)
    {
        $sql = '
		SELECT c.`id_category`, c.`id_parent`, cl.`name`, rm.`reverb_code`, rm.`id_mapping`,
		IF((
			SELECT COUNT(*)
			FROM `'._DB_PREFIX_.'category` c2
			WHERE c2.`id_parent` = c.`id_category`
		) > 0, 1, 0) AS has_children
		FROM `'._DB_PREFIX_.'category` c
		LEFT JOIN `'._DB_PREFIX_.'category_lang` cl ON (c.`id_category` = cl.`id_category` '.Shop::addSqlRestrictionOnLang('cl', $shop_id).')
		LEFT JOIN `'._DB_PREFIX_.'category_shop` cs ON (c.`id_category` = cs.`id_category` AND cs.`id_shop` = '.(int)$shop_id.')
		LEFT JOIN `'._DB_PREFIX_.'reverb_mapping` rm ON (rm.`id_category` = c.`id_category`)
		WHERE `id_lang` = '.(int)$languageId.'
		AND c.`id_parent` = '.(int)$id_parent;
        $sql .= ' AND cs.`id_shop` = '.(int)$shop_id;
        
        return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);
    }
    /**
     * Return number of PS categories for mapping
     *
     * @param int $languageId
     * @return int
     */
    public static function countPsCategories($languageId,$id_shop)
    {
        $sql = new DbQuery();
        $sql->select('count(*) as totals')
            ->from('category', 'c')
            ->leftJoin('category_lang', 'cl', 'cl.`id_category` = c.`id_category`')
            ->leftJoin('category_shop', 'cs', 'cs.`id_category` = c.`id_category`')
            ->leftJoin('reverb_mapping', 'rm', 'rm.`id_category` = c.`id_category`')
            ->where('((c.`id_parent` != 0 AND c.`id_parent`=' . (int)Category::getRootCategory()->id . ') OR (c.id_category='. (int)Category::getRootCategory()->id . ')) AND cs.`id_shop`= '.$id_shop.' AND `id_lang` = ' . (int)$languageId);
        $result = Db::getInstance()->getRow($sql);
        return $result['totals'];
    }
    /**
     * Return the full name category included parent name
     *
     * @param array $category
     * @param array $indexedCategories
     * @return string
     */
    private static function getPsFullName($category, $indexedCategories)
    {
        if (!isset($category['id_parent']) || !isset($indexedCategories[$category['id_parent']])) {
            return $category['name'];
        }
        return self::getPsFullName(
            $indexedCategories[$category['id_parent']],
            $indexedCategories
        )
        . ' / ' . $category['name'];
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
    public static function getMappingId($psCategoryId)
    {
        $sql = new DbQuery();
        $sql->select('rm.id_mapping')
            ->from('reverb_mapping', 'rm')
            ->where('rm.`id_category` = ' . $psCategoryId);
        $result = Db::getInstance()->getValue($sql);
        return $result;
    }
    /**
     * Return the Reverb code from PS category
     *
     * @param int $psCategoryId
     * @return int|false
     */
    public static function getReverbCode($psCategoryId)
    {
        $sql = new DbQuery();
        $sql->select('rm.reverb_code')
            ->from('reverb_mapping', 'rm')
            ->where('rm.`id_category` = ' . $psCategoryId);
        $result = Db::getInstance()->getValue($sql);
        return $result;
    }
    /**
     * Update a mapping
     *
     * @param $mappingId
     * @param $psCategoryId
     * @param $reverbCode
     * @return bool
     */
    private function updateMapping($mappingId, $psCategoryId, $reverbCode)
    {
        $return = false;
        $exec = Db::getInstance()->update(
            'reverb_mapping',
            array('reverb_code' => $reverbCode),
            'id_mapping = ' . (int)$mappingId . ' AND id_category = ' . (int)$psCategoryId
        );
        if ($exec) {
            $return = $mappingId;
            if ($return) {
                $return = $this->subCategoriesManagement($psCategoryId, $reverbCode);
            }
        }
        $this->module->logs->infoLogs(
            'Update mapping ' . $mappingId . ' for category ' . $psCategoryId . ' => ' . $reverbCode . ' : ' . var_export($return, true)
        );
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
                'reverb_code' => $reverbCode,)
        );
        if ($exec) {
            $return = Db::getInstance()->Insert_ID();
            if ($return) {
                $return = $this->subCategoriesManagement($psCategoryId, $reverbCode);
            }
        }
        $this->module->logs->infoLogs(
            'Insert mapping for category ' . $psCategoryId . ' => ' . $reverbCode . ' : result = ' . var_export($return, true)
        );
        return $return;
    }

    /**
     * Delete and insert the new Reverb category
     *
     * @param $psCategoryId
     * $return bool
     */

    private function subCategoriesManagement($psCategoryId, $reverbCode)
    {
        $return = false;
        $delete_categories = array();
        $insert_categories = array();
        // load the category object
        $cat = new Category($psCategoryId);
        // get all category children
        $all_cat = $cat->getAllChildren();
        // construct array delete and inser to optimize sql request (2 requests)
        foreach ($all_cat as $subcat) {
            if (isset($subcat->id) && $subcat->id > 0) {
                // delete category in the reverb_mapping
                $delete_categories[] = $subcat->id;
                // insert category in the reverb mapping with new Reverb Code
                $insert_categories[] = array(
                    'id_category' => $subcat->id,
                    'reverb_code' => $reverbCode,);
            }
        }
        // execute delete request
        if (count($delete_categories)) {
            $delete_exec = Db::getInstance()->delete(
                'reverb_mapping',
                'id_category in ('.implode(',', $delete_categories).')'
            );
            if ($delete_exec) {
                // execute insert request
                $insert_exec = Db::getInstance()->insert(
                    'reverb_mapping',
                    $insert_categories
                );
                if ($insert_exec) {
                    $this->module->logs->infoLogs(
                        'Insert mapping for sub-category '.implode(',', $delete_categories)
                    );
                    $return = true;
                    unset($delete_categories);
                    unset($insert_categories);
                } else {
                    $this->module->logs->errorLogs('error Insert Sub-Categories in reverb_mapping ('.implode(',', $delete_categories).')');
                }
            } else {
                $this->module->logs->errorLogs('error Delete existing Sub-Categories in reverb_mapping ('.implode(',', $delete_categories).')');
            }
        }
        return $return;
    }
}