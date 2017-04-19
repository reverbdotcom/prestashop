<?php
/**
 *  Map product reverb and prestashop
 *
 * @author Johan Protin
 * @copyright Copyright (c) 2017 - Johan Protin
 * @license Apache License Version 2.0, January 2004
 * @package Reverb
 */

namespace Reverb\Mapper\Models;

class Condition extends AbstractModel
{
    public $uuid;

    /**
     * Condition constructor.
     *
     * @param string $uuid
     */
    public function __construct($uuid)
    {
        $this->uuid = $uuid;
    }
}
