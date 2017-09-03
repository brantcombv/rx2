<?php

abstract class Rx_Validate_Abstract extends Zend_Validate_Abstract
{

    /**
     * Class constructor
     *
     * @param array|Zend_Config $options OPTIONAL Configuration options for validator
     * @return void
     */
    public function __construct($options = null)
    {
        // Provide standard way for configuring validators
        // by using setters for configuration options passed to constructor
        if (($options instanceof Zend_Config) ||
            ((is_object($options)) && (is_callable(array($options, 'toArray'))))
        ) {
            $options = $options->toArray();
        }
        if (!is_array($options)) {
            return;
        }
        foreach ($options as $name => $value) {
            $method = 'set' . ucfirst($name);
            if (!method_exists($this, $method)) {
                trigger_error(
                    'No setter method is available for validator configuration option: ' . $name,
                    E_USER_WARNING
                );
                continue;
            }
            $this->$method($value);
        }
    }

}
