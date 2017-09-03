<?php

class Rx_Json_Handler_Default implements Rx_Json_Handler_Interface
{

    /**
     * Pre-process given value before it will be encoded in JSON
     *
     * @param mixed $value             Reference to value to pre-process
     * @param string $path             "Path" of given value within encoded structure (separated by "|")
     * @param Rx_Json_Encoder $encoder Encoder object
     * @return string|null                  New identifier for pre-processed value or null if no pre-processing was done
     */
    public function jsonEncoderPreProcess(&$value, $path, $encoder)
    {
        // Objects should normally be converted to arrays
        if ((is_object($value)) && (!$value instanceof Zend_Json_Expr)) {
            if (method_exists($value, 'toArray')) {
                $value = $value->toArray();
            } elseif (method_exists($value, 'toString')) {
                $value = $value->toString();
            } elseif (method_exists($value, '__toString')) {
                $value = $value->__toString();
            }
        }
        // Encode binary strings in base64
        if ((is_string($value)) && (preg_match('/[\x00-\x08\x0B\x0E-\x1F]/s', $value))) {
            $value = base64_encode($value);
            return ('base64');
        }
        return null;
    }

    /**
     * Post-process given value after it was decoded from JSON
     *
     * @param string $name             Name of pre-processed value from JSON format
     * @param mixed $value             Pre-processed value
     * @param string $path             "Path" of given value within JSON structure (separated by "|")
     * @param Rx_Json_Decoder $decoder Decoder object
     * @return mixed                        Post-processed value
     */
    public function jsonDecoderPostProcess($name, $value, $path, $decoder)
    {
        switch ($name) {
            case 'base64':
                return (base64_decode($value));
                break;
            default:
                return ($value);
                break;
        }
    }

}
