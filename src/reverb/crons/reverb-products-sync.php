<?php
include(dirname(__FILE__).'/../../../config/config.inc.php');

require_once dirname(__FILE__).'/../classes/ReverbClient.php';
require_once dirname(__FILE__).'/../classes/models/ReverbSync.php';
require_once dirname(__FILE__).'/../classes/ReverbProduct.php';
require_once dirname(__FILE__).'/../reverb.php';

$module = new Reverb();
$reverbProduct = new \Reverb\ReverbProduct($module);

$module->logs->requestLogs('##########################');
$module->logs->requestLogs('# BEGIN Product Sync CRON');
$module->logs->requestLogs('##########################');

$products = $module->reverbSync->getProductsToSync();

$module->logs->requestLogs('# ' . count($products) . ' product(s) to sync');

foreach ($products as $product) {
    if ($product['reverb_enabled']) {
        $res = $reverbProduct->syncProduct($product, ReverbSync::ORIGIN_CRON);
        $module->logs->requestLogs('# ' . json_encode($res));
    } else {
        $module->logs->requestLogs('# Product ' . $product['id_product'] . ' not enabled for reverb sync');
    }
}

$module->logs->requestLogs('##########################');
$module->logs->requestLogs('# END Product Sync CRON');
$module->logs->requestLogs('##########################');