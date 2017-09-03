<?php

class Rx_View_Helper_Get extends Zend_View_Helper_Abstract
{

    /**
     * Get view variable with fallback to default value
     *
     * @param string $var    View variable name to get
     * @param mixed $default OPTIONAL Default value for variable
     * @return mixed
     */
    public function get($var, $default = null)
    {
        if (isset($this->view->$var)) {
            return ($this->view->$var);
        } else {
            return ($default);
        }
    }

}
