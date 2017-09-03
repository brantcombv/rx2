<?php

abstract class Rx_Template_Abstract
{
    /**
     * Apply modification to given template value
     *
     * @param string $value Value to apply modification to
     * @param string $name  Placeholder name which will get modified value
     * @param array $args   Modifier arguments defined in template
     * @return string
     */
    abstract public function apply($value, $name = null, $args = array());
}
