<?php
namespace Reverb;

class ReverLogs
{
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
    public function errorLogsReverb($msg)
    {
        $this->writeLogs('error', $msg);
    }

    /**
     * Log info
     * @param $msg
     */
    public function infoLogs($msg)
    {
        $this->writeLogs('infos', $msg);
    }

    /**
     * Cron log
     *
     * @param $msg
     */
    public function cronLogs($msg)
    {
        $this->writeLogs('cron', $msg);
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
            $fp = fopen(_PS_MODULE_DIR_ . 'reverb/logs/' . date('Y-m-d') . '-' . $file . '-logs.txt', 'a+');
            fseek($fp, SEEK_END);
            fputs($fp, '## ' . date('Y-m-d H:i:s') . ' : ' . $msg . PHP_EOL);
            fclose($fp);
        }
    }

    private function getLogsFileByApiEndPoint($endPoint)
    {
        if (strstr($endPoint, ReverbCategories::REVERB_CATEGORIES_ENDPOINT)) {
            return 'categories';
        }
        if (strstr($endPoint, ReverbProduct::REVERB_PRODUCT_ENDPOINT)) {
            return 'listings';
        }
        return 'infos';
    }
}
