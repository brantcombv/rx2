<?php

class Rx_Template_Translate extends Rx_Template_Abstract
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
        $language = (sizeof($params)) ? array_shift($params) : null;
        return (Rx_Translate::translate($value, false, $language));
    }
}
