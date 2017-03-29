<?php
include(dirname(__FILE__).'/../../../config/config.inc.php');

require_once dirname(__FILE__).'/../classes/ReverbClient.php';
require_once dirname(__FILE__).'/../classes/models/ReverbSync.php';
require_once dirname(__FILE__).'/../classes/ReverbProduct.php';
require_once dirname(__FILE__).'/../reverb.php';

$module = new Reverb();
$reverbProduct = new \Reverb\ReverbProduct($module);

$module->logs->infoLogs('##########################');
$module->logs->infoLogs('# BEGIN Product Sync CRON');
$module->logs->infoLogs('##########################');

$products = $module->reverbSync->getProductsToSync();

$module->logs->infoLogs('# ' . count($products) . ' product(s) to sync');

foreach ($products as $product) {
    if ($product['reverb_enabled']) {
        $res = $reverbProduct->syncProduct($product, ReverbSync::ORIGIN_CRON);
        $module->logs->infoLogs('# ' . json_encode($res));
    } else {
        $module->logs->infoLogs('# Product ' . $product['id_product'] . ' not enabled for reverb sync');
    }
}

$module->logs->infoLogs('##########################');
$module->logs->infoLogs('# END Product Sync CRON');
$module->logs->infoLogs('##########################');