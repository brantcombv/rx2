<?php

interface Rx_Json_Handler_Interface
{

    /**
     * Pre-process given value before it will be encoded in JSON
     *
     * @param mixed $value             Reference to value to pre-process
     * @param string $path             "Path" of given value within encoded structure (separated by "|")
     * @param Rx_Json_Encoder $encoder Encoder object
     * @return string|null                  New identifier for pre-processed value or null if no pre-processing was done
     */
    public function jsonEncoderPreProcess(&$value, $path, $encoder);

    /**
     * Post-process given value after it was decoded from JSON
     *
     * @param string $name             Name of pre-processed value from JSON format
     * @param mixed $value             Pre-processed value
     * @param string $path             "Path" of given value within JSON structure (separated by "|")
     * @param Rx_Json_Decoder $decoder Decoder object
     * @return mixed                        Post-processed value
     */
    public function jsonDecoderPostProcess($name, $value, $path, $decoder);

}
