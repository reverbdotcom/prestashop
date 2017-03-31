<?php
/**
 * 2007-2016 PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2016 PrestaShop SA
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * International Registered Trademark & Property of PrestaShop SA
 */

/**
 * @since 1.5.0
 */
class HelperCron
{
    CONST CODE_CRON_STATUS_PROGRESS = 'process';
    CONST CODE_CRON_STATUS_END = 'finish';
    CONST CODE_CRON_STATUS_ERROR = 'error';

    /**
     * @var $module
     */
    private $module;

    /**
     * Construct a new Helper Cron
     *
     * @param $request
     */
    public function __construct($module)
    {
        $this->module = $module;
    }

    /**
     * @param $idCron
     * @param $code
     * @param null $status
     * @param null $details
     * @param null $nbToSync
     * @param null $nbSync
     * @return int|string
     */
    public function insertOrUpdateCronStatus(
        $idCron,
        $code,
        $status = null,
        $details = null,
        $nbToSync = null,
        $nbSync = null
    ) {
        $id = null;
        if (!empty($idCron)) {
            self::updateCronStatus(
                $idCron,
                $code,
                $status,
                $details,
                $nbToSync,
                $nbSync
            );
        } else {
            $id = self::insertSyncStatus(
                $idCron,
                $code,
                $status,
                $details,
                $nbToSync,
                $nbSync);
        }

        return $id ? $id : true;
    }

    /**
     * @param $idCron
     * @param $code
     * @param null $status
     * @param null $details
     * @param null $nbToSync
     * @param null $nbSync
     */
    private function updateCronStatus($idCron, $code, $status = null, $details = null, $nbToSync = null, $nbSync = null)
    {
        Db::getInstance()->update(
            'reverb_crons',
            array(
                'date' => (new \DateTime())->format('Y-m-d H:i:s'),
                'code' => $code,
                'number_to_sync' => $nbToSync,
                'number_sync' => $nbSync,
                'details' => $details,
                'status' => $status
            ),
            'id_cron= ' . (int)$idCron
        );

        $this->module->logs->infoLogs('Update cron status ' . $idCron .' with status ' . $status);
    }

    /**
     *  Process an insert into table Reverb cron
     *
     * @param $idCron
     * @param $code
     * @param null $status
     * @param null $details
     * @param null $nbToSync
     * @param null $nbSync
     * @return int|string
     */
    private function insertSyncStatus($idCron, $code, $status = null, $details = null, $nbToSync = null, $nbSync = null)
    {
        $exec = Db::getInstance()->insert(
            'reverb_crons',
            array(
                'date' => (new \DateTime())->format('Y-m-d H:i:s'),
                'code' => $code,
                'number_to_sync' => $nbToSync,
                'number_sync' => $nbSync,
                'details' => $details,
                'status' => $status
            )
        );

        if ($exec) {
            $return = Db::getInstance()->Insert_ID();
        }

        $this->module->logs->infoLogs('Insert reverb cron ' . $return . ' with status ' . $status . ' and code ' . $code);
        return $return;
    }

    /**
     *  Get date from last execution for an cron and an status
     *
     * @param $code
     * @param $status
     * @return null|string
     */
    public function getDateLastCronWithStatus($code, $status) {
        $sql = new DbQuery();
        $sql->select('rc.date')
            ->from('reverb_crons', 'rc')
            ->where('rc.`status` = "' . $status .'"')
            ->orderBy('rc.`id_cron` DESC');

        $date = Db::getInstance()->getValue($sql);
        return $date;
    }
}
