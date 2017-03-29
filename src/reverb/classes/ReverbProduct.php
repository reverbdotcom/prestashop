<?php

namespace Reverb;

/**
 * Client Product
 *
 * @package Reverb
 * @author Johan Protin
 * @copyright Copyright (c) 2017 - Johan Protin
 * @license
 */
class ReverbProduct extends ReverbClient
{

    CONST REVERB_PRODUCT_ENDPOINT = 'listings';
    CONST REVERB_ROOT_KEY = 'conditions';

    CONST REVERB_CODE_SUCCESS = 'success';
    CONST REVERB_CODE_ERROR = 'error';
    CONST REVERB_CODE_TO_SYNC = 'to_sync';

    public function __construct($module)
    {
        parent::__construct($module);
        $this->setEndPoint(self::REVERB_PRODUCT_ENDPOINT)
            ->setRootKey(self::REVERB_ROOT_KEY);
    }

    /**
     * Get Reverb product by sku
     * @param $sku
     * @return array|bool
     */
    public function getProduct($sku)
    {
        $this->setEndPoint('my/' . self::REVERB_PRODUCT_ENDPOINT);
        $search = array(
            'state' => 'all',
            'sku' => $sku,
        );
        $product = $this->sendGet($search);

        if (!empty($product['total']) && $product['total'] == 1) {
            return $product['listings'][0];
        }
        return false;
    }

    /**
     *  Send a product to reverb (POST or PUT)
     *
     * @param array $product
     * @param string $origin
     * @return array
     */
    public function syncProduct($product, $origin)
    {
        $this->logInfosMessage('##########################');
        $this->logInfosMessage('# BEGIN Request SYNC product ' . $product['reference']);
        $this->logInfosMessage('##########################');

        try {
            // Call Reverb API and process request
            $reverbSlug = isset($product['reverb_slug']) ? $product['reverb_slug'] : false;

            // Map Product Array To Model To json
            $request = $this->mapRequestForProduct($product, $reverbSlug);

            // Send POST or PUT
            if ($reverbSlug) {
                // Product was already sync to Reverb => PUT
                $this->module->logs->infoLogs('Product ' . $reverbSlug . ' already sync on Reverb => PUT');
                $this->setEndPoint($this->getEndPoint() . '/' . $reverbSlug);
                $response = $this->sendPut($request);

            } else {

                // Checks if product already exists on Reberb
                $reverbProduct = $this->getProduct($product['reference']);
                $this->setEndPoint(self::REVERB_PRODUCT_ENDPOINT);

                if (!empty($reverbProduct)) {
                    // Product already exists on Reberb => PUT
                    $reverbSlug = $this->getReverbProductSlug($reverbProduct);

                    $this->module->logs->infoLogs('Product ' . $reverbSlug . ' already exists on Reverb => PUT');

                    $this->setEndPoint($this->getEndPoint() . '/' . $reverbSlug);
                    $response = $this->sendPut($request);

                } else {
                    // Product does not exist on Reberb => POST
                    $this->module->logs->infoLogs('Product ' . $reverbSlug . ' does not exist on Reverb yet => POST');
                    $response = $this->sendPost($request);
                }
            }

            $return = $this->proccessResponse($product, $response, $origin);

        } catch (\Exception $e) {
            $return = $this->proccessTechnicalError($e);
        }

        $this->logInfosMessage('##########################');
        $this->logInfosMessage('# END Request SYNC product');
        $this->logInfosMessage('##########################');

        return $return;
    }

    /**
     *  Process Technical Eror
     *
     * @param \Exception $e
     * @return array
     */
    private function proccessTechnicalError($e) {
        return array(
            'status' => 'error',
            'message' => $e->getTraceAsString(),
        );
    }

    /**
     *  Process response from Reverb API
     *
     * @param array $product
     * @param array $response
     * @param string $origin
     * @return array
     */
    private function proccessResponse($product, $response, $origin)
    {
        $return = array();

        // Get Reverb ID and slug from response
        $reverbSlug = $this->getReverbProductSlugFromResponse($response);
        $reverbId = $this->getReverbProductIdFromResponse($response);

        // Check sync status
        if(count($response['errors']) == 0) {
            $status = self::REVERB_CODE_SUCCESS;
        } else {
            $status = self::REVERB_CODE_ERROR;
        }

        // Construct return response
        $return['status'] = $status;
        $return['message'] = $response['message'];
        $return['reverb-slug'] = $reverbSlug;
        $return['reverb-id'] = $reverbId;

        // Insert or update sync on DB
        $lastSync = $this->module->reverbSync->insertOrUpdateSyncStatus(
            $product['id_product'],
            $status,
            $response['message'],
            $reverbId,
            $reverbSlug,
            $origin
        );

        $return['last-synced'] = $lastSync['date'];

        return $return;
    }

    /**
     * Find the reverb slug from response
     * @param array $response
     * @return string
     */
    private function getReverbProductSlugFromResponse($response)
    {
        $slug = null;
        if (array_key_exists('listing',$response) && !empty($response['listing'])) {
            return $this->getReverbProductSlug($response['listing']);
        }
        return $slug;
    }

    /**
     * Find the reverb slug from product
     * @param array $product
     * @return string
     */
    private function getReverbProductSlug($product)
    {
        $slug = null;
        if (array_key_exists('_links',$product) && !empty($product['_links'])) {
            $url = $product['_links']['update']['href'];
            $slug = substr($url, strrpos($url, '/') + 1);
        }
        return $slug;
    }

    /**
     * Find the reverb id from response
     * @param array $response
     * @return int|null
     */
    private function getReverbProductIdFromResponse($response)
    {
        $id = null;
        if (array_key_exists('listing',$response) && !empty($response['listing']['id'])) {
            $id = (int) $response['listing']['id'];
        }
        return $id;
    }

    /*
     *  Map prestashop product to Reverb Model
     *  @param array $product
     *  @param string|false $reverbSlug
     *  @return json
     */
    private function mapRequestForProduct($product, $reverbSlug) {
        $mapper = new \ProductMapper($this->module);

        $mapper->processMapping($product, $reverbSlug);

        return $mapper->getObjetForRequest();

    }
}
