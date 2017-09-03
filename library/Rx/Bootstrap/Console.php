<?php

class Rx_Bootstrap_Console extends Rx_Bootstrap_Abstract
{

    /**
     * Class constructor
     *
     * @param  Zend_Application|Zend_Application_Bootstrap_Bootstrapper $application
     * @return Rx_Bootstrap_Console
     * @throws Zend_Application_Bootstrap_Exception When invalid application is provided
     */
    public function __construct($application)
    {
        parent::__construct($application);
        if (!$this->isRegisteredResource('console')) {
            $this->registerPluginResource('console');
        }
    }

    /**
     * Run the application
     *
     * @param Rx_Console_Process_Abstract $process OPTIONAL Console process object to run
     * @return void
     */
    public function run($process = null)
    {
        $controller = Rx_Console_Controller::getInstance();
        if ($process instanceof Rx_Console_Process_Abstract) {
            $controller->setProcess($process);
        }
        $controller->run();
    }

}
