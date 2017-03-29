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
class Category extends AbstractModel
{
    public $uuid;

    /**
     * Category constructor.
     *
     * @param string $uuid
     */
    public function __construct($uuid)
    {
        $this->uuid = $uuid;
    }
}
