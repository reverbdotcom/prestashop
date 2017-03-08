<?php
namespace Reverb;

class ReverbCategories extends ReverbClient
{

    CONST REVERB_CATEGORIES_ENDPOINT = 'categories';

    /**
     * Get all categories or one by uuid
     * @param null $uuid
     * @return array
     */
    public function getCategories($uuid = null)
    {
        $this->module->logs->requestLogs('##########################');
        $this->module->logs->requestLogs('# BEGIN Request GET categories');
        $this->module->logs->requestLogs('##########################');

        $endPoint = self::REVERB_CATEGORIES_ENDPOINT;

        if ($uuid) {
            $endPoint .= '/' . $uuid;
        }
        $categories = $this->sendGet($endPoint);

        if (!$uuid && !isset($categories['categories'])) {
            return $this->convertException(new \Exception('Categories not found'));
        }

        $this->module->logs->requestLogs('##########################');
        $this->module->logs->requestLogs('# END Request GET categories');
        $this->module->logs->requestLogs('##########################');

        return $uuid ? $categories : $categories['categories'];
    }
}