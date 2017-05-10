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

class ReverbConditions extends ReverbClient
{
    const REVERB_CONDITIONS_ENDPOINT = 'listing_conditions';
    const REVERB_ROOT_KEY = 'conditions';
    const REVERB_DISPLAYNAME = 'display_name';

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
