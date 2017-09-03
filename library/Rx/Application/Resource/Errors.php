<?php

class Rx_Application_Resource_Errors extends Rx_Application_Resource_Abstract
{
    /**
     * List of resources to bootstrap before running initialization
     *
     * @var array $_bootstrapResources
     */
    protected $_bootstrapResources = array('config', 'path');

    /**
     * Perform resource initialization
     *
     * @return void
     */
    protected function _init()
    {
        Rx_ErrorsHandler::bootstrap();
    }

}
