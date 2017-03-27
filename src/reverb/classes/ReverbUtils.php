<?php
namespace Reverb;

/**
 * Client Utils
 *
 * @package Reverb
 * @author Johan Protin
 * @copyright Copyright (c) 2017 - Johan Protin
 * @license
 */
class ReverbUtils extends ReverbClient
{

    /**
     * Get all object for an endpoint or one by uuid
     *
     * @param null $uuid
     * @return array
     */
    public function getListFromEndpoint($endPoint,$key,$uuid = null)
    {
        $this->module->logs->requestLogs('##########################');
        $this->module->logs->requestLogs('# BEGIN Request GET ' . $endPoint);
        $this->module->logs->requestLogs('##########################');


        if ($uuid) {
            $endPoint .= '/' . $uuid;
        }

        $list = $this->sendGet($endPoint);

        if (!$uuid && !isset($list[$key])) {
            return $this->convertException(new \Exception($endPoint . ' not found'));
        }

        $this->module->logs->requestLogs('##########################');
        $this->module->logs->requestLogs('# END Request GET ' . $endPoint);
        $this->module->logs->requestLogs('##########################');

        return $uuid ? $list : $list[$key];
    }

    /**
     * Return formatted list from endpoint for mapping
     */
    public function getFormattedList($endPoint,$key,$display_name)
    {
        $list = $this->getListFromEndpoint($endPoint,$key);

        $formattedList = array();

        foreach ($list as $object) {
            $formattedList[$object['uuid']] = $object[$display_name];
        }

        return $formattedList;
    }
}