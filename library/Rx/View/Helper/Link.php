<?php

class Rx_View_Helper_Link extends Rx_View_Helper_HtmlElement
{

    /**
     * Render <a> link with given attributes and content
     *
     * @param string|array $url URL target as accepted by Rx_Url or Zend_View_Helper_Url
     * @param array $attrs      OPTIONAL List of attributes (can be skipped)
     * @param string $label     OPTIONAL Link label
     * @return string
     */
    public function link($url, $attrs = null, $label = null)
    {
        if ((is_string($attrs)) && ($label === null)) {
            $label = $attrs;
            $attrs = array();
        }
        $url = Rx_Url::url($url);
        if (!is_array($attrs)) {
            $attrs = array();
        }
        $attrs['href'] = $url;
        $html = $this->_element('a', $attrs, $label);
        return ($html);
    }

}
