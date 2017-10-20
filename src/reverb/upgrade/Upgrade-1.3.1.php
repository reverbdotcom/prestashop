<?php
function upgrade_module_1_3_1($module)
{
    // Process Module upgrade to 1.3.1
    // ....
    $module->logs->infoLogs(__METHOD__);
    $sql = array();
    // Drop foreign key for table ps_reverb_sync
    $query = "SELECT CONCAT('ALTER TABLE ', table_name, ' drop foreign key `', constraint_name, '`;') AS query FROM information_schema.key_column_usage WHERE TABLE_NAME = '" . _DB_PREFIX_ . "reverb_sync' AND COLUMN_NAME = 'id_product' AND REFERENCED_TABLE_NAME = '" . _DB_PREFIX_ . "product';";
    $result = Db::getInstance()->executeS($query);
    if (count($result) == 1) {
        if (Db::getInstance()->execute($result[0]['query']) == false) {
            return false;
        }
    }
    $query = "SELECT CONCAT('ALTER TABLE ', table_name, ' drop foreign key `', constraint_name, '`;') AS query FROM information_schema.key_column_usage WHERE TABLE_NAME = '" . _DB_PREFIX_ . "reverb_sync' AND COLUMN_NAME = 'id_product_attribute' AND REFERENCED_TABLE_NAME = '" . _DB_PREFIX_ . "product_attribute';";
    $result = Db::getInstance()->executeS($query);
    if (count($result) == 1) {
        if (Db::getInstance()->execute($result[0]['query']) == false) {
            return false;
        }
    }
    // Recreate foreign key with DELETE CASCADE
    $sql[] = 'ALTER TABLE `' . _DB_PREFIX_ . 'reverb_sync` ADD CONSTRAINT `ps_reverb_sync_ibfk_1` FOREIGN KEY fk_reverb_sync_product(id_product) REFERENCES `' . _DB_PREFIX_ . 'product` (id_product) ON DELETE CASCADE;';
    $sql[] = 'ALTER TABLE `' . _DB_PREFIX_ . 'reverb_sync` ADD CONSTRAINT `ps_reverb_sync_ibfk_2` FOREIGN KEY fk_reverb_sync_product_2(id_product_attribute) REFERENCES `' . _DB_PREFIX_ . 'product_attribute` (id_product_attribute) ON DELETE CASCADE;';
    // Drop foreign key for table ps_reverb_mapping
    $query = "SELECT CONCAT('ALTER TABLE ', table_name, ' drop foreign key `', constraint_name, '`;') AS query FROM information_schema.key_column_usage WHERE TABLE_NAME = '" . _DB_PREFIX_ . "reverb_mapping' AND COLUMN_NAME = 'id_category' AND REFERENCED_TABLE_NAME = '" . _DB_PREFIX_ . "category';";
    $result = Db::getInstance()->executeS($query);
    if (count($result) == 1) {
        if (Db::getInstance()->execute($result[0]['query']) == false) {
            return false;
        }
    }
    // Recreate foreign key with DELETE CASCADE
    $sql[] = 'ALTER TABLE `' . _DB_PREFIX_ . 'reverb_mapping` ADD CONSTRAINT `ps_reverb_mapping_ibfk_1` FOREIGN KEY fk_reverb_mapping_category(id_category) REFERENCES `' . _DB_PREFIX_ . 'category` (id_category) ON DELETE CASCADE;';
    // Drop foreign key for table ps_reverb_attributes
    $query = "SELECT CONCAT('ALTER TABLE ', table_name, ' drop foreign key `', constraint_name, '`;') AS query FROM information_schema.key_column_usage WHERE TABLE_NAME = '" . _DB_PREFIX_ . "reverb_attributes' AND COLUMN_NAME = 'id_product' AND REFERENCED_TABLE_NAME = '" . _DB_PREFIX_ . "product';";
    $result = Db::getInstance()->executeS($query);
    if (count($result) == 1) {
        if (Db::getInstance()->execute($result[0]['query']) == false) {
            return false;
        }
    }
    $query = "SELECT CONCAT('ALTER TABLE ', table_name, ' drop foreign key `', constraint_name, '`;') AS query FROM information_schema.key_column_usage WHERE TABLE_NAME = '" . _DB_PREFIX_ . "reverb_attributes' AND COLUMN_NAME = 'id_lang' AND REFERENCED_TABLE_NAME = '" . _DB_PREFIX_ . "lang';";
    $result = Db::getInstance()->executeS($query);
    if (count($result) == 1) {
        if (Db::getInstance()->execute($result[0]['query']) == false) {
            return false;
        }
    }
    // Recreate foreign key with DELETE CASCADE
    $sql[] = 'ALTER TABLE `' . _DB_PREFIX_ . 'reverb_attributes` ADD CONSTRAINT `ps_reverb_attributes_ibfk_1` FOREIGN KEY fk_reverb_attributes_product(id_product) REFERENCES `' . _DB_PREFIX_ . 'product` (id_product) ON DELETE CASCADE;';
    $sql[] = 'ALTER TABLE `' . _DB_PREFIX_ . 'reverb_attributes` ADD CONSTRAINT `ps_reverb_attributes_ibfk_2` FOREIGN KEY fk_reverb_attributes_lang(id_lang) REFERENCES `' . _DB_PREFIX_ . 'lang` (id_lang) ON DELETE CASCADE;';
    // Drop foreign key for table ps_reverb_shipping_methods
    $query = "SELECT CONCAT('ALTER TABLE ', table_name, ' drop foreign key `', constraint_name, '`;') AS query FROM information_schema.key_column_usage WHERE TABLE_NAME = '" . _DB_PREFIX_ . "reverb_shipping_methods' AND COLUMN_NAME = 'id_attribute' AND REFERENCED_TABLE_NAME = '" . _DB_PREFIX_ . "reverb_attributes';";
    $result = Db::getInstance()->executeS($query);
    if (count($result) == 1) {
        if (Db::getInstance()->execute($result[0]['query']) == false) {
            return false;
        }
    }
    // Recreate foreign key with DELETE CASCADE
    $sql[] = 'ALTER TABLE `' . _DB_PREFIX_ . 'reverb_shipping_methods` ADD CONSTRAINT `ps_reverb_shipping_methods_ibfk_1` FOREIGN KEY fk_reverb_shipping_methods_attribute(id_attribute) REFERENCES `' . _DB_PREFIX_ . 'reverb_attributes` (id_attribute) ON DELETE CASCADE;';
    // Drop foreign key for table ps_reverb_sync_history
    $query = "SELECT CONCAT('ALTER TABLE ', table_name, ' drop foreign key `', constraint_name, '`;') AS query FROM information_schema.key_column_usage WHERE TABLE_NAME = '" . _DB_PREFIX_ . "reverb_sync_history' AND COLUMN_NAME = 'id_product' AND REFERENCED_TABLE_NAME = '" . _DB_PREFIX_ . "product';";
    $result = Db::getInstance()->executeS($query);
    if (count($result) == 1) {
        if (Db::getInstance()->execute($result[0]['query']) == false) {
            return false;
        }
    }
    $query = "SELECT CONCAT('ALTER TABLE ', table_name, ' drop foreign key `', constraint_name, '`;') AS query FROM information_schema.key_column_usage WHERE TABLE_NAME = '" . _DB_PREFIX_ . "reverb_sync_history' AND COLUMN_NAME = 'id_product_attribute' AND REFERENCED_TABLE_NAME = '" . _DB_PREFIX_ . "product_attribute';";
    $result = Db::getInstance()->executeS($query);
    if (count($result) == 1) {
        if (Db::getInstance()->execute($result[0]['query']) == false) {
            return false;
        }
    }
    // Recreate foreign key with DELETE CASCADE
    $sql[] = 'ALTER TABLE `' . _DB_PREFIX_ . 'reverb_sync_history` ADD CONSTRAINT `ps_reverb_sync_history_ibfk_1` FOREIGN KEY fk_reverb_sync_history_product(id_product) REFERENCES `' . _DB_PREFIX_ . 'product` (id_product) ON DELETE CASCADE;';
    $sql[] = 'ALTER TABLE `' . _DB_PREFIX_ . 'reverb_sync_history` ADD CONSTRAINT `ps_reverb_sync_history_ibfk_2` FOREIGN KEY fk_reverb_sync_history_product_attribute(id_product_attribute) REFERENCES `' . _DB_PREFIX_ . 'product_attribute` (id_product_attribute) ON DELETE CASCADE;';
    // Add column tax_exempt
    //$sql[] = 'DELIMITER $$';
    //$sql[] = 'DROP PROCEDURE IF EXISTS add_tax_exempt $$';
    //$sql[] = 'CREATE PROCEDURE add_tax_exempt()';
    //$sql[] = 'BEGIN';
    //$sql[] = 'IF NOT EXISTS(SELECT * FROM information_schema.COLUMNS WHERE TABLE_NAME = \'ps_reverb_attributes\' AND COLUMN_NAME = \'tax_exempt\') THEN';
    $sql[] = 'ALTER TABLE `' . _DB_PREFIX_ . 'reverb_attributes` ADD COLUMN `tax_exempt` tinyint(1);';
    //$sql[] = 'END IF;';
    //$sql[] = 'END $$';
    //$sql[] = 'CALL add_tax_exempt() $$';
    //$sql[] = 'DROP PROCEDURE IF EXISTS add_tax_exempt $$';
    //$sql[] = 'DELIMITER ;';
    foreach ($sql as $query) {
        if (Db::getInstance()->execute($query) == false) {
            return false;
        }
    }
    return true; // Return true if success.
}