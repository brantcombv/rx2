<?php

class Rx_Bootstrap_Web extends Rx_Bootstrap_Abstract
{

    /**
     * Class constructor
     *
     * @param  Zend_Application|Zend_Application_Bootstrap_Bootstrapper $application
     * @return Rx_Bootstrap_Web
     * @throws Zend_Application_Bootstrap_Exception When invalid application is provided
     */
    public function __construct($application)
    {
        parent::__construct($application);
        if (!$this->isRegisteredResource('frontcontroller')) {
            $this->registerPluginResource('frontcontroller');
        }
    }

    /**
     * Run the application
     *
     * @throws Zend_Application_Bootstrap_Exception
     * @return void
     */
    public function run()
    {
        $controller = $this->getResource('frontcontroller');
        if (!$controller instanceof Zend_Controller_Front) {
            $controller = Zend_Controller_Front::getInstance();
        }
        $default = $controller->getDefaultModule();
        if ($controller->getControllerDirectory($default) === null) {
            throw new Zend_Application_Bootstrap_Exception('No default controller directory registered with front controller');
        }
        $controller->setParam('bootstrap', $this);
        $controller->dispatch();
    }

}
