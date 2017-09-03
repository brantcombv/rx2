<?php

class Rx_View_Helper_Attribs extends Zend_View_Helper_HtmlElement
{

    /**
     * Render given array of HTML attributes
     *
     * @param array $attribs Array of HTML attributes to render
     * @return string
     */
    public function attribs($attribs)
    {
        return ($this->_htmlAttribs($attribs));
    }

}
