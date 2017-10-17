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

class ReverbAuth extends ReverbClient
{
    const REVERB_AUTH_ENDPOINT = 'my/account';
    const REVERB_ROOT_KEY = 'shop';

    private $scope = array('read_listings', 'write_listings');

    /**
     * ReverbAuth constructor.
     * @param \Reverb $module
     */
    public function __construct($module, $token = null)
    {
        parent::__construct($module, $token);
        $this->setEndPoint(self::REVERB_AUTH_ENDPOINT)
            ->setRootKey(self::REVERB_ROOT_KEY);
    }

    /**
     * @return null|string
     */
    public function getAppClientId()
    {
        return isset($this->reverbConfig[\Reverb::KEY_APP_CLIENT_ID]) ? $this->reverbConfig[\Reverb::KEY_APP_CLIENT_ID] : null;
    }

    /**
     * @return null|string
     */
    public function getAppRedirectUri()
    {
        return isset($this->reverbConfig[\Reverb::KEY_APP_REDIRECT_URI]) ? $this->reverbConfig[\Reverb::KEY_APP_REDIRECT_URI] : null;
    }

    /**
     * @deprecated not use yet
     * Return the Reverb request access URL
     * @param string
     * @return string url
     */
    public function getRequestAccessUrl($state)
    {
        return $this->getBaseUrl()
            . 'oauth/authorize?client_id='
            . $this->getAppClientId()
            . '&redirect_uri='
            . $this->getAppRedirectUri()
            . '&response_type=token'
            . '&scope=' . implode('+', $this->scope)
            . '&state=' . $state;
    }
}
