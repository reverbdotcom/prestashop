<?php
namespace Reverb;

class ReverLogs
{
    public $enable = true;

    public function __construct(\Reverb $module_instance)
    {
        $this->module = $module_instance;
        // init reverb config
        $this->configReverb = $module_instance->configReverb;
        $this->enable = (isset($this->configReverb[\Reverb::KEY_DEBUG_MODE]) ? $this->configReverb[\Reverb::KEY_DEBUG_MODE] : true);
    }

    /**
     *
     * LOG Errors
     *
     */
    public function errorLogsReverb($msg)
    {
        $this->writeLogs(0, $msg);
    }

    /**
     *
     * LOG APP
     *
     */
    public function logsReverb($msg)
    {
        $this->writeLogs(1, $msg);
    }

    public function callbackLogs($msg)
    {
        $this->writeLogs(2, $msg);
    }

    public function requestLogs($msg)
    {
        $this->writeLogs(3, $msg);
    }

    public function refundLogs($msg)
    {
        $this->writeLogs(4, $msg);
    }

    private function writeLogs($code, $msg)
    {
        if ($this->enable) {
            switch ($code) {
                case 0:
                    $fp = fopen(_PS_MODULE_DIR_ . 'reverb/logs/' . date('Y-m-d') . '-error-logs.txt', 'a+');
                    break;
                case 1:
                    $fp = fopen(_PS_MODULE_DIR_ . 'reverb/logs/' . date('Y-m-d') . '-infos-logs.txt', 'a+');
                    break;
                case 2:
                    $fp = fopen(_PS_MODULE_DIR_ . 'reverb/logs/' . date('Y-m-d') . '-callback.txt', 'a+');
                    break;
                case 3:
                    $fp = fopen(_PS_MODULE_DIR_ . 'reverb/logs/' . date('Y-m-d') . '-request-new-order.txt', 'a+');
                    break;
                case 4:
                    $fp = fopen(_PS_MODULE_DIR_ . 'reverb/logs/' . date('Y-m-d') . '-refund-order.txt', 'a+');
                    break;
                default:
                    $fp = fopen(_PS_MODULE_DIR_ . 'reverb/logs/' . date('Y-m-d') . '-infos-logs.txt', 'a+');
                    break;
            }
            fseek($fp, SEEK_END);
            fputs($fp, '## ' . date('Y-m-d H:i:s') . ' ##' . PHP_EOL);
            fputs($fp, $msg . PHP_EOL);
            fclose($fp);
        }
    }
}
