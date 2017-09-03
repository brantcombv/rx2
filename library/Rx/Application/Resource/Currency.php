<?php

class Rx_Application_Resource_Currency extends Rx_Application_Resource_Abstract
{
    /**
     * List of resources to bootstrap before running initialization
     *
     * @var array $_bootstrapResources
     */
    protected $_bootstrapResources = array('appstate', 'config', 'language');

    /**
     * Perform resource initialization
     *
     * @return void
     */
    protected function _init()
    {
        // Actually it is not necessary to call bootstrap() directly because it will anyway be called
        // at a time of object instance creation and Rx_Currency itself will take information
        // from configuration, but we must be sure that we have all dependencies initialized
        Rx_Currency::bootstrap();
    }

}
