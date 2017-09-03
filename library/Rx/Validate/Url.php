<?php

class Rx_Validate_Url extends Rx_Validate_Abstract
{
    const INVALID = 'invalidUrl';

    protected $_messageTemplates = array(
        self::INVALID => 'Invalid URL',
    );

    /**
     * Returns true if and only if $value meets the validation requirements
     *
     * @param  mixed $value
     * @return boolean
     * @throws Zend_Validate_Exception If validation of $value is impossible
     */
    public function isValid($value)
    {
        try {
            $url = Zend_Uri_Http::fromString($value);
            if (!$url->valid()) {
                $this->_error(self::INVALID);
                return (false);
            }
        } catch (Zend_Uri_Exception $e) {
            $this->_error(self::INVALID);
            return (false);
        }
        return (true);
    }

}
