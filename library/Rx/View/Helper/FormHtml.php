<?php

class Rx_View_Helper_FormHtml extends Zend_View_Helper_FormElement
{

    /**
     * Generate static HTML content as form element
     * HTML code should be passed as "html" attribute, it can be templated
     * by using %name% placeholders where "name" is names of passed attributes
     * or [id,name,value]
     * Use "hidden" attribute to add hidden form element together with rendered HTML code,
     * value of this attribute is treated as list of additional attributes for hidden form element
     *
     * @param string|array $name        If a string, the element name.
     *                                  If an array, all other parameters are ignored,
     *                                  and the array elements are extracted in place of added parameters.
     * @param mixed $value              OPTIONAL The element value.
     * @param array $attribs            OPTIONAL Attributes for the element tag.
     *
     * @return string
     */
    public function formHtml($name, $value = null, $attribs = null)
    {
        $info = $this->_getInfo($name, $value, $attribs);
        $html = '';
        // Render hidden form element if required
        if (array_key_exists('hidden', $info['attribs'])) {
            $attrs = (is_array($info['attribs']['hidden'])) ? $info['attribs']['hidden'] : array();
            $html = $this->_hidden($info['name'], $info['value'], $attrs);
            unset($info['attribs']['hidden']);
        }
        // Render given HTML template
        $tpl = (array_key_exists('html', $info['attribs'])) ? $info['attribs']['html'] : '%value%';
        unset($info['attribs']['html']);
        $map = $info['attribs'];
        foreach (array('name', 'id', 'value') as $n) {
            if (!array_key_exists($n, $map)) {
                $map[$n] = $info[$n];
            }
        }
        $t = $map;
        $map = array();
        foreach ($t as $k => $v) {
            $map['%' . $k . '%'] = $v;
        }
        $html .= strtr($tpl, $map);
        return ($html);
    }

}