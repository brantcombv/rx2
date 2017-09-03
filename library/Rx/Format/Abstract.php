<?php

abstract class Rx_Format_Abstract extends Rx_Configurable_Object
{
    /**
     * Format given value
     *
     * @param string $value                  Value to format
     * @param array $params                  OPTIONAL Additional parameters to use for formatting
     * @param array|Zend_Config|null $config OPTIONAL Configuration options for formatter plugin
     * @return string
     */
    public function format($value, $params = null, $config = null)
    {
        $config = $this->getConfig($config);
        if ($params === null) {
            $params = array();
        } elseif (!is_array($params)) {
            $params = array($params);
        }
        $result = $this->_format($value, $params, $config);
        return ($result);
    }

    /**
     * Format given value
     *
     * @param string $value Value to format
     * @param array $params Additional parameters to use for formatting
     * @param array $config Configuration options for formatter plugin
     * @return string
     */
    abstract protected function _format($value, $params, $config);

}
