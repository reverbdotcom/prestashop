<?php
namespace Reverb;

/**
 * Client Conditions
 *
 * @package Reverb
 * @author Johan Protin
 * @copyright Copyright (c) 2017 - Johan Protin
 * @license
 */
class ReverbConditions extends ReverbClient
{

    CONST REVERB_CONDITIONS_ENDPOINT = 'listing_conditions';
    CONST REVERB_ROOT_KEY = 'conditions';
    CONST REVERB_DISPLAYNAME= 'display_name';

    /**
     * Return formatted conditions for mapping
     */
    public function getFormattedConditions()
    {
        $reverbUtils = new \Reverb\ReverbUtils($this->module);
        return $reverbUtils->getFormattedList(self::REVERB_CONDITIONS_ENDPOINT,self::REVERB_ROOT_KEY,self::REVERB_DISPLAYNAME);
    }
}