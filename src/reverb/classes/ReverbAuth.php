<?php
namespace Reverb;

class ReverbAuth extends ReverbClient
{

    private $scope = ['read_listings', 'write_listings'];

    /**
     * @return null|string
     */
    public function getAppClientId()
    {
        return isset($this->reverbConfig[\Reverb::KEY_APP_CLIENT_ID]) ? $this->reverbConfig[\Reverb::KEY_APP_CLIENT_ID]: null;
    }

    /**
     * @return null|string
     */
    public function getAppRedirectUri()
    {
        return isset($this->reverbConfig[\Reverb::KEY_APP_REDIRECT_URI]) ? $this->reverbConfig[\Reverb::KEY_APP_REDIRECT_URI]: null;
    }

    /**
     * @deprecated not use yet
     * Return the Reverb request access URL
     * @param $state random state
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
            . '&state=' . $state
        ;
    }
}