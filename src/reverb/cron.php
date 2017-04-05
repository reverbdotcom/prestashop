<?php

/**
 * Context for cron
 *
 * @package Reverb
 * @author Johan Protin
 * @copyright Copyright (c) 2017 - Johan Protin
 * @license Apache License Version 2.0, January 2004
 */

include(dirname(__FILE__) . '/../../config/config.inc.php');
include(dirname(__FILE__) . '/../../init.php');
require_once dirname(__FILE__) . '/classes/crons/OrdersSyncEngine.php';
require_once dirname(__FILE__) . '/classes/ReverbClient.php';
require_once dirname(__FILE__) . '/classes/models/ReverbSync.php';
require_once dirname(__FILE__) . '/classes/ReverbProduct.php';
require_once dirname(__FILE__) . '/reverb.php';

const CODE_CRON_ORDERS = 'orders';
const CODE_CRON_PRODUCT = 'product';

if (PHP_SAPI === 'cli') {
    $code_cron = $argv[1];
}
else {
    $code_cron = $_GET['code'];
}

if (!isset($code_cron) || $code_cron != CODE_CRON_ORDERS && $code_cron != CODE_CRON_PRODUCT) {
    throw new \Exception('No code cron corresponding. ' . $code_cron);
}

try {
    $module = new \Reverb();
    $helper = new \HelperCron($module);

    $module->logs->cronLogs('##########################');
    $module->logs->cronLogs('# BEGIN ' . $code_cron . ' sync CRON');
    $module->logs->cronLogs('##########################');
    $idCron = $helper->insertOrUpdateCronStatus(null, $code_cron, $helper::CODE_CRON_STATUS_PROGRESS);

    //$pstoken = Tools::getAdminTokenLite('AdminModules');
    //if (!Tools::getValue('token') && Tools::getValue('token') == $pstoken) {
        if ($module->isApiTokenAvailable()) {
            switch ($code_cron) {
                case CODE_CRON_ORDERS:
                    $engine = new \OrdersSyncEngine($module);
                    $engine->processSyncOrder($idCron);
                    break;
                case CODE_CRON_PRODUCT:
                    $reverbProduct = new \Reverb\ReverbProduct($module);
                    $products = $module->reverbSync->getProductsToSync();
                    $module->logs->cronLogs('# ' . count($products) . ' product(s) to sync');
                    foreach ($products as $product) {
                        if ($product['reverb_enabled']) {
                            $res = $reverbProduct->syncProduct($product, ReverbSync::ORIGIN_CRON);
                            $module->logs->cronLogs('# ' . json_encode($res));
                        } else {
                            $module->logs->cronLogs('# Product ' . $product['id_product'] . ' not enabled for reverb sync');
                        }
                    }
                    break;
            }
        } else {
            throw new \Exception('No valid API token is defined for the shop');
        }
    //} else {
    //    throw new \Exception('No secure TOKEN to launch the cron' . CODE_CRON_ORDERS);
    //}
} catch (\Exception $e) {
    $error = 'Error in cron ' . $code_cron . ' ' . $e->getMessage();
    $module->logs->cronLogs($error);
    $module->logs->errorLogsReverb($error);
    $helper->insertOrUpdateCronStatus($idCron, $code_cron, $helper::CODE_CRON_STATUS_ERROR,
        $e->getMessage());
}

$module->logs->cronLogs('##########################');
$module->logs->cronLogs('# END ' . $code_cron . ' sync CRON');
$module->logs->cronLogs('##########################');
