<?php

/**
 * Context for cron
 *
 * @package Reverb
 * @author Johan Protin
 * @copyright Copyright (c) 2017 - Johan Protin
 * @license Apache License Version 2.0, January 2004
 */

include(dirname(__FILE__) . '/../../../config/config.inc.php');
include(dirname(__FILE__) . '/../../../init.php');

require_once dirname(__FILE__) . '/../reverb.php';
//require_once dirname(__FILE__) . '/../classes/crons/OrdersSyncEngine.php';
require_once dirname(__FILE__) . '/OrdersSyncEngine.php';

const CODE_CRON_ORDERS = 'orders';

try {
    $module = new Reverb();
    $engine = new \OrdersSyncEngine($module);
    //$pstoken = Tools::getAdminTokenLite('AdminModules');

    $module->logs->requestLogs('##########################');
    $module->logs->requestLogs('# BEGIN ' . CODE_CRON_ORDERS . ' sync CRON');
    $module->logs->requestLogs('##########################');

    //if (!Tools::getValue('token') &&  Tools::getValue('token') == $pstoken) {
        if ($module->isApiTokenAvailable()) {
            $engine->processSyncOrder();
        } else {
            throw new \Exception('No valid API token is defined for the shop');
        }
    //}else {
    //    throw new \Exception('No secure TOKEN to launch the cron' . CODE_CRON_ORDERS);
    //}
} catch (\Exception $e) {
    $module->logs->errorLogsReverb('Error in cron ' . CODE_CRON_ORDERS . $e->getTraceAsString());
    $engine->helper->insertOrUpdateCronStatus($idCron, CODE_CRON_ORDERS, $helper::CODE_CRON_STATUS_ERROR,
        $e->getMessage());
}

$module->logs->requestLogs('##########################');
$module->logs->requestLogs('# END ' . CODE_CRON_ORDERS . ' sync CRON');
$module->logs->requestLogs('##########################');




