<?php
namespace Reverb;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Message\ResponseInterface;

class ReverbClient extends Client
{
    protected $context = false;
    protected $module = false;
    protected $client;
    protected $headers = array('Accept' => 'application/json', 'Content-Type'=>'application/json');

    protected $prod_url = 'https://reverb.com';
    protected $sandbox_url = 'https://sandbox.reverb.com';

    public $reverbConfig;

    public function __construct(\Reverb $module_instance)
    {
        $this->module = $module_instance;

        $this->context = \Context::getContext();

        $iso_code = \Language::getIsoById($this->context->employee->id_lang);

        // init reverb config
        $this->reverbConfig = $module_instance->reverbConfig;

        if (!empty($this->reverbConfig[\Reverb::KEY_API_TOKEN])) {
            $this->addHeaders([
                'Authorization'=> 'Bearer ' . $this->reverbConfig[\Reverb::KEY_API_TOKEN],
                'Accept-Language'=> $iso_code,
                'Accept-Version'=> '1.0',
            ]);
        }

        parent::__construct(array('base_url' => $this->getBaseUrl()));
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
        $url = $this->prod_url;

        if ((bool)$this->reverbConfig[\Reverb::KEY_SANDBOX_MODE]) {
            $url = $this->sandbox_url;
        }

        return $url . '/api/';
    }

    /**
     * Send a GET request
     * @param string $endpoint
     * @return mixed
     */
    public function sendGet($endpoint)
    {
        try {
            $this->module->logs->requestLogs('# GET ' . $this->getBaseUrl() . $endpoint);
            $this->module->logs->requestLogs(var_export($this->getHeaders(), true));

            $response = $this->get(
                $endpoint,
                array('headers' => $this->getHeaders())
            );

            return $this->convertResponse($response);

        } catch (\Exception $e)
        {
            return $this->convertException($e);
        }
    }

    /**
     * Send a POST request
     * @param string $endpoint
     * @param array $params
     * @return mixed
     */
    public function sendPost($endpoint, $params = array())
    {
        try {
            $this->module->logs->requestLogs('# POST ' . $this->getBaseUrl() . $endpoint);

            $request = $this->createRequest('POST', $endpoint, array('json' => $params));

            $this->module->logs->requestLogs('# with body ' . $request->getBody());
            $this->module->logs->requestLogs('# with header Content-Type ' . var_export($this->getHeaders(), true));

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
        $this->module->logs->requestLogs('# response class : ' . get_class($response));
        $content = $response->getBody()->getContents();
        if (! $array = json_decode($content, true)) {
            $this->module->logs->requestLogs(var_export($content, true));
            $this->convertException(new \Exception('Api response is not a json'));
        }
        $this->module->logs->requestLogs(var_export($array, true));
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
}