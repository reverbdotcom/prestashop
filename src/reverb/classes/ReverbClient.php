<?php
namespace Reverb;

require_once(dirname(__FILE__) . '/../vendor/autoload.php');

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Message\ResponseInterface;

class ReverbClient extends Client
{
    protected $context = false;
    protected $module = false;
    protected $client;
    protected $headers = array('Accept' => 'application/json', 'Content-Type'=>'application/json','Accept-Version'=> '3.0');
    protected $endPoint = '';
    protected $rootKey = '';

    public $reverbConfig;

    public function __construct(\Reverb $module_instance)
    {
        $this->module = $module_instance;

        $this->context = \Context::getContext();

        if ($this->context->employee) {
            $iso_code = \Language::getIsoById($this->context->employee->id_lang);
        } else {
            $iso_code = \Language::getIsoById(\Configuration::get('PS_LANG_DEFAULT'));
        }

        // init reverb config
        $this->reverbConfig = $module_instance->reverbConfig;

        if (!empty($this->reverbConfig[\Reverb::KEY_API_TOKEN])) {
            $this->addHeaders(array(
                'Authorization' => 'Bearer ' . $this->reverbConfig[\Reverb::KEY_API_TOKEN],
                'Accept-Language' => $iso_code,
            ));
        }

        parent::__construct(array('base_url' => $this->getBaseUrl()));
    }

    /**
     * @return string
     */
    public function getEndPoint()
    {
        return $this->endPoint;
    }

    /**
     * @param $endPoint
     * @return $this
     */
    public function setEndPoint($endPoint)
    {
        $this->endPoint = $endPoint;
        return $this;
    }

    /**
     * @return string
     */
    public function getRootKey()
    {
        return $this->rootKey;
    }

    /**
     * @param $rootKey
     * @return $this
     */
    public function setRootKey($rootKey)
    {
        $this->rootKey = $rootKey;
        return $this;
    }

    /**
     * @param array $headers
     * @return $this
     */
    public function setHeaders(array $headers)
    {
        $this->headers = $headers;
        return $this;
    }

    /**
     * @param array $headers
     * @return $this
     */
    public function addHeaders(array $headers)
    {
        $this->headers = array_merge($this->headers, $headers);
        return $this;
    }

    /**
     * @return array
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    public function getBaseUrl()
    {
        $url = $this->module->getReverbUrl();
        return $url . '/api/';
    }

    /**
     * Get all object for an endpoint or one by uuid
     *
     * @param null $uuid
     * @return mixed|string
     */
    public function getListFromEndpoint($uuid = null)
    {
        $key = $this->getRootKey();

        if ($uuid) {
            $this->setEndPoint($this->getEndPoint() . '/' . $uuid);
        }

        $list = $this->sendGet();

        if (!$uuid && !isset($list[$key])) {
            return $this->convertException(new \Exception($this->getEndPoint() . ' not found'));
        }

        return $uuid ? $list : $list[$key];
    }

    /**
     * Return formatted list from endpoint for mapping
     *
     * @param $display_name
     * @return array
     */
    public function getFormattedList($display_name)
    {
        $list = $this->getListFromEndpoint();

        $formattedList = array();

        foreach ($list as $object) {
            $formattedList[$object['uuid']] = $object[$display_name];
        }

        return $formattedList;
    }

    /**
     * Send a GET request
     * @return mixed
     */
    public function sendGet()
    {
        return $this->sendResquest('GET');
    }

    /**
     * Send a POST request
     * @param array $params
     * @return mixed
     */
    public function sendPost($params = array())
    {
        return $this->sendResquest('POST', $params);
    }

    /**
     * Send a PUT request
     * @param array $params
     * @return mixed
     */
    public function sendPut($params = array())
    {
        return $this->sendResquest('PUT', $params);
    }

    /**
     *  Send POST or PUT
     *
     * @param $method
     * @return mixed
     */
    private function sendResquest($method, $params = array())
    {
        try {
            $this->logMessage('# ' . $method . ' ' . $this->getBaseUrl() . $this->getEndPoint());

            $options = array('headers' => $this->getHeaders());
            if (!empty($params)) {
                $options['body'] = $params;
            }

            $request = $this->createRequest($method, $this->getEndPoint(), $options);

            $this->logMessage('# with body ' . $request->getBody());
            $this->logMessage('# with query ' . $request->getQuery());
            $this->logMessage('# with header Content-Type ' . var_export($this->getHeaders(), true));

            $response = $this->send($request);

            return $this->convertResponse($response);

        } catch (\Exception $e)
        {
            return $this->convertException($e);
        }
    }

    /**
     * Analyse and convert a Guzzle response to an array
     * @param ResponseInterface $response
     * @return mixed
     */
    protected function convertResponse(ResponseInterface $response)
    {
        $content = $response->getBody()->getContents();
        if (! $array = json_decode($content, true)) {
            $this->logMessage(var_export($content, true));
            $this->convertException(new \Exception('Api response is not a json'));
        }
        $this->logMessage(var_export($array, true));
        return $array;
    }

    /**
     * Analyse and convert an exception
     * @param \Exception $e
     * @return mixed|string
     */
    protected function convertException(\Exception $e)
    {
        $this->module->logs->errorLogsReverb('# Exception : ' . get_class($e) . ' : ' . $e->getMessage());

        if ($e instanceof ClientException) {
            $message = json_decode($e->getResponse()->getBody()->getContents(), true);
            $this->module->logs->errorLogsReverb(var_export($message, true));
            return $message;
        }

        return $e->getMessage();
    }

    /**
     * Log a message in the good file
     * @param string $msg
     */
    protected function logMessage($msg)
    {
        $this->module->logs->requestLogs($msg, $this->getEndPoint());
    }
}