<?php

class Rx_Template_Ucwords extends Rx_Template_Abstract
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
        if ((in_array('utf', $params)) && (function_exists('mb_strtoupper'))) {
            $p = preg_split('/(\s+)/us', $value, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
            $value = '';
            foreach ($p as $t) {
                $value .= mb_strtoupper(mb_substr($t, 0, 1, 'utf-8'), 'utf-8') . mb_substr(
                        $t,
                        1,
                        mb_strlen($t, 'utf-8'),
                        'utf-8'
                    );
            }
            return ($value);
        } else {
            return (ucwords($value));
        }
    }
}
