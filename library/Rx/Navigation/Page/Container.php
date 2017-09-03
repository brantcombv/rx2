<?php

class Rx_Navigation_Page_Container extends Rx_Navigation_Page
{

    /**
     * Returns href for this page
     *
     * @throws Zend_Navigation_Exception
     * @return string  the page's href
     */
    public function getHref()
    {
        throw new Zend_Navigation_Exception('Container pages can\'t be directly used in navigation');
    }

}