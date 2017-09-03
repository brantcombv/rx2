<?php

class Rx_Template_Lower extends Rx_Template_Abstract
{
    /**
     * Apply modification to given template value
     *
     * @param string $value Value to apply modification to
     * @param string $name  Placeholder name which will get modified value
     * @param array $params Modifier arguments defined in template
     * @return string
     */
    public function apply($value, $name = null, $params = array())
    {
        if ((in_array('utf', $params)) && (function_exists('mb_strtolower'))) {
            return (mb_strtolower($value, 'utf-8'));
        } else {
            return (strtolower($value));
        }
    }
}
