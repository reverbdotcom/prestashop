<?php

namespace Reverb\Mapper\Models;

/**
 * Model Reverb Sync
 *
 * @package Reverb
 * @author Johan Protin
 * @copyright Copyright (c) 2017 - Johan Protin
 * @license
 */
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
