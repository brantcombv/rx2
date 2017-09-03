<?php

class Rx_Filter_Float implements Zend_Filter_Interface
{

    /**
     * true to allow negative floats, false to filter them
     *
     * @var boolean $_allowNegative
     */
    protected $_allowNegative = false;

    /**
     * Class constructor
     *
     * @param boolean $allowNegative OPTIONAL true to allow negative floats, false to filter them
     * @return void
     */
    public function __construct($allowNegative = false)
    {
        $this->setAllowNegative($allowNegative);
    }

    /**
     * Defined by Zend_Filter_Interface
     *
     * @param  string $value
     * @return string
     */
    public function filter($value)
    {
        $value = str_replace(',', '.', $value);
        $value = preg_replace('/[^0-9' . (($this->getAllowNegative()) ? '\-' : '') . '\.]+/', '', $value);
        $value = (double)$value;
        return ($value);
    }

    /**
     * Get "allow negative" flag value
     *
     * @return boolean
     */
    public function getAllowNegative()
    {
        return ($this->_allowNegative);
    }

    /**
     * Set "allow negative" flag
     *
     * @param boolean $allowNegative true to allow negative floats, false to filter them
     * @return Rx_Filter_Float
     */
    public function setAllowNegative($allowNegative)
    {
        $this->_allowNegative = (boolean)$allowNegative;
        return ($this);
    }

}
