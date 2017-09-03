<?php

class Rx_Application_Resource_Translate extends Rx_Application_Resource_Abstract
{
    /**
     * List of resources to bootstrap before running initialization
     *
     * @var array $_bootstrapResources
     */
    protected $_bootstrapResources = array('config', 'language', 'path', 'cache');

    /**
     * Perform resource initialization
     *
     * @return void
     */
    protected function _init()
    {
        // Actually it is not necessary to call bootstrap() directly because it will anyway be called
        // at a time of object instance creation and Rx_Translate itself will take information
        // from configuration, but we must be sure that we have all dependencies initialized
        Rx_Translate::bootstrap();

        // If we have view scripts handler registered in application - register translate view filter in it
        if ($this->getBootstrap()->isRegisteredResource('view', true)) {
            $view = $this->getBootstrap()
                ->bootstrap(array('view'))
                ->getResource('view');
            if ($view instanceof Zend_View) {
                $view->addFilter('translate');
            }
        }
    }

}
