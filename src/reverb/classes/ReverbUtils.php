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

class ReverbUtils extends ReverbClient
{
    /**
     * Get all object for an endpoint or one by uuid
     *
     * @param $endPoint
     * @param null $uuid
     * @param string List of params with value
     * @param boolean Throw exception if key not exist
     * @return array
     */
    public function getListFromEndpoint($endPoint, $key, $uuid = null, $params = null, $validKey = true)
    {
        $this->module->logs->requestLogs('##########################');
        $this->module->logs->requestLogs('# BEGIN Request GET ' . $endPoint);
        $this->module->logs->requestLogs('##########################');


        if ($uuid) {
            $endPoint .= '/' . $uuid;
        }

        if ($params) {
            $paramsFlat = '';
            foreach ($params as $name => $value) {
                $paramsFlat .= $name . '=' . $value;
            }
            $endPoint .= '?' . $paramsFlat;
        }

        $list = $this->sendGet($endPoint);

        if (!$uuid && !isset($list[$key])) {
            return $this->convertException(new \Exception($endPoint . ' not found'));
        }

        $this->module->logs->requestLogs('##########################');
        $this->module->logs->requestLogs('# END Request GET ' . $endPoint);
        $this->module->logs->requestLogs('##########################');

        $return = '';
        if ($uuid) {
            $return = $list;
        } else {
            $return = $list[$key];
        }

        return $return;
    }

    /**
     * Return formatted list from endpoint for mapping
     */
    public function getFormattedList($endPoint, $key, $display_name)
    {
        $list = $this->getListFromEndpoint($endPoint, $key);

        $formattedList = array();

        foreach ($list as $object) {
            $formattedList[$object['uuid']] = $object[$display_name];
        }

        return $formattedList;
    }
}
