<?php

class Rx_View_Helper_Url extends Zend_View_Helper_Abstract
{
    /**
     * MVC URL target for current page
     *
     * @var array $_url
     */
    protected $_url = null;

    /**
     * Generates an url from given url target
     *
     * @param  string|null $target OPTIONAL Url target as accepted by Rx_Url or Zend_View_Helper_Url
     * @param  array $params       OPTIONAL Additional parameters to set in Url
     * @param  bool $reset         OPTIONAL Whether or not to reset the route defaults with those provided
     * @param bool $encode
     * @return string               Url for the link href attribute.
     */
    public function url($target = null, $params = array(), $reset = true, $encode = true)
    {
        if (($target === null) || (!strlen($target))) {
            if (!$this->_url) {
                $this->_url = Rx_Url::parseRequest();
            }
            $target = $this->_url;
        }
        return (Rx_Url::url($target, $params, $reset, $encode));
    }
}
