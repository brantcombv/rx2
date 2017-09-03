<?php

class Rx_Validate_Phone extends Rx_Validate_Abstract
{
    const INVALID = 'phoneNumberInvalid';
    const TOLL_FREE = 'tollFree';

    protected $_messageTemplates = array(
        self::INVALID   => 'Phone number is not valid, it must be numeric and have at least 8 digits',
        self::TOLL_FREE => 'Toll free numbers are not allowed',
    );

    protected $tollFreeAllowed = false;

    public function isValid($value, $context = null)
    {
        $temp = preg_replace('/\D+/', '', $value);
        // There is no phone numbers with less then 8 digits
        if (strlen($temp) < 8) {
            $this->_error(self::INVALID);
            return (false);
        }
        // We also should not allow numbers with more then 2 lead zeroes
        if (substr($temp, 0, 3) == '000') {
            $this->_error(self::INVALID);
            return (false);
        }
        if (!$this->tollFreeAllowed) {
            // Check if we get toll free number
            if (preg_match('/^18(00|88)/', $temp)) {
                $this->_error(self::TOLL_FREE);
                return (false);
            }
        }

        return (true);
    }

    /**
     * Set option to allow use of toll-free numbers as phone number
     *
     * @param boolean $tollFreeAllowed
     */
    public function setTollFreeAllowed($tollFreeAllowed)
    {
        $this->tollFreeAllowed = $tollFreeAllowed;
    }

}