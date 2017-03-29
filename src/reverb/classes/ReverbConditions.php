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

    public function __construct($module)
    {
        parent::__construct($module);
        $this->setEndPoint(self::REVERB_CONDITIONS_ENDPOINT)
            ->setRootKey(self::REVERB_ROOT_KEY);
    }

    /**
     * Return formatted conditions for mapping
     */
    public function getFormattedConditions()
    {
        return $this->getFormattedList(self::REVERB_DISPLAYNAME);
    }
}