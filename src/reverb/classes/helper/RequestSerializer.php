<?php

use Reverb\Mapper\Models\AbstractModel;

class RequestSerializer
{
    /**
     * @var $_request Request object
     */
    private $_request;
    
    /**
     * Construct a new Serializer
     * 
     * @param $request
     */
    public function __construct($request){
        $this->_request = $request;
    }
    
    /**
     * Returns data string as Json
     * 
     * @param bool $pretty If true, display with pretty format (const: JSON_PRETTY_PRINT)
     * @return string Return result of json_encode function
     */
    public function toJson($pretty=false){
        return json_encode($this->toArray(),$pretty ? JSON_PRETTY_PRINT : 0);
    }
    
    
    /**
	 * Returns data array with only scalar values
	 * @return array Hashed array with key/valur pairs (property/value)
	 */
    public function toArray() {
        $params = array();
        
        //prepare an array of scalar properties
        $this->prepareParams($this->_request, $params);
        return $params;
    }
    
    /**
     * Populate $_params array with data to send
     * 
     * If one of properties is type of AbstractModel
     * This method will be called recursively
     * 
     * @param AbstractModel $object Object source
     * @param array $params Passed by reference
     *
     */
    protected function prepareParams($object,&$params) {
        //Get all readble object properties
        $properties = get_object_vars($object);

        /**
         * Else if value of property is scalar we assign it
         */
        foreach ($properties as $p=>$v){
            if(
                (is_object($v) && $v instanceof AbstractModel)
                || (
                    is_array($v)
                    && isset($v[0])
                    && is_object($v[0])
                    && $v[0] instanceof Reverb\Mapper\Models\AbstractModel
                )
            ) {

                if (is_array($v)) {
                    $params[$p] = array();
                    foreach ($v as $i) {
                        $params[$p] =  $this->prepareParams($i,$params[$p]);
                    }
                } else {
                    $params[$p] = $this->prepareParams($v,$params[$p]);
                }
            }
            if(is_scalar($v) || (is_object($v) || is_array($v))) {
                if ($v == "null"){
                    $params[$p] = "";
                }else{
                    $params[$p] = $v;
                }
            }
        }
        return $params;
    }
}