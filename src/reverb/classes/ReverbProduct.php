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

    CONST REVERB_CODE_SUCCESS = 'success';
    CONST REVERB_CODE_ERROR = 'error';
    CONST REVERB_CODE_TO_SYNC = 'to_sync';

    /**
     *  Send a product to reverb (POST or PUT)
     *
     * @param array $product
     * @return array
     */
    public function syncProduct($product)
    {
        $this->module->logs->requestLogs('##########################');
        $this->module->logs->requestLogs('# BEGIN Request SYNC product');
        $this->module->logs->requestLogs('##########################');

        $endPoint = self::REVERB_PRODUCT_ENDPOINT;

        try {
            // Call Reverb API and process request
            $reverbSlug = isset($product['reverb_slug']) ? $product['reverb_slug'] : false;

            // Map Product Array To Model To json
            $request = $this->mapRequestForProduct($product, $reverbSlug);

            // Send POST or PUT
            if ($reverbSlug) {
                $endPoint .= '/' . $reverbSlug;
                $response = $this->sendPut($endPoint, $request);
            } else {
                $response = $this->sendPost($endPoint,$request);
            }

            $return = $this->proccessResponse($product, $response);

        } catch (\Exception $e) {
            throw $e;
            $return = $this->proccessTechnicalError($e);
        }

        $this->module->logs->requestLogs('##########################');
        $this->module->logs->requestLogs('# END Request SYNC product');
        $this->module->logs->requestLogs('##########################');

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
     * @return array
     */
    private function proccessResponse($product, $response)
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
            $reverbSlug
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
            $url = $response['listing']['_links']['update']['href'];
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
        if (array_key_exists('listing',$response) && !empty($response['listing'])) {
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
