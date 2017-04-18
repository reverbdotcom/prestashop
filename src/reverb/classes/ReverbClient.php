<?php
namespace Reverb;

require_once(dirname(__FILE__) . '/../vendor/autoload.php');

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Message\RequestInterface;
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

    public function __construct(\Reverb $module_instance, $token = null)
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
        $api_key_token = $module_instance->isApiTokenAvailable();

        if (!empty($token)) {
            $api_key_token = $token;
        }

        $this->addHeaders(array(
            'X-Reverb-App' => 'Prestashop ' . _PS_VERSION_,
        ));

        if (!empty($api_key_token)) {
            $this->addHeaders(array(
                'Authorization' => 'Bearer ' . $api_key_token,
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
     * @param array $params
     * @param boolean $params
     * @return mixed|string
     */
    public function getListFromEndpoint($uuid = null, $params = array() , $validkey = true)
    {
        $key = $this->getRootKey();

        if ($uuid) {
            $this->setEndPoint($this->getEndPoint() . '/' . $uuid);
        }

        if (!empty($params)) {
            $paramsFlat = '';
            foreach ($params as $name => $value){
                $paramsFlat .= $name . '=' .  $value;
            }
            $this->setEndPoint($this->getEndPoint() . '?' . $paramsFlat);
        }

        $list = $this->sendGet();

        if ($validkey) {
            if (!$uuid && !isset($list[$key])) {
                return $this->convertException(new \Exception($this->getEndPoint() . ' not found'));
            }
            return $uuid ? $list : $list[$key];
        } else {
            return $list;
        }
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
     * @param array $params
     * @return mixed
     */
    public function sendGet($params = array())
    {
        return $this->sendResquest('GET', $params);
    }

    /**
     * Send a POST request
     * @param array|json $params
     * @return mixed
     */
    public function sendPost($params)
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
     * @param string $method
     * @param array|json $params
     * @return mixed
     */
    private function sendResquest($method, $params)
    {
        try {
            $this->logRequestMessage('# ' . $method . ' ' . $this->getBaseUrl() . $this->getEndPoint());
            $options = array('headers' => $this->getHeaders());
            if (!empty($params)) {
                if ($method == 'GET') {
                    $options['query'] = $params;
                } else {
                    $options['body'] = $params;
                }
            }

            $request = $this->createRequest($method, $this->getEndPoint(), $options);

            $this->logRequest($request);

            $response = $this->send($request);

            return $this->convertResponse($response);

        } catch (\Exception $e) {
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
            $this->logRequestMessage(var_export($content, true));
            $this->convertException(new \Exception('Api response is not a json'));
        }
        $this->logRequestMessage('### RESPONSE ###');
        $this->logRequestMessage(var_export($array, true));
        $this->logRequestMessage('################');
        return $array;
    }

    /**
     * Analyse and convert an exception
     * @param \Exception $e
     * @return mixed|string
     */
    protected function convertException(\Exception $e)
    {
        $this->module->logs->errorLogs('# Exception : ' . get_class($e) . ' : ' . $e->getMessage());

        if ($e instanceof ClientException) {
            $message = json_decode($e->getResponse()->getBody()->getContents(), true);
            $this->module->logs->errorLogs(var_export($message, true));
            return $message;
        }

        return $e->getMessage();
    }

    /**
     * Log a request
     * @param RequestInterface $request
     */
    protected function logRequest(RequestInterface $request)
    {
        $body = $request->getBody();
        if (!empty($body)) {
            $this->logRequestMessage('# with body ' . $body);
        }

        $query = $request->getQuery();
        if (!empty($query)) {
            $this->logRequestMessage('# with query ' . var_export($query, true));
        }

        $headers = $request->getHeaders();
        if (!empty($headers)) {
            $this->logRequestMessage('# with headers ' . var_export($headers, true));
        }
    }

    /**
     * Log a message in the good request file
     * @param string $msg
     */
    protected function logRequestMessage($msg)
    {
        $this->module->logs->requestLogs($msg, $this->getEndPoint());
    }

    /**
     * Log a message in the infos file
     * @param string $msg
     */
    protected function logInfosMessage($msg)
    {
        $this->module->logs->infoLogs($msg);
    }
}