<?php

namespace Reverb;

use PhpParser\Node\Expr\Cast\Array_;

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

    /**
     *  Send an product to reverb (POST or PUT)
     *
     * @param null $uuid
     * @return array
     */
    public function syncProduct($product,$uuid = null)
    {
        $this->module->logs->requestLogs('##########################');
        $this->module->logs->requestLogs('# BEGIN Request SYNC product');
        $this->module->logs->requestLogs('##########################');

        $endPoint = self::REVERB_PRODUCT_ENDPOINT;

        if ($uuid) {
            $endPoint .= '/' . $uuid;
        }

        try {
            // Map Product Array To Model To json
            $request = $this->mapRequestForProduct($product);

            // Call Reverb API and process request
            $sync = \ReverbSync::getSyncStatus($product['id_product']);
            if ($sync && $sync[0]['reverb_ref']) {
                $response = $this->sendPut($endPoint,$request,$sync[0]['reverb_ref']);
            } else {
                $response = $this->sendPost($endPoint,$request);
            }

            $this->proccessResponse($product,$response);

        } catch (\Exception $e) {
            $this->proccessTechnicalError($product,$e);
        }

        $this->module->logs->requestLogs('##########################');
        $this->module->logs->requestLogs('# END Request SYNC produt');
        $this->module->logs->requestLogs('##########################');

        return $response;
    }

    /**
     *  Process Technical Eror
     *
     * @param $response
     */
    private function proccessTechnicalError($product,$e) {
        $response['message'] = $e->getTraceAsString();
        $response['errors'] = 1;
        $this->proccessResponse($product,$response);
    }

    /**
     *  Process response from Reverb API
     *
     * @param $response
     */
    private function proccessResponse($product,$response) {
        if ($response['listing']){
            $slug = str_replace($this->getBaseUrl() . self::REVERB_PRODUCT_ENDPOINT . '/' ,'',$response['listing']['_links']['update']['href']);
            $url = $response['listing']['_links']['web']['href'];
        }

        if(count($response['errors']) == 0) {
            $this->module->reverbSync->insertOrUpdateSyncStatus($product['id_product'],self::REVERB_CODE_SUCCESS,
                                                                $response['message'],$slug,$url);
        }else{
            $this->module->reverbSync->insertOrUpdateSyncStatus($product['id_product'],self::REVERB_CODE_ERROR,
                                                                $response['message'],$slug,$url);
        }
    }

    /*
     *  Map prestashop product to Reverb Model
     *  @param array $product
     *  @return json
     */
    private function mapRequestForProduct($product) {
        $mapper = new \ProductMapper();

        $mapper->processMapping($product);

        return $mapper->getObjetForRequest();

    }
}
