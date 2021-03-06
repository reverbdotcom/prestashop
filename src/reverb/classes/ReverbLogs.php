<?php
/**
 *
 *
 *
 * @author Johan Protin
 * @copyright Copyright (c) 2017 - Johan Protin
 * @license Apache License Version 2.0, January 2004
 * @package Reverb
 */

namespace Reverb;

class ReverbLogs
{
    const LOG_ERROR = 'error';
    const LOG_INFOS = 'infos';
    const LOG_LISTINGS = 'listings';
    const LOG_CATEGORIES = 'categories';
    const LOG_CRON = 'cron';

    public $enable = true;

    public function __construct(\Reverb $module_instance)
    {
        $this->module = $module_instance;
        // init reverb config
        $this->reverbConfig = $module_instance->reverbConfig;
        $this->enable = (isset($this->reverbConfig[\Reverb::KEY_DEBUG_MODE]) ? $this->reverbConfig[\Reverb::KEY_DEBUG_MODE] : true);
    }

    /**
     * Log Errors
     * @param $msg
     */
    public function errorLogs($msg)
    {
        $this->writeLogs(self::LOG_ERROR, $msg);
    }

    /**
     * Log info
     * @param $msg
     */
    public function infoLogs($msg)
    {
        $this->writeLogs(self::LOG_INFOS, $msg);
    }

    /**
     * Cron log
     *
     * @param $msg
     */
    public function cronLogs($msg)
    {
        $this->writeLogs(self::LOG_CRON, $msg);
    }

    /**
     * Log API call
     * @param $msg
     * @param $endpoint
     */
    public function requestLogs($msg, $endpoint)
    {
        $file = $this->getLogsFileByApiEndPoint($endpoint);
        $this->writeLogs($file, $msg);
    }

    private function writeLogs($file, $msg)
    {
        if ($this->enable) {
            $filename = _PS_MODULE_DIR_ . 'reverb/logs/' . date('Y-m-d') . '-' . $file . '-logs.txt';
            $this->file_prepend('## ' . date('Y-m-d H:i:s') . ' : ' . $msg, $filename);
        }
    }

    private function getLogsFileByApiEndPoint($endPoint)
    {
        if (strstr($endPoint, ReverbCategories::REVERB_CATEGORIES_ENDPOINT)) {
            return self::LOG_CATEGORIES;
        }
        if (strstr($endPoint, ReverbProduct::REVERB_PRODUCT_ENDPOINT)) {
            return self::LOG_LISTINGS;
        }
        return self::LOG_INFOS;
    }

    private function file_prepend($string, $filename)
    {
        if (!file_exists($filename)) {
            $fp = fopen($filename, 'a+');
            fputs($fp, $string . PHP_EOL);
            fclose($fp);
        } else {
            $fileContent = file_get_contents($filename);
            file_put_contents($filename, $string . PHP_EOL . $fileContent);
        }
    }
}
