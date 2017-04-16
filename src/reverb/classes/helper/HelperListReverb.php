<?php

/**
 *  Override Helper List
 *
 * @package Reverb
 * @author Johan Protin
 * @copyright Copyright (c) 2017 - Johan Protin
 * @license Apache License Version 2.0, January 2004
 */
class HelperListReverb extends HelperList
{
    /** @var Smarty_Internal_Template|string */
    protected $header_tpl = 'list_header.tpl';

    /** @var Smarty_Internal_Template|string */
    protected $content_tpl = 'list_content.tpl';
}
