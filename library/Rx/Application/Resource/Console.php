<?php

class Rx_Application_Resource_Console extends Rx_Application_Resource_Abstract
{

    /**
     * Perform resource initialization
     *
     * @return void
     */
    protected function _init()
    {
        // This plugin resource is more or less dummy and its primary purpose
        // is to show to other bootstrap resources that console application
        // is being bootstrapped
    }

}
