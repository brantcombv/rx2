<?php

class Rx_Application_Resource_Language extends Rx_Application_Resource_Abstract
{
    /**
     * List of resources to bootstrap before running initialization
     *
     * @var array $_bootstrapResources
     */
    protected $_bootstrapResources = array('appstate', 'config', 'cache');

    /**
     * Perform resource initialization
     *
     * @return void
     */
    protected function _init()
    {
        // Actually it is not necessary to call bootstrap() directly because it will anyway be called
        // at a time of object instance creation and Rx_Language itself will take information about
        // languages from configuration and app.state and will setup Zend_Locale, but we must be sure
        // that we have configuration and cache initialization at a time of first access to this object
        Rx_Language::bootstrap();
    }

}
