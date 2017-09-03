<?php

class Rx_Validate_Uid extends Rx_Validate_Abstract
{
    const INVALID = 'invalid';

    protected $_messageTemplates = array(
        self::INVALID => 'Invalid value format',
    );

    public function isValid($value, $context = null)
    {
        if (!Rx_Uid::isUid($value)) {
            $this->_error(self::INVALID);
            return (false);
        }

        return (true);
    }

}