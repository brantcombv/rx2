<?php

class Rx_Filter_Url implements Zend_Filter_Interface
{

    /**
     * Returns the result of filtering $value
     *
     * @param  mixed $value
     * @throws Zend_Filter_Exception If filtering $value is impossible
     * @return mixed
     */
    public function filter($value)
    {
        if (!strlen(trim($value))) {
            return ($value);
        }
        $p = parse_url($value);
        if (!is_array($p)) {
            return ($value);
        }
        if (array_key_exists('scheme', $p)) {
            return ($value);
        }
        $validator = new Zend_Validate_Hostname();
        if (!$validator->isValid($value)) {
            return ($value);
        }
        $value = 'http://' . $value;
        return ($value);
    }

}