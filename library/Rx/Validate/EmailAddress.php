<?php

class Rx_Validate_EmailAddress extends Zend_Validate_EmailAddress
{
    const INVALID = 'emailAddressInvalid';

    protected $_messageTemplates = array(
        self::INVALID => 'Invalid email address',
    );

    public function isValid($value)
    {
        $valid = parent::isValid($value);
        if (!$valid) {
            $this->_messages = array();
            $this->_error(self::INVALID);
        }
        return ($valid);
    }

}
