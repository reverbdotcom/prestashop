<?php
/**
 *  Helper for cron
 *
 * @author Johan Protin
 * @copyright Copyright (c) 2017 - Johan Protin
 * @license Apache License Version 2.0, January 2004
 * @package Reverb
 */

class HelperCron
{
    const CODE_CRON_STATUS_PROGRESS = 'process';
    const CODE_CRON_STATUS_END = 'finish';
    const CODE_CRON_STATUS_ERROR = 'error';

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
                $nbSync
            );
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

        $this->module->logs->infoLogs('Update cron status ' . $idCron . ' with status ' . $status);
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
    public function getDateLastCronWithStatus($status)
    {
        $sql = new DbQuery();
        $sql->select('rc.date')
            ->from('reverb_crons', 'rc')
            ->where('rc.`status` = "' . $status . '"')
            ->orderBy('rc.`id_cron` DESC');

        $date = Db::getInstance()->getValue($sql);
        return $date;
    }
}
