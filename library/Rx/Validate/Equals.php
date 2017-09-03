<?php

class Rx_Validate_Equals extends Rx_Validate_Abstract
{
    const NOT_EQUAL = 'notEqual';

    protected $_messageTemplates = array(
        self::NOT_EQUAL => 'Strings are not equal',
    );

    protected $name = null;

    public function isValid($value, $context = null)
    {
        $compare = ((is_array($context)) && (array_key_exists($this->name, $context))) ? $context[$this->name] : null;
        if ($value != $compare) {
            $this->_error(self::NOT_EQUAL);
            return (false);
        }
        return (true);
    }

    /**
     * Set form field name to compare value to
     *
     * @param string $name Form field name to compare value to
     */
    public function setName($name)
    {
        $this->name = $name;
    }

}
